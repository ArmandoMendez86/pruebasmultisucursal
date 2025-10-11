<?php
// Archivo: /app/models/Cliente.php

require_once __DIR__ . '/../../config/Database.php';

class Cliente
{
    private $conn;
    private $table_name = "clientes";
    private $address_table = "cliente_direcciones";
    private $special_prices_table = "cliente_precios_especiales";
    private $payments_table = "pagos_clientes";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getClientsForDataTable($request)
    {
        // JOIN a cliente_tipo con alias explícitos
        $baseQuery = " FROM " . $this->table_name . " c
                   LEFT JOIN cliente_tipo ct ON ct.id = c.id_tipo";

        // columnas para ordenar (todas con alias de tabla)
        $columns = ['c.nombre', 'c.telefono', 'c.email', 'ct.nombre', 'c.deuda_actual'];

        // búsqueda global
        $searchQuery = "";
        if (!empty($request['search']['value'])) {
            $searchValue = $request['search']['value'];
            $searchQuery = " WHERE (c.nombre LIKE :search_value
                             OR c.telefono LIKE :search_value
                             OR c.email LIKE :search_value
                             OR ct.nombre LIKE :search_value)";
        }

        // conteos (OJO: COUNT(c.id) para evitar ambigüedad)
        $stmtTotal = $this->conn->prepare("SELECT COUNT(c.id) AS total " . $baseQuery);
        $stmtTotal->execute();
        $recordsTotal = $stmtTotal->fetchColumn();

        $stmtFiltered = $this->conn->prepare("SELECT COUNT(c.id) AS total " . $baseQuery . $searchQuery);
        if (!empty($searchQuery)) {
            $stmtFiltered->bindValue(':search_value', '%' . $searchValue . '%', PDO::PARAM_STR);
        }
        $stmtFiltered->execute();
        $recordsFiltered = $stmtFiltered->fetchColumn();

