<?php
// API para llenar Select2 con clientes por nombre.
// Usa UNA coordenada de referencia por cliente para centrar el mapa al seleccionarlo.
// Preferencia: su dirección principal (principal=1) si tiene lat/lon; si no, cualquier dirección con lat/lon.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// --- CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS (AJUSTAR) ---
$servername = "localhost";
$username   = "u111680873_geocode";
$password   = "Geocode861215#-";
$dbname     = "u111680873_geocode";

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 3) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar clientes por nombre y tomar UNA dirección (principal primero) con coordenadas.
    $query = "
        SELECT
            c.id,
            c.nombre,
            cd.latitud,
            cd.longitud,
            cd.codigo_postal
        FROM clientes c
        INNER JOIN cliente_direcciones cd
            ON c.id = cd.id_cliente
        WHERE c.nombre LIKE :term
          AND cd.latitud IS NOT NULL
          AND cd.longitud IS NOT NULL
        ORDER BY cd.principal DESC, cd.id ASC
        LIMIT 10
    ";

    $stmt = $conn->prepare($query);
    $searchTerm = '%' . $term . '%';
    $stmt->bindParam(':term', $searchTerm);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($results as $row) {
        $formatted[] = [
            'id'  => $row['id'],
            'text'=> $row['nombre'] . ' <span style="font-size: 0.8em; color: red;">(CP: ' . $row['codigo_postal'] . ')</span>',
            'lat' => $row['latitud'],
            'lon' => $row['longitud']
        ];
    }

    echo json_encode(['results' => $formatted]);
} catch (PDOException $e) {
    error_log("Error de DB en select2_clientes.php: " . $e->getMessage());
    echo json_encode(['results' => [], 'error' => 'Error de conexión.']);
}
