<?php
// API de Búsqueda de Proximidad de Clientes (todas las direcciones)
// Campos reales usados de cliente_direcciones: id, id_cliente, direccion, ciudad, estado, codigo_postal, latitud, longitud

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// --- CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS (AJUSTAR) ---
$servername = "localhost";
$username   = "u111680873_geocode";
$password   = "Geocode861215#-";
$dbname     = "u111680873_geocode";

// --- OBTENCIÓN DE PARÁMETROS ---
$ref_lat = isset($_GET['ref_lat']) ? (float)$_GET['ref_lat'] : null;
$ref_lon = isset($_GET['ref_lon']) ? (float)$_GET['ref_lon'] : null;
$radio_km = isset($_GET['radio_km']) ? (float)$_GET['radio_km'] : null;
$ref_id  = isset($_GET['ref_id']) && $_GET['ref_id'] !== '' ? (int)$_GET['ref_id'] : null; // id del cliente a excluir (si aplica)

if (!$ref_lat || !$ref_lon || !$radio_km) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros de coordenadas o radio.']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // NOTA: ya NO se filtra por principal=1. Se consideran TODAS las direcciones con lat/lon.
    $query = "
        SELECT
            c.id AS id_cliente,
            c.nombre AS nombre_cliente,
            cd.codigo_postal,
            cd.estado,
            cd.latitud,
            cd.longitud,
            ( 6371 * acos(
                cos( radians(:ref_lat) )
                * cos( radians( cd.latitud ) )
                * cos( radians( cd.longitud ) - radians(:ref_lon) )
                + sin( radians(:ref_lat) )
                * sin( radians( cd.latitud ) )
            ) ) AS distancia_km
        FROM clientes c
        INNER JOIN cliente_direcciones cd ON c.id = cd.id_cliente
        WHERE
            cd.latitud IS NOT NULL AND cd.longitud IS NOT NULL
        HAVING
            distancia_km <= :radio_km
            " . ($ref_id !== null ? " AND c.id != :ref_id " : "") . "
        ORDER BY distancia_km
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':ref_lat',  $ref_lat);
    $stmt->bindParam(':ref_lon',  $ref_lon);
    $stmt->bindParam(':radio_km', $radio_km);
    if ($ref_id !== null) {
        $stmt->bindParam(':ref_id', $ref_id, PDO::PARAM_INT);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
} catch (PDOException $e) {
    error_log("Error de DB en api_proximidad.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión o consulta a la base de datos.']);
}