        // ordenamiento
        if (isset($request['order']) && count($request['order'])) {
            $orderColumnIndex = (int) $request['order'][0]['column'];
            $orderColumnName = $columns[$orderColumnIndex] ?? 'c.nombre';
            $orderDir = (strtolower($request['order'][0]['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
            $orderQuery = " ORDER BY {$orderColumnName} {$orderDir}";
        } else {
            $orderQuery = " ORDER BY c.nombre ASC";
        }

        // paginación
        $limitQuery = "";
        if (isset($request['length']) && $request['length'] != -1) {
            $limitQuery = " LIMIT :limit OFFSET :offset";
        }

        // SELECT con alias y el nombre del tipo
        $query = "SELECT 
                c.id AS id,
                c.nombre,
                c.telefono,
                c.email,
                COALESCE(ct.nombre, 'Público en General') AS tipo,
                c.deuda_actual
              " . $baseQuery . $searchQuery . $orderQuery . $limitQuery;

        $stmtData = $this->conn->prepare($query);
        if (!empty($searchQuery)) {
            $stmtData->bindValue(':search_value', '%' . $searchValue . '%', PDO::PARAM_STR);
        }
        if (!empty($limitQuery)) {
            $stmtData->bindValue(':limit', (int) $request['length'], PDO::PARAM_INT);
            $stmtData->bindValue(':offset', (int) $request['start'], PDO::PARAM_INT);
        }
        $stmtData->execute();
        $clients = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        // armado de filas
        $data = [];
        foreach ($clients as $client) {
            $deuda = (float) ($client['deuda_actual'] ?? 0);
            $hasDebt = $deuda > 0;

            $acciones = '';
            if ($hasDebt) {
                $acciones .= '<button class="payment-btn text-green-400 hover:text-green-300 mr-3" title="Registrar Abono"><i class="fas fa-dollar-sign"></i></button>';
            }
            $acciones .= '<button class="edit-btn text-blue-400 hover:text-blue-300 mr-3" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
            $acciones .= '<button class="delete-btn text-red-500 hover:text-red-400" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';

            $data[] = [
                "id" => $client['id'],
                "nombre" => htmlspecialchars($client['nombre']),
                "telefono" => htmlspecialchars($client['telefono'] ?? 'N/A'),
                "email" => htmlspecialchars($client['email'] ?? 'N/A'),
                "tipo" => htmlspecialchars($client['tipo'] ?? 'Público en General'),
                "deuda_actual" => $deuda, // crudo para que DataTables/JS lo formatee
                "acciones" => $acciones
            ];
        }

        return [
            "draw" => (int) ($request['draw'] ?? 0),
            "recordsTotal" => (int) $recordsTotal,
            "recordsFiltered" => (int) $recordsFiltered,
            "data" => $data
        ];
    }


    public function getAll()
    {
        $query = "SELECT id, nombre, telefono, email, deuda_actual FROM " . $this->table_name . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $cliente['direcciones'] = $this->getDirecciones($id);
            $cliente['precios_especiales'] = $this->getPreciosEspeciales($id);
        }
        return $cliente;
    }

    public function create($data)
    {
        $this->conn->beginTransaction();
        try {

            //Consulta modificada para tipo cliente
            $query_cliente = "INSERT INTO {$this->table_name}
            (nombre, rfc, telefono, email, id_tipo, tiene_credito, limite_credito, obs_envio)
            VALUES (:nombre, :rfc, :telefono, :email, :id_tipo, :tiene_credito, :limite_credito, :obs_envio)";
            $stmt_cliente = $this->conn->prepare($query_cliente);
            $stmt_cliente->bindParam(':nombre', $data['nombre']);
            $stmt_cliente->bindParam(':rfc', $data['rfc']);
            $stmt_cliente->bindParam(':telefono', $data['telefono']);
            $stmt_cliente->bindParam(':email', $data['email']);
            $idTipo = (isset($data['id_tipo']) && ctype_digit((string) $data['id_tipo'])) ? (int) $data['id_tipo'] : 1;
            $stmt_cliente->bindParam(':id_tipo', $idTipo, PDO::PARAM_INT);
            $stmt_cliente->bindParam(':tiene_credito', $data['tiene_credito'], PDO::PARAM_BOOL);
            $stmt_cliente->bindParam(':limite_credito', $data['limite_credito']);
            $obs = isset($data['obs_envio']) ? trim($data['obs_envio']) : null;
            $stmt_cliente->bindValue(':obs_envio', $obs === '' ? null : $obs, $obs === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_cliente->execute();
            $idCliente = $this->conn->lastInsertId();
            if (isset($data['direcciones']) && is_array($data['direcciones'])) {
                $this->guardarDirecciones($idCliente, $data['direcciones']);
            }
            if (isset($data['precios']) && is_array($data['precios'])) {
                $this->guardarPreciosEspeciales($idCliente, $data['precios']);
            }
            $this->conn->commit();
            return $idCliente;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function update($id, $data)
    {
        $this->conn->beginTransaction();
        try {
            $query_cliente = "UPDATE {$this->table_name} SET
            nombre = :nombre,
            rfc = :rfc,
            telefono = :telefono,
            email = :email,
            id_tipo = :id_tipo,
            tiene_credito = :tiene_credito,
            limite_credito = :limite_credito,
            obs_envio = :obs_envio
            WHERE id = :id";
            $stmt_cliente = $this->conn->prepare($query_cliente);
            $stmt_cliente->bindParam(':id', $id);
            $stmt_cliente->bindParam(':nombre', $data['nombre']);
            $stmt_cliente->bindParam(':rfc', $data['rfc']);
            $stmt_cliente->bindParam(':telefono', $data['telefono']);
            $stmt_cliente->bindParam(':email', $data['email']);
            $idTipo = (isset($data['id_tipo']) && ctype_digit((string) $data['id_tipo'])) ? (int) $data['id_tipo'] : 1;
            $stmt_cliente->bindParam(':id_tipo', $idTipo, PDO::PARAM_INT);
            $stmt_cliente->bindParam(':tiene_credito', $data['tiene_credito'], PDO::PARAM_BOOL);
            $stmt_cliente->bindParam(':limite_credito', $data['limite_credito']);
            $obs = isset($data['obs_envio']) ? trim($data['obs_envio']) : null;
            $stmt_cliente->bindValue(':obs_envio', $obs === '' ? null : $obs, $obs === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt_cliente->execute();
            if (isset($data['direcciones']) && is_array($data['direcciones'])) {
                $this->guardarDirecciones($id, $data['direcciones']);
            }
            if (isset($data['precios']) && is_array($data['precios'])) {
                $this->guardarPreciosEspeciales($id, $data['precios']);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function search($term)
    {
        $query = "SELECT id, nombre, rfc, telefono FROM " . $this->table_name . " WHERE nombre LIKE :term OR rfc LIKE :term OR telefono LIKE :term LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $term . "%";
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarAbono($idCliente, $monto, $metodoPago, $idUsuario, $detalle = '', $fechaRecibido = '')
    {
        $cliente = $this->getById($idCliente);
        if (!$cliente) {
            throw new Exception("Cliente no encontrado.");
        }

        /*  $stLed = $this->conn->prepare("
         SELECT COALESCE(SUM(saldo_pendiente),0)
         FROM ventas_credito
         WHERE id_cliente = :idc AND saldo_pendiente > 0
     ");
         $stLed->bindValue(':idc', $idCliente, PDO::PARAM_INT);
         $stLed->execute();
         $deudaLedger = (float) $stLed->fetchColumn();

         if ($monto > $deudaLedger) {
             throw new Exception("El monto del abono no puede ser mayor a la deuda actual de $" . number_format($deudaLedger, 2));
         } */

        // 2) Valida contra la tabla clientes.deuda_actual (lo que el cliente manipula)
        $stTab = $this->conn->prepare("
        SELECT COALESCE(deuda_actual,0)
        FROM clientes
        WHERE id = :idc
        ");
        $stTab->bindValue(':idc', $idCliente, PDO::PARAM_INT);
        $stTab->execute();
        $deudaTabla = (float) $stTab->fetchColumn();

        if ($monto > $deudaTabla) {
            throw new Exception(
                "El monto del abono no puede ser mayor a la deuda actual de $" . number_format($deudaTabla, 2)
            );
        }


        // 3) Normaliza detalle y fecha
        $detalle = trim((string) $detalle);
        $detalleParam = (strlen($detalle) === 0) ? null : $detalle;

        if (!is_string($fechaRecibido) || $fechaRecibido === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRecibido)) {
            $fechaRecibido = date('Y-m-d');
        }

        // 4) Transacción: SOLO insertamos el pago (NO tocamos clientes.deuda_actual)
        $this->conn->beginTransaction();
        try {
            $query_insert = "INSERT INTO " . $this->payments_table . " 
            (id_cliente, id_usuario, monto, metodo_pago, detalle, fecha_recibido) 
            VALUES (:id_cliente, :id_usuario, :monto, :metodo_pago, :detalle, :fecha_recibido)";
            $stmt_insert = $this->conn->prepare($query_insert);
            $stmt_insert->bindParam(':id_cliente', $idCliente);
            $stmt_insert->bindParam(':id_usuario', $idUsuario);
            $stmt_insert->bindParam(':monto', $monto);
            $stmt_insert->bindParam(':metodo_pago', $metodoPago);
            $stmt_insert->bindParam(':detalle', $detalleParam);       // puede ser NULL
            $stmt_insert->bindParam(':fecha_recibido', $fechaRecibido);
            $stmt_insert->execute();

            // El trigger AFTER INSERT se encarga de aplicar FIFO y ajustar la deuda en clientes.
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }



    private function getPreciosEspeciales($id_cliente)
    {
        // --- MODIFICACIÓN CLAVE ---
        // Se une con la tabla de productos para obtener todos los datos necesarios.
        $query = "SELECT 
                    pe.id_producto, 
                    pe.precio_especial,
                    p.nombre,
                    p.sku,
                    p.precio_menudeo
                  FROM " . $this->special_prices_table . " pe
                  JOIN productos p ON pe.id_producto = p.id
                  WHERE pe.id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        // Devuelve un array de objetos, cada uno con los detalles del producto y su precio especial.
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function guardarPreciosEspeciales($id_cliente, $precios)
    {
        $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente");
        $stmt_delete->bindParam(':id_cliente', $id_cliente);
        $stmt_delete->execute();
        $query = "INSERT INTO " . $this->special_prices_table . " (id_cliente, id_producto, precio_especial) VALUES (:id_cliente, :id_producto, :precio_especial)";
        $stmt_insert = $this->conn->prepare($query);
        foreach ($precios as $id_producto => $precio) {
            $precio_limpio = filter_var($precio, FILTER_VALIDATE_FLOAT);
            if ($precio_limpio !== false && $precio_limpio > 0) {
                $stmt_insert->bindParam(':id_cliente', $id_cliente);
                $stmt_insert->bindParam(':id_producto', $id_producto);
                $stmt_insert->bindParam(':precio_especial', $precio_limpio);
                $stmt_insert->execute();
            }
        }
    }

    private function getDirecciones($id_cliente)
    {
        $query = "SELECT * FROM " . $this->address_table . " WHERE id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function guardarDirecciones($id_cliente, $direcciones)
    {
        $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->address_table . " WHERE id_cliente = :id_cliente");
        $stmt_delete->bindParam(':id_cliente', $id_cliente);
        $stmt_delete->execute();
        $query = "INSERT INTO " . $this->address_table . " (id_cliente, direccion, ciudad, estado, codigo_postal, principal) VALUES (:id_cliente, :direccion, :ciudad, :estado, :codigo_postal, :principal)";
        $stmt_insert = $this->conn->prepare($query);
        foreach ($direcciones as $dir) {
            if (!empty($dir['direccion'])) {
                $stmt_insert->bindParam(':id_cliente', $id_cliente);
                $stmt_insert->bindParam(':direccion', $dir['direccion']);
                $stmt_insert->bindParam(':ciudad', $dir['ciudad']);
                $stmt_insert->bindParam(':estado', $dir['estado']);
                $stmt_insert->bindParam(':codigo_postal', $dir['codigo_postal']);
                $stmt_insert->bindParam(':principal', $dir['principal'], PDO::PARAM_BOOL);
                $stmt_insert->execute();
            }
        }
    }

    public function setSpecialPrice($id_cliente, $id_producto, $precio_especial)
    {
        $query = "INSERT INTO cliente_precios_especiales (id_cliente, id_producto, precio_especial) VALUES (:id_cliente, :id_producto, :precio_especial) ON DUPLICATE KEY UPDATE precio_especial = VALUES(precio_especial)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':precio_especial', $precio_especial, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function updateClientCredit($id_cliente, $amount)
    {
        $query = "UPDATE " . $this->table_name . " SET limite_credito = limite_credito - :amount, deuda_actual = deuda_actual + :amount WHERE id = :id_cliente AND tiene_credito = 1 AND (limite_credito - deuda_actual) >= :amount";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    public function decreaseClientCredit($id_cliente, $amount)
    {
        $query = "UPDATE " . $this->table_name . " SET deuda_actual = deuda_actual - :amount WHERE id = :id_cliente AND tiene_credito = 1 AND deuda_actual >= :amount";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    // ================== DataTables Server-Side ==================
    public function sspVentasCredito(array $p): array
    {
        $draw = (int) ($p['draw'] ?? 0);
        $start = (int) ($p['start'] ?? 0);
        $length = (int) ($p['length'] ?? 10);
        $search = trim((string) ($p['search'] ?? ''));
        $orderBy = $p['orderBy'] ?? 'v.fecha';
        $orderDir = ($p['orderDir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $soloPendientes = !empty($p['soloPendientes']);

        // WHERE base
        $wheres = [];
        $params = [];

        if ($soloPendientes) {
            $wheres[] = "vc.estatus IN ('Abierta','Parcial')";
        }

        if ($search !== '') {
            // búsqueda en cliente, id venta, estatus y fecha
            $wheres[] = "(c.nombre LIKE :q OR v.id = :qId OR vc.estatus LIKE :q OR DATE(v.fecha) LIKE :q)";
            $params[':q'] = "%{$search}%";
            $params[':qId'] = (ctype_digit($search) ? (int) $search : -1);
        }

        $whereSql = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

        // Conteo total (sin filtros)
        $sqlTotal = "SELECT COUNT(*) 
                 FROM ventas_credito vc 
                 JOIN ventas v   ON v.id = vc.id_venta
                 JOIN clientes c ON c.id = vc.id_cliente";
        $total = (int) $this->conn->query($sqlTotal)->fetchColumn();

        // Conteo filtrado
        $sqlFiltered = "SELECT COUNT(*)
                    FROM ventas_credito vc
                    JOIN ventas v   ON v.id = vc.id_venta
                    JOIN clientes c ON c.id = vc.id_cliente
                    $whereSql";
        $stF = $this->conn->prepare($sqlFiltered);
        foreach ($params as $k => $v)
            $stF->bindValue($k, $v);
        $stF->execute();
        $filtered = (int) $stF->fetchColumn();


        // === Total de SALDO con los mismos filtros (para mostrar al filtrar) ===
        $sqlSum = "SELECT COALESCE(SUM(vc.saldo_pendiente),0)
           FROM ventas_credito vc
           JOIN ventas   v ON v.id = vc.id_venta
           JOIN clientes c ON c.id = vc.id_cliente
           $whereSql";
        $stS = $this->conn->prepare($sqlSum);
        foreach ($params as $k => $v)
            $stS->bindValue($k, $v);
        $stS->execute();
        $sumSaldoFiltrado = (float) $stS->fetchColumn();


        // Dataset paginado
        $sql = "
        SELECT
            c.id                               AS id_cliente,
            c.nombre                           AS cliente,
            v.id                               AS venta_id,
            DATE(v.fecha)                      AS fecha_venta,
            vc.monto_credito,
            COALESCE(pa.total_abonado, 0)      AS abonado_total,
            vc.saldo_pendiente,
            /* <<< Calculado en tiempo real >>> */
            CASE
            WHEN ROUND(vc.saldo_pendiente,2) <= 0 THEN 'Cerrada'
            WHEN ROUND(vc.saldo_pendiente,2)  < vc.monto_credito THEN 'Parcial'
            ELSE 'Abierta'
            END                                AS estatus_calc,
            COALESCE(det.num_items, 0)         AS num_items
        FROM ventas_credito vc
        JOIN ventas   v  ON v.id = vc.id_venta
        JOIN clientes c  ON c.id = vc.id_cliente
        LEFT JOIN (
            SELECT a.id_venta, SUM(a.monto_aplicado) AS total_abonado
            FROM aplicaciones_pagos_credito a
            GROUP BY a.id_venta
        ) pa ON pa.id_venta = v.id
        LEFT JOIN (
            SELECT vd.id_venta, SUM(vd.cantidad) AS num_items
            FROM venta_detalles vd
            GROUP BY vd.id_venta
        ) det ON det.id_venta = v.id
        $whereSql
        ORDER BY $orderBy $orderDir
        LIMIT :limit OFFSET :offset
        ";

        $st = $this->conn->prepare($sql);
        foreach ($params as $k => $v)
            $st->bindValue($k, $v);
        $st->bindValue(':limit', $length, PDO::PARAM_INT);
        $st->bindValue(':offset', $start, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
            // === NUEVO ===
            'sumSaldoFiltered' => $sumSaldoFiltrado
        ];
    }

    // ============ Detalle de productos por venta (modal) ============
    public function getDetalleProductosPorVenta(int $ventaId): array
    {
        $sql = "SELECT 
              p.nombre            AS producto,
              vd.cantidad         AS cantidad,
              vd.precio_unitario  AS precio_unitario,
              vd.subtotal         AS importe
            FROM venta_detalles vd
            JOIN productos p ON p.id = vd.id_producto
            WHERE vd.id_venta = :venta
            ORDER BY vd.id";
        $st = $this->conn->prepare($sql);
        $st->bindValue(':venta', $ventaId, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sspPagosAplicados(array $p): array
    {
        try {
            $this->conn->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        } catch (\Throwable $e) {
        }

        $draw = (int) ($p['draw'] ?? 0);
        $start = (int) ($p['start'] ?? 0);
        $length = (int) ($p['length'] ?? 10);
        $search = trim((string) ($p['search'] ?? ''));
        $orderBy = $p['orderBy'] ?? 't.fecha_recibido';
        $orderDir = (($p['orderDir'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';

        if (strpos($orderBy, 't.') !== 0) {
            $orderBy = 't.fecha_recibido';
        }

        $idCliente = (int) ($p['idCliente'] ?? 0);
        $desde = $p['desde'] ?? null;
        $hasta = $p['hasta'] ?? null;

        $w = [];
        $pr = [];

        if ($idCliente > 0) {
            $w[] = 'pc.id_cliente = :idc';
            $pr[':idc'] = $idCliente;
        }
        if ($desde) {
            $w[] = 'pc.fecha_recibido >= :desde';
            $pr[':desde'] = $desde;
        }
        if ($hasta) {
            $w[] = 'pc.fecha_recibido <= :hasta';
            $pr[':hasta'] = $hasta;
        }

        if ($search !== '') {
            $w[] = '(c.nombre LIKE :q
              OR u.nombre LIKE :q
              OR pc.metodo_pago LIKE :q
              OR pc.detalle LIKE :q
              OR v.id = :qNum
              OR pc.id = :qNum
              OR DATE(pc.fecha_recibido) LIKE :q)';
            $pr[':q'] = "%{$search}%";
            $pr[':qNum'] = ctype_digit($search) ? (int) $search : -1;
        }
        $whereSql = $w ? ('WHERE ' . implode(' AND ', $w)) : '';

        $sqlTotal = "SELECT COUNT(*)
                  FROM aplicaciones_pagos_credito apc
                  JOIN pagos_clientes pc ON pc.id = apc.id_pago
                  JOIN ventas v          ON v.id = apc.id_venta
                  JOIN ventas_credito vc ON vc.id_venta = v.id
                  JOIN clientes c        ON c.id = pc.id_cliente
                  JOIN usuarios u        ON u.id = pc.id_usuario";
        $total = (int) $this->conn->query($sqlTotal)->fetchColumn();

        $sqlFiltered = "SELECT COUNT(*)
                     FROM aplicaciones_pagos_credito apc
                     JOIN pagos_clientes pc ON pc.id = apc.id_pago
                     JOIN ventas v          ON v.id = apc.id_venta
                     JOIN ventas_credito vc ON vc.id_venta = v.id
                     JOIN clientes c        ON c.id = pc.id_cliente
                     JOIN usuarios u        ON u.id = pc.id_usuario
                     $whereSql";
        $stF = $this->conn->prepare($sqlFiltered);
        foreach ($pr as $k => $v)
            $stF->bindValue($k, $v);
        $stF->execute();
        $filtered = (int) $stF->fetchColumn();

        $sql = "
         SELECT *
         FROM (
             SELECT
               pc.id              AS nro_pago,
               c.nombre           AS cliente,
               u.nombre           AS usuario_registro,
               pc.fecha_recibido  AS fecha_recibido,
               pc.monto           AS monto_pago,
               pc.metodo_pago     AS metodo_pago,
               pc.detalle         AS detalle,
               v.id               AS venta_id,
               v.fecha            AS fecha_venta,
               apc.monto_aplicado AS aplicado_en_esta_venta,

               vc.monto_credito
                 - COALESCE(
                     SUM(apc.monto_aplicado) OVER (
                       PARTITION BY apc.id_venta
                       ORDER BY pc.fecha_recibido, apc.id
                       ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
                     ),
                     0
                   ) AS monto_credito_venta,


               vc.saldo_pendiente AS saldo_pendiente_actual,
               pc.fecha           AS fecha_sistema
             FROM aplicaciones_pagos_credito apc
             JOIN pagos_clientes pc ON pc.id = apc.id_pago
             JOIN ventas v          ON v.id = apc.id_venta
             JOIN ventas_credito vc ON vc.id_venta = v.id
             JOIN clientes c        ON c.id = pc.id_cliente
             JOIN usuarios u        ON u.id = pc.id_usuario
             $whereSql
         ) AS t
         ORDER BY $orderBy $orderDir
         LIMIT :limit OFFSET :offset
         ";

        $st = $this->conn->prepare($sql);
        foreach ($pr as $k => $v)
            $st->bindValue($k, $v);
        $st->bindValue(':limit', $length, \PDO::PARAM_INT);
        $st->bindValue(':offset', $start, \PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows
        ];
    }



    //Tipos de clientes
    public function getTiposCliente()
    {
        $stmt = $this->conn->query("SELECT id, nombre FROM cliente_tipo ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



}
