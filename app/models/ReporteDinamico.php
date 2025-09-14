<?php
// Archivo: /app/models/ReporteDinamico.php
require_once __DIR__ . '/../../config/Database.php';

class ReporteDinamico
{
    private $conn;

    // Tabla base (tu flujo parte de ventas)
    private $baseTable = 'ventas';
    private $baseAlias = 'v';

    // Tablas que PERMITIMOS para el builder (lista blanca)
    // Agrega o quita aquí si quieres incluir más/menos.
    private $allowedTables = [
        'ventas',
        'venta_detalles',
        'clientes',
        'usuarios',
        'sucursales',
        'productos',
        'categorias',
        'marcas',
        'ventas_credito',
        'pagos_clientes',
        'producto_codigos',
        'producto_imagenes',
        'cliente_precios_especiales',
        'cliente_direcciones',
        'aperturas_caja'
    ];

    // Alias fijos legibles (se usan en keys tipo "v.fecha")
    private $tableAliases = [
        'ventas' => 'v',
        'venta_detalles' => 'vd',
        'clientes' => 'c',
        'usuarios' => 'u',
        'sucursales' => 's',
        'productos' => 'p',
        'categorias' => 'cat',
        'marcas' => 'm',
        'ventas_credito' => 'vc',
        'pagos_clientes' => 'pc',
        'producto_codigos' => 'pcod',
        'producto_imagenes' => 'pimg',
        'cliente_precios_especiales' => 'cpe',
        'cliente_direcciones' => 'cd',
        'aperturas_caja' => 'ac',
    ];

    // ====== Meta dinámico (se llena al iniciar) ======
    private $columnsMeta = [];   // "alias.col" => [label, alias, type, table]
    private $groupedColumns = []; // table => [ [key, alias, label, type]... ]
    private $dateCols = [];      // "alias.col" => "Etiqueta"
    private $defaultDateCol = ''; // "alias.col"

