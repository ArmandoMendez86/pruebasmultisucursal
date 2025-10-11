<?php
// Devuelve TODAS las direcciones de un cliente (con lat/lon) para pintarlas y elegir el origen.
// Tablas y campos reales: cliente_direcciones(id, id_legado, id_cliente, direccion, ciudad, estado, codigo_postal, principal, latitud, longitud)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "megaparty";

$clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
if ($clienteId <= 0) {
    echo json_encode(['success' => false, 'error' => 'cliente_id requerido']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = "
        SELECT
            cd.id,
            cd.id_cliente,
            c.nombre AS nombre_cliente,
            cd.direccion,
            cd.ciudad,
            cd.estado,
            cd.codigo_postal,
            cd.principal,
            cd.latitud,
            cd.longitud
        FROM cliente_direcciones cd
        INNER JOIN clientes c ON c.id = cd.id_cliente
        WHERE cd.id_cliente = :cliente_id
          AND cd.latitud IS NOT NULL
          AND cd.longitud IS NOT NULL
        ORDER BY cd.principal DESC, cd.id ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':cliente_id' => $clienteId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // Armar una etiqueta legible
    $data = array_map(function($r) {
        $etiqueta = trim(implode(', ', array_filter([
            $r['direccion'],
            $r['ciudad'],
            $r['estado'],
            $r['codigo_postal'] ? 'CP ' . $r['codigo_postal'] : null
        ])));
        return [
            'id'              => (int)$r['id'],
            'id_cliente'      => (int)$r['id_cliente'],
            'nombre_cliente'  => $r['nombre_cliente'],
            'etiqueta'        => $etiqueta !== '' ? $etiqueta : 'Dirección',
            'lat'             => (float)$r['latitud'],
            'lon'             => (float)$r['longitud'],
            'principal'       => (int)$r['principal'] === 1
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("api_direcciones_cliente.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión o consulta.']);
}
