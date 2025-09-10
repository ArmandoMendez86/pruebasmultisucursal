<?php
// Archivo: /app/models/Reporte.php

require_once __DIR__ . '/../../config/Database.php';

class Reporte
{
    private $conn;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getGlobalVentasServerSide($params)
    {
        $columns = ['v.fecha', 'v.id', 's.nombre', 'c.nombre', 'u.nombre', 'v.total', 'v.estado'];
        $orderBy = $columns[$params['order'][0]['column']] ?? 'v.fecha';
        $orderDir = strtoupper($params['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

        $where = "";
        $queryParams = [];

        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $where .= " WHERE v.fecha BETWEEN :startDate AND :endDate ";
            $queryParams[':startDate'] = $params['startDate'] . " 00:00:00";
            $queryParams[':endDate']   = $params['endDate']   . " 23:59:59";
        }

        $base = " FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  JOIN usuarios u ON v.id_usuario = u.id
                  JOIN sucursales s ON v.id_sucursal = s.id ";

        $sqlTotal = "SELECT COUNT(v.id) as cnt " . $base;
        $stmtTotal = $this->conn->prepare($sqlTotal);
        $stmtTotal->execute();
        $recordsTotal = (int)($stmtTotal->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

        $whereSearch = $where;
        $paramsSearch = $queryParams;
        if (!empty($params['search']['value'])) {
            $searchValue = '%' . $params['search']['value'] . '%';
            $whereSearch .= ($where ? " AND " : " WHERE ") . "(c.nombre LIKE :search OR u.nombre LIKE :search OR s.nombre LIKE :search OR v.id LIKE :search OR v.estado LIKE :search)";
            $paramsSearch[':search'] = $searchValue;
        }

        $sqlFiltered = "SELECT COUNT(v.id) as cnt " . $base . $whereSearch;
        $stmtFiltered = $this->conn->prepare($sqlFiltered);
        foreach ($paramsSearch as $key => $val) {
            $stmtFiltered->bindValue($key, $val);
        }
        $stmtFiltered->execute();
        $recordsFiltered = (int)($stmtFiltered->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

        $sqlData = "SELECT v.id, v.fecha, v.total, v.estado, c.nombre as cliente_nombre, u.nombre as usuario_nombre, s.nombre as sucursal_nombre "
            . $base . $whereSearch
            . " ORDER BY $orderBy $orderDir LIMIT :start, :length";

        $stmtData = $this->conn->prepare($sqlData);
        foreach ($paramsSearch as $key => $val) {
            $stmtData->bindValue($key, $val);
        }
        $stmtData->bindValue(':start', intval($params['start']), PDO::PARAM_INT);
        $stmtData->bindValue(':length', intval($params['length']), PDO::PARAM_INT);
        $stmtData->execute();
        $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $acciones = '<button class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1 px-3 rounded-lg text-xs view-pdf-btn" data-id="' . htmlspecialchars($r['id']) . '" title="Ver PDF"><i class="fas fa-file-pdf"></i></button>';
            $data[] = [
                'fecha' => $r['fecha'],
                'id' => $r['id'],
                'sucursal_nombre' => $r['sucursal_nombre'],
                'cliente_nombre' => $r['cliente_nombre'],
                'usuario_nombre' => $r['usuario_nombre'],
                'total' => $r['total'],
                'estado' => $r['estado'],
                'acciones' => $acciones
            ];
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getVentasPorFecha($id_sucursal, $fecha_inicio, $fecha_fin, $id_vendedor = null)
    {
        $fecha_fin_completa = $fecha_fin . ' 23:59:59';
        $query = "SELECT v.id, v.fecha, v.total, v.estado, c.nombre as cliente_nombre, u.nombre as usuario_nombre
                  FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  JOIN usuarios u ON v.id_usuario = u.id
                  WHERE v.id_sucursal = :id_sucursal 
                    AND v.fecha BETWEEN :fecha_inicio AND :fecha_fin_completa";

        if ($id_vendedor !== null) {
            $query .= " AND v.id_usuario = :id_vendedor";
        }

        $query .= " ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin_completa', $fecha_fin_completa);
        if ($id_vendedor !== null) {
            $stmt->bindParam(':id_vendedor', $id_vendedor);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCorteDeCaja($id_sucursal, $fecha, $id_usuario = null)
    {
        $fecha_inicio_completa = $fecha . ' 00:00:00';
        $fecha_fin_completa = $fecha . ' 23:59:59';
        $resultado = [
            'total_ventas' => 0,
            'ventas_efectivo' => 0,
            'ventas_tarjeta' => 0,
            'ventas_transferencia' => 0,
            'ventas_credito' => 0,
            'total_gastos' => 0,
            'abonos_clientes' => 0
        ];

        $query_ventas = "SELECT total, metodo_pago FROM ventas WHERE id_sucursal = :id_sucursal AND fecha BETWEEN :fecha_inicio AND :fecha_fin AND estado = 'Completada'";
        if ($id_usuario !== null)
            $query_ventas .= " AND id_usuario = :id_usuario";

        $stmt_ventas = $this->conn->prepare($query_ventas);
        $stmt_ventas->bindParam(':id_sucursal', $id_sucursal);
        $stmt_ventas->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt_ventas->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt_ventas->bindParam(':id_usuario', $id_usuario);
        $stmt_ventas->execute();

        while ($row = $stmt_ventas->fetch(PDO::FETCH_ASSOC)) {
            $sale_total = floatval($row['total']);
            $resultado['total_ventas'] += $sale_total;

            $metodos_pago = json_decode($row['metodo_pago'], true);

            if (is_array($metodos_pago)) {
                // CASO ESPECIAL: Si solo hay un pago y es en efectivo, significa que el monto real que ingresó a caja es el total de la venta (el resto fue cambio).
                if (count($metodos_pago) === 1 && $metodos_pago[0]['method'] === 'Efectivo') {
                    $resultado['ventas_efectivo'] += $sale_total;
                } else {
                    // CASO NORMAL: Múltiples pagos o pagos que no son solo efectivo. Aquí los montos son exactos.
                    foreach ($metodos_pago as $pago) {
                        if (isset($pago['method']) && isset($pago['amount'])) {
                            $monto_pago = floatval($pago['amount']);
                            switch ($pago['method']) {
                                case 'Efectivo':
                                    $resultado['ventas_efectivo'] += $monto_pago;
                                    break;
                                case 'Tarjeta':
                                    $resultado['ventas_tarjeta'] += $monto_pago;
                                    break;
                                case 'Transferencia':
                                    $resultado['ventas_transferencia'] += $monto_pago;
                                    break;
                                case 'Crédito':
                                    $resultado['ventas_credito'] += $sale_total;
                                    break;
                            }
                        }
                    }
                }
            } elseif (is_string($row['metodo_pago'])) { // Lógica de respaldo para formato antiguo
                switch ($row['metodo_pago']) {
                    case 'Efectivo':
                        $resultado['ventas_efectivo'] += $sale_total;
                        break;
                    case 'Tarjeta':
                        $resultado['ventas_tarjeta'] += $sale_total;
                        break;
                    case 'Transferencia':
                        $resultado['ventas_transferencia'] += $sale_total;
                        break;
                    case 'Crédito':
                        $resultado['ventas_credito'] += $sale_total;
                        break;
                }
            }
        }

        // Gastos
        $query_gastos = "SELECT SUM(monto) as total_gastos FROM gastos WHERE id_sucursal = :id_sucursal AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
        if ($id_usuario !== null)
            $query_gastos .= " AND id_usuario = :id_usuario";
        $stmt_gastos = $this->conn->prepare($query_gastos);
        $stmt_gastos->bindParam(':id_sucursal', $id_sucursal);
        $stmt_gastos->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt_gastos->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt_gastos->bindParam(':id_usuario', $id_usuario);
        $stmt_gastos->execute();
        $gastos_result = $stmt_gastos->fetch(PDO::FETCH_ASSOC);
        if ($gastos_result && $gastos_result['total_gastos']) {
            $resultado['total_gastos'] = $gastos_result['total_gastos'];
        }

        // Abonos de Clientes
        $query_abonos = "SELECT SUM(pc.monto) as total_abonos FROM pagos_clientes pc JOIN usuarios u ON pc.id_usuario = u.id WHERE u.id_sucursal = :id_sucursal AND pc.fecha BETWEEN :fecha_inicio AND :fecha_fin AND pc.metodo_pago IN ('Efectivo', 'Transferencia')";
        if ($id_usuario !== null)
            $query_abonos .= " AND pc.id_usuario = :id_usuario";
        $stmt_abonos = $this->conn->prepare($query_abonos);
        $stmt_abonos->bindParam(':id_sucursal', $id_sucursal);
        $stmt_abonos->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt_abonos->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt_abonos->bindParam(':id_usuario', $id_usuario);
        $stmt_abonos->execute();
        $abonos_result = $stmt_abonos->fetch(PDO::FETCH_ASSOC);
        if ($abonos_result && $abonos_result['total_abonos']) {
            $resultado['abonos_clientes'] = $abonos_result['total_abonos'];
        }

        return $resultado;
    }

    public function getGastosDetallados($id_sucursal, $fecha, $id_usuario = null)
    {
        $fecha_inicio_completa = $fecha . ' 00:00:00';
        $fecha_fin_completa = $fecha . ' 23:59:59';
        $query = "SELECT id, fecha, categoria_gasto, descripcion, monto 
                  FROM gastos WHERE id_sucursal = :id_sucursal AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
        if ($id_usuario !== null)
            $query .= " AND id_usuario = :id_usuario";
        $query .= " ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAbonosDetallados($id_sucursal, $fecha, $id_usuario = null)
    {
        $fecha_inicio_completa = $fecha . ' 00:00:00';
        $fecha_fin_completa = $fecha . ' 23:59:59';
        $query = "SELECT pc.id, pc.fecha, pc.monto, pc.metodo_pago, c.nombre as cliente_nombre, u.nombre as usuario_nombre
                  FROM pagos_clientes pc JOIN clientes c ON pc.id_cliente = c.id JOIN usuarios u ON pc.id_usuario = u.id
                  WHERE u.id_sucursal = :id_sucursal AND pc.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  AND pc.metodo_pago IN ('Efectivo', 'Transferencia')";
        if ($id_usuario !== null)
            $query .= " AND pc.id_usuario = :id_usuario";
        $query .= " ORDER BY pc.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVentasGlobales()
    {
        $query = "SELECT 
                    v.id, v.fecha, v.total, v.estado, 
                    c.nombre as cliente_nombre, 
                    u.nombre as usuario_nombre,
                    s.nombre as sucursal_nombre
                  FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  JOIN usuarios u ON v.id_usuario = u.id
                  JOIN sucursales s ON v.id_sucursal = s.id
                  ORDER BY v.fecha DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getSalesReportPaginated($id_sucursal, $startDate, $endDate, $userFilter, $searchValue, $orderColIdx, $orderDir, $start, $length)
    {
        $columns = ['v.fecha', 'v.id', 'c.nombre', 'u.nombre', 'v.total', 'v.estado'];
        $orderBy = $columns[$orderColIdx] ?? 'v.fecha';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $where = " WHERE v.id_sucursal = :id_sucursal ";
        $params = [':id_sucursal' => $id_sucursal];

        if ($startDate !== '' && $endDate !== '') {
            $where .= " AND v.fecha BETWEEN :startDate AND :endDate ";
            $params[':startDate'] = $startDate . " 00:00:00";
            $params[':endDate']   = $endDate   . " 23:59:59";
        }

        if ($userFilter !== '' && $userFilter !== null) {
            $where .= " AND v.id_usuario = :userFilter ";
            $params[':userFilter'] = $userFilter;
        }

        $base = " FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  JOIN usuarios u ON v.id_usuario = u.id ";

        $sqlTotal = "SELECT COUNT(*) as cnt " . $base . $where;
        $stmt = $this->conn->prepare($sqlTotal);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $recordsTotal = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

        $recordsFiltered = $recordsTotal;
        $whereSearch = $where;
        $paramsSearch = $params;
        if ($searchValue !== '') {
            $whereSearch .= " AND (c.nombre LIKE :search OR u.nombre LIKE :search OR v.id LIKE :search OR v.estado LIKE :search) ";
            $paramsSearch[':search'] = '%' . $searchValue . '%';
            $sqlCount = "SELECT COUNT(*) as cnt " . $base . $whereSearch;
            $stmt2 = $this->conn->prepare($sqlCount);
            foreach ($paramsSearch as $k => $v) $stmt2->bindValue($k, $v);
            $stmt2->execute();
            $recordsFiltered = (int)($stmt2->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
        }

        $sqlData = "SELECT v.id, v.fecha, v.total, v.estado, c.nombre as cliente, u.nombre as usuario "
            . $base . $whereSearch
            . " ORDER BY $orderBy $orderDir LIMIT :start, :length";

        $stmt3 = $this->conn->prepare($sqlData);
        foreach ($paramsSearch as $k => $v) $stmt3->bindValue($k, $v);
        $stmt3->bindValue(':start', intval($start), PDO::PARAM_INT);
        $stmt3->bindValue(':length', intval($length), PDO::PARAM_INT);
        $stmt3->execute();
        $rows = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $acciones = '';
            $estado = $r['estado'] ?? 'Completada';
            if ($estado === 'Completada' || $estado === 'Pendiente') {
                $acciones = '<div class="flex items-center space-x-2">'
                    . '<button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded-lg text-xs print-ticket-win-btn" data-id="' . htmlspecialchars($r['id']) . '" title="Imprimir (Windows)"><i class="fas fa-print"></i></button>'
                    . '<button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded-lg text-xs print-ticket-btn" data-id="' . htmlspecialchars($r['id']) . '" title="Imprimir Ticket"><i class="fas fa-receipt"></i></button>'
                    . '<button class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1 px-3 rounded-lg text-xs view-pdf-btn" data-id="' . htmlspecialchars($r['id']) . '" title="Ver PDF"><i class="fas fa-file-pdf"></i></button>'
                    . '<button class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded-lg text-xs cancel-sale-btn" data-id="' . htmlspecialchars($r['id']) . '" title="Cancelar Venta"><i class="fas fa-times-circle"></i></button>'
                    . '</div>';
            }elseif ($estado === 'Cancelada') {
                $acciones = '<span class="text-gray-500 text-xs">Cancelada</span>';
            } elseif ($estado === 'Cotizacion') {
                $acciones = '<div class="flex items-center space-x-2">'
                    . '<button class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1 px-3 rounded-lg text-xs view-pdf-btn" data-id="' . htmlspecialchars($r['id']) . '" title="Ver Cotización PDF"><i class="fas fa-file-pdf"></i></button>'
                    . '</div>';
            }

            $data[] = [
                'fecha' => $r['fecha'],
                'id' => $r['id'],
                'cliente' => $r['cliente'],
                'usuario' => $r['usuario'],
                'total' => $r['total'],
                'estado' => $r['estado'],
                'acciones' => $acciones
            ];
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getSucursalById($id_sucursal)
    {
        $query = "SELECT nombre, direccion, telefono FROM sucursales WHERE id = :id_sucursal";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