    // Grafo de FKs (ambidireccional) para armar JOINs
    // Estructura:
    //   $fkGraph['tablaA']['tablaB'][] = [ 'a_col' => 'id_x', 'b_col' => 'id_y' ]
    private $fkGraph = [];

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->bootstrapMeta();
    }

    // -------------------------------------------------
    // Carga columnas/tipos + grafo de FKs desde el esquema
    // -------------------------------------------------
    private function bootstrapMeta(): void
    {
        $dbName = $this->conn->query("SELECT DATABASE()")->fetchColumn();
        if (!$dbName) {
            throw new RuntimeException('No hay base seleccionada');
        }

        // 1) Columnas y tipos
        $inTables = implode("','", array_map('addslashes', $this->allowedTables));
        $sqlCols = "
            SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME IN ('{$inTables}')
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        ";
        $st = $this->conn->prepare($sqlCols);
        $st->execute([':db' => $dbName]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $this->columnsMeta = [];
        $this->groupedColumns = [];
        $this->dateCols = [];
        $numTypes = ['int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'decimal', 'numeric', 'float', 'double', 'real'];
        $dateTypes = ['date', 'datetime', 'timestamp', 'time', 'year'];

        foreach ($rows as $r) {
            $t = $r['TABLE_NAME'];
            $c = $r['COLUMN_NAME'];
            $dtype = strtolower($r['DATA_TYPE']);
            if (!isset($this->tableAliases[$t]))
                continue; // alias requerido
            $ta = $this->tableAliases[$t];

            // tipo para UI/agregaciones
            $type = in_array($dtype, $numTypes, true) ? 'number' : (in_array($dtype, $dateTypes, true) ? 'datetime' : 'text');

            $key = "{$ta}.{$c}";
            $alias = "{$ta}_{$c}";
            $label = ucfirst($t) . ' · ' . $c;

            $this->columnsMeta[$key] = [
                'label' => $label,
                'alias' => $alias,
                'type' => $type,
                'table' => $t,
            ];
            $this->groupedColumns[$t][] = [
                'key' => $key,
                'alias' => $alias,
                'label' => $label,
                'type' => $type,
            ];
            if ($type === 'datetime') {
                $this->dateCols[$key] = $label;
            }
        }

        // default date col: v.fecha si existe; si no, la primera datetime
        $vFecha = $this->tableAliases[$this->baseTable] . '.fecha';
        $this->defaultDateCol = isset($this->columnsMeta[$vFecha])
            ? $vFecha
            : (array_key_first($this->dateCols) ?: $this->tableAliases[$this->baseTable] . '.id'); // fallback

        // 2) FKs → grafo
        $sqlFK = "
            SELECT
              TABLE_NAME           AS child_table,
              COLUMN_NAME          AS child_column,
              REFERENCED_TABLE_NAME AS parent_table,
              REFERENCED_COLUMN_NAME AS parent_column
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = :db
              AND REFERENCED_TABLE_NAME IS NOT NULL
              AND TABLE_NAME IN ('{$inTables}')
              AND REFERENCED_TABLE_NAME IN ('{$inTables}')
        ";
        $st = $this->conn->prepare($sqlFK);
        $st->execute([':db' => $dbName]);
        $fks = $st->fetchAll(PDO::FETCH_ASSOC);

        $this->fkGraph = [];
        foreach ($fks as $fk) {
            $a = $fk['child_table'];
            $aCol = $fk['child_column'];
            $b = $fk['parent_table'];
            $bCol = $fk['parent_column'];
            // A <-> B (grafo no dirigido, guardamos la condición en ambos sentidos)
            $this->fkGraph[$a][$b][] = ['a_col' => $aCol, 'b_col' => $bCol];
            $this->fkGraph[$b][$a][] = ['a_col' => $bCol, 'b_col' => $aCol]; // invertido
        }
    }

    // -------------------------------------------------
    // Público: meta para el builder (igual que antes)
    // -------------------------------------------------
    public function getMeta()
    {
        // Ordenar grupos por tabla
        $grouped = [];
        foreach ($this->allowedTables as $t) {
            $grouped[$t] = $this->groupedColumns[$t] ?? [];
        }

        return [
            'base' => $this->baseTable,
            'columns' => $grouped,
            'aggs' => ['SUM', 'COUNT', 'AVG', 'MIN', 'MAX'],
            'dateCols' => $this->dateCols,
            'defaultDateCol' => $this->defaultDateCol
        ];
    }

    // -------------------------------------------------
    // DataTables server-side (misma firma)
    // -------------------------------------------------
    public function getServerSide(array $params)
    {
        // 1) Config del front
        $config = [];
        if (isset($params['config'])) {
            $decoded = json_decode($params['config'], true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        $selected = isset($config['selectedColumns']) && is_array($config['selectedColumns'])
            ? $config['selectedColumns'] : [$this->baseAlias . '.fecha', $this->baseAlias . '.id', 'c.nombre', 'u.nombre', 's.nombre', $this->baseAlias . '.total'];

        $filters = isset($config['filters']) && is_array($config['filters']) ? $config['filters'] : [];
        $groupBy = isset($config['groupBy']) && is_array($config['groupBy']) ? $config['groupBy'] : [];
        $aggs = isset($config['aggregations']) && is_array($config['aggregations']) ? $config['aggregations'] : [];
        $dateCol = isset($config['dateCol']) && isset($this->columnsMeta[$config['dateCol']]) ? $config['dateCol'] : $this->defaultDateCol;
        $startDate = isset($config['startDate']) ? $config['startDate'] : null;
        $endDate = isset($config['endDate']) ? $config['endDate'] : null;

        // 2) DataTables params
        $draw = isset($params['draw']) ? (int) $params['draw'] : 1;
        $start = isset($params['start']) ? (int) $params['start'] : 0;
        $length = isset($params['length']) ? (int) $params['length'] : 10;
        $searchValue = isset($params['search']['value']) ? trim($params['search']['value']) : '';

        // 3) Validación y SELECT
        $selectParts = [];
        $orderableAliases = [];
        $neededTablesSet = [$this->baseTable => true];

        $aliasOf = function (string $key) {
            return $this->columnsMeta[$key]['alias'];
        };
        $tableOf = function (string $key) {
            return $this->columnsMeta[$key]['table'];
        };

        foreach ($selected as $key) {
            if (!isset($this->columnsMeta[$key]))
                continue;
            $alias = $aliasOf($key);
            $selectParts[] = "$key AS `$alias`";
            $orderableAliases[] = $alias;
            $neededTablesSet[$tableOf($key)] = true;
        }

        // Agregaciones
        $aggFunctions = ['SUM', 'COUNT', 'AVG', 'MIN', 'MAX'];
        $aggSelect = [];
        foreach ($aggs as $agg) {
            if (!isset($agg['fn'], $agg['col']))
                continue;
            $fn = strtoupper($agg['fn']);
            $col = $agg['col'];
            if (!in_array($fn, $aggFunctions, true))
                continue;
            if (!isset($this->columnsMeta[$col]))
                continue;

            $type = $this->columnsMeta[$col]['type'];
            if (!in_array($type, ['number'], true) && $fn !== 'COUNT')
                continue;

            $alias = isset($agg['alias']) && preg_match('/^[A-Za-z0-9_]+$/', $agg['alias'])
                ? $agg['alias']
                : strtolower($fn . '_' . $this->columnsMeta[$col]['alias']);

            $aggSelect[] = "$fn($col) AS `$alias`";
            $orderableAliases[] = $alias;
            $neededTablesSet[$tableOf($col)] = true;
        }

        $select = implode(", ", array_merge($selectParts, $aggSelect));
        if ($select === '') {
            $select = "{$this->baseAlias}.id AS `{$this->baseAlias}_id`";
            $orderableAliases[] = "{$this->baseAlias}_id";
        }

        // 4) FROM + JOINs (auto por FKs)
        $from = " FROM {$this->baseTable} {$this->baseAlias} ";
        $neededTables = array_keys($neededTablesSet);

        // Añade también tablas mencionadas en filtros y groupBy
        foreach ($filters as $f) {
            if (isset($f['col']) && isset($this->columnsMeta[$f['col']]))
                $neededTablesSet[$tableOf($f['col'])] = true;
        }
        foreach ($groupBy as $g) {
            if (isset($this->columnsMeta[$g]))
                $neededTablesSet[$tableOf($g)] = true;
        }
        $neededTables = array_keys($neededTablesSet);

        $joinStr = $this->buildJoinChain($neededTables); // arma el camino por FK

        // 5) WHERE: filtros + rango de fechas + buscador
        $where = " WHERE 1=1 ";
        $bind = [];

        // Fechas
        if ($startDate) {
            $where .= " AND $dateCol >= :_startDate ";
            $bind[':_startDate'] = $startDate . " 00:00:00";
        }
        if ($endDate) {
            $where .= " AND $dateCol <= :_endDate ";
            $bind[':_endDate'] = $endDate . " 23:59:59";
        }

        // Filtros
        foreach ($filters as $i => $f) {
            if (!isset($f['col'], $f['op']))
                continue;
            $col = $f['col'];
            if (!isset($this->columnsMeta[$col]))
                continue;

            $param = ":_f{$i}";
            $op = strtolower($f['op']);

            switch ($op) {
                case 'eq':
                    $where .= " AND $col = $param ";
                    $bind[$param] = $f['value'] ?? null;
                    break;
                case 'neq':
                    $where .= " AND $col <> $param ";
                    $bind[$param] = $f['value'] ?? null;
                    break;
                case 'gt':
                    $where .= " AND $col > $param ";
                    $bind[$param] = $f['value'] ?? null;
                    break;
                case 'gte':
                    $where .= " AND $col >= $param ";
                    $bind[$param] = $f['value'] ?? null;
                    break;
                case 'lt':
                    $where .= " AND $col < $param ";
                    $bind[$param] = $f['value'] ?? null;
                    break;
                case 'lte':
                    $where .= " AND $col <= $param ";
                    $bind[$param] = $f['value'] ?? null;
                    break;
                case 'like':
                    $where .= " AND $col LIKE $param ";
                    $bind[$param] = '%' . ($f['value'] ?? '') . '%';
                    break;
                case 'between':
                    if (isset($f['value'], $f['valueTo'])) {
                        $p1 = ':_f' . $i . 'a';
                        $p2 = ':_f' . $i . 'b';
                        $where .= " AND $col BETWEEN $p1 AND $p2 ";
                        $bind[$p1] = $f['value'];
                        $bind[$p2] = $f['valueTo'];
                    }
                    break;
            }
        }

        // Buscador global (solo columnas text seleccionadas)
        if ($searchValue !== '') {
            $likes = [];
            foreach ($selected as $key) {
                if (!isset($this->columnsMeta[$key]))
                    continue;
                if ($this->columnsMeta[$key]['type'] === 'text') {
                    $likes[] = "$key LIKE :_search";
                }
            }
            if (!empty($likes)) {
                $where .= " AND (" . implode(" OR ", $likes) . ") ";
                $bind[':_search'] = "%{$searchValue}%";
            }
        }

        // 6) GROUP BY
        $groupCols = [];
        if (!empty($aggSelect)) {
            foreach ($selected as $key) {
                if (isset($this->columnsMeta[$key]))
                    $groupCols[] = $key;
            }
        } else {
            foreach ($groupBy as $key) {
                if (isset($this->columnsMeta[$key]))
                    $groupCols[] = $key;
            }
        }
        $groupSql = '';
        if (!empty($groupCols))
            $groupSql = " GROUP BY " . implode(", ", array_unique($groupCols));

        // 7) ORDER BY (DataTables)
        $orderSql = '';
        if (isset($params['order'][0]['column'], $params['order'][0]['dir'])) {
            $idx = (int) $params['order'][0]['column'];
            $dir = strtolower($params['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            if (isset($orderableAliases[$idx])) {
                $orderAlias = $orderableAliases[$idx];
                $orderSql = " ORDER BY `$orderAlias` $dir ";
            }
        }

        // 8) Totales
        $sqlCountTotal = "SELECT COUNT(*) AS cnt FROM {$this->baseTable} {$this->baseAlias}";
        $stmtTotal = $this->conn->query($sqlCountTotal);
        $recordsTotal = (int) $stmtTotal->fetchColumn();

        $sqlCountFiltered = "SELECT COUNT(*) AS cnt $from $joinStr $where $groupSql";
        $stmtFiltered = $this->conn->prepare($sqlCountFiltered);
        foreach ($bind as $k => $v)
            $stmtFiltered->bindValue($k, $v);
        $stmtFiltered->execute();
        $recordsFiltered = (int) $stmtFiltered->fetchColumn();

        // 9) Data page
        $sqlData = "SELECT $select $from $joinStr $where $groupSql $orderSql LIMIT :_start, :_len";
        $stmtData = $this->conn->prepare($sqlData);
        foreach ($bind as $k => $v)
            $stmtData->bindValue($k, $v);
        $stmtData->bindValue(':_start', $start, PDO::PARAM_INT);
        $stmtData->bindValue(':_len', $length, PDO::PARAM_INT);
        $stmtData->execute();
        $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    // -------------------------------------------------
    // Arma la cadena de JOINs desde base -> tablas requeridas
    // Usa BFS en el grafo de FKs para encontrar el camino.
    // -------------------------------------------------
    private function buildJoinChain(array $neededTables): string
    {
        // Quitar base y duplicados
        $needed = array_values(array_unique(array_filter($neededTables, fn($t) => $t !== $this->baseTable)));

        if (empty($needed))
            return ''; // nada que unir

        $present = [$this->baseTable => true];
        $joins = [];       // lista de pares [A,B] en orden de unión
        $addedPairs = [];  // set "A|B" para no duplicar
        $toAddTables = [];

        foreach ($needed as $target) {
            $path = $this->bfsPath($this->baseTable, $target);
            if (empty($path))
                continue; // no hay camino; se ignora silenciosamente

            // Camina por pares consecutivos: t0->t1, t1->t2, ...
            for ($i = 0; $i < count($path) - 1; $i++) {
                $A = $path[$i];
                $B = $path[$i + 1];
                $key = $A . '|' . $B;
                $key2 = $B . '|' . $A;
                if (!isset($addedPairs[$key]) && !isset($addedPairs[$key2])) {
                    $joins[] = [$A, $B];
                    $addedPairs[$key] = true;
                }
                $toAddTables[$A] = true;
                $toAddTables[$B] = true;
            }
        }

        // Construir SQL de JOIN en un orden que respete que la izquierda ya esté presente
        $sql = '';
        $presentAliases = [$this->baseTable => true];

        // para lookup rápido de condiciones A<->B
        $condAB = function ($A, $B) {
            // Puede haber múltiples columnas FK entre A y B → concatenamos con AND
            $conds = $this->fkGraph[$A][$B] ?? $this->fkGraph[$B][$A] ?? [];
            if (empty($conds))
                return null;

            $aAlias = $this->tableAliases[$A];
            $bAlias = $this->tableAliases[$B];

            $pieces = [];
            foreach ($conds as $c) {
                // $c tiene a_col,b_col desde la perspectiva del índice que guardamos
                // Usamos alias según A/B concretos
                if (isset($this->fkGraph[$A][$B])) {
                    $pieces[] = "{$aAlias}.{$c['a_col']} = {$bAlias}.{$c['b_col']}";
                } else {
                    // invertido
                    $pieces[] = "{$aAlias}.{$c['b_col']} = {$bAlias}.{$c['a_col']}";
                }
            }
            return implode(' AND ', $pieces);
        };

        // Vamos intentando agregar hasta que no haya cambios
        $pending = $joins;
        $guard = 0;
        while (!empty($pending) && $guard++ < 200) {
            $next = [];
            foreach ($pending as [$A, $B]) {
                // Si A está presente y B no → unimos B con A
                if (isset($presentAliases[$A]) && !isset($presentAliases[$B])) {
                    $on = $condAB($A, $B);
                    if ($on) {
                        $sql .= " LEFT JOIN {$B} {$this->tableAliases[$B]} ON {$on} ";
                        $presentAliases[$B] = true;
                        continue;
                    }
                }
                // Si B está presente y A no → unimos A con B
                if (isset($presentAliases[$B]) && !isset($presentAliases[$A])) {
                    $on = $condAB($A, $B);
                    if ($on) {
                        $sql .= " LEFT JOIN {$A} {$this->tableAliases[$A]} ON {$on} ";
                        $presentAliases[$A] = true;
                        continue;
                    }
                }
                // Aún no se puede, dejamos para otra vuelta
                $next[] = [$A, $B];
            }
            if (count($next) === count($pending))
                break; // no avanzamos
            $pending = $next;
        }

        return $sql;
    }

    // BFS para encontrar un camino tablaOrigen -> tablaDestino
    private function bfsPath(string $src, string $dst): array
    {
        if ($src === $dst)
            return [$src];
        $q = new SplQueue();
        $q->enqueue($src);
        $visited = [$src => true];
        $parent = [];

        while (!$q->isEmpty()) {
            $u = $q->dequeue();
            if (!isset($this->fkGraph[$u]))
                continue;
            foreach ($this->fkGraph[$u] as $v => $_) {
                if (!in_array($v, $this->allowedTables, true))
                    continue;
                if (!isset($visited[$v])) {
                    $visited[$v] = true;
                    $parent[$v] = $u;
                    if ($v === $dst) {
                        // reconstruir camino
                        $path = [$dst];
                        while (end($path) !== $src) {
                            $path[] = $parent[end($path)];
                        }
                        return array_reverse($path);
                    }
                    $q->enqueue($v);
                }
            }
        }
        return []; // sin camino
    }

    public function listPresets(): array
    {
        $sql = "SELECT id, nombre, descripcion, created_at
              FROM reportes_dinamicos_presets
          ORDER BY created_at DESC, nombre ASC";
        $st = $this->conn->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePreset(string $nombre, ?string $descripcion, $config, $createdBy = null): int
    {
        // guardamos el objeto tal cual como JSON
        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        // si ya existe nombre, actualizamos; si no, insertamos
        $sql = "INSERT INTO reportes_dinamicos_presets (nombre, descripcion, config_json, created_by)
            VALUES (:n, :d, :j, :u)
            ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), config_json = VALUES(config_json)";
        $st = $this->conn->prepare($sql);
        $st->execute([
            ':n' => $nombre,
            ':d' => $descripcion,
            ':j' => $json,
            ':u' => $createdBy
        ]);

        // si fue update, recuperamos id
        $id = intval($this->conn->lastInsertId());
        if ($id === 0) {
            $st2 = $this->conn->prepare("SELECT id FROM reportes_dinamicos_presets WHERE nombre = :n");
            $st2->execute([':n' => $nombre]);
            $id = intval($st2->fetchColumn());
        }
        return $id;
    }

    public function getPreset(int $id): array
    {
        $st = $this->conn->prepare("SELECT id, nombre, descripcion, config_json, created_at
                                  FROM reportes_dinamicos_presets
                                 WHERE id = :id");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            throw new RuntimeException('Preset no encontrado');
        $row['config'] = json_decode($row['config_json'], true);
        unset($row['config_json']);
        return $row;
    }

    public function deletePreset(int $id): bool
    {
        $st = $this->conn->prepare("DELETE FROM reportes_dinamicos_presets WHERE id = :id");
        $st->execute([':id' => $id]);
        return $st->rowCount() > 0;
    }

    //Consultas libres tipo SQL

    public function runRawSQL(string $sql, int $limit = 1000): array
    {
        $sql = trim($sql);
        if ($sql === '') {
            throw new InvalidArgumentException('SQL vacío.');
        }
        // Solo una sentencia y que inicie con SELECT
        if (preg_match('/;\s*\S/', $sql)) {
            throw new RuntimeException('Solo se permite una sentencia SQL por vez.');
        }
        if (stripos(ltrim($sql), 'select') !== 0) {
            throw new RuntimeException('Solo se permiten consultas SELECT.');
        }
        // Si no hay LIMIT, añadimos uno
        if (!preg_match('/\blimit\b/i', $sql)) {
            $sql .= ' LIMIT ' . max(1, (int) $limit);
        }

        // Ejecuta con la MISMA conexión
        $stmt = $this->conn->query($sql);

        // Nombres de columnas en el orden real del SELECT
        $colCount = $stmt->columnCount();
        $orderedCols = [];
        for ($i = 0; $i < $colCount; $i++) {
            $meta = $stmt->getColumnMeta($i);
            $name = $meta['name'] ?? ('col' . ($i + 1));
            $orderedCols[] = $name;
        }

        // Filas en ASSOC para que DataTables reciba objetos {col: valor}
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Estructura de columnas para DataTables
        $columns = array_map(function ($c) {
            return ['data' => $c, 'title' => $c];
        }, $orderedCols);

        return [
            'columns' => $columns,
            'rows' => $rows
        ];
    }

    public function listSqlPresets(): array
    {
        $st = $this->conn->query("
        SELECT id, nombre, descripcion, config_json, created_at
        FROM reportes_dinamicos_presets
        ORDER BY created_at DESC, nombre ASC
    ");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $cfg = json_decode($r['config_json'] ?? 'null', true);
            if (isset($cfg['type']) && $cfg['type'] === 'sql') {
                $out[] = [
                    'id' => (int) $r['id'],
                    'nombre' => $r['nombre'],
                    'descripcion' => $r['descripcion'],
                    'created_at' => $r['created_at'],
                ];
            }
        }
        return $out;
    }


}
