<?php
// Configuración de la cabecera para devolver JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// 1. Obtener la consulta (CP o Ciudad) enviada desde el frontend
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Si no hay consulta, devolver un error
if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Consulta (CP/Ciudad) no proporcionada.']);
    exit;
}

// 2. Construir la URL de la API de Nominatim
// Se recomienda añadir el país para mejorar la precisión (ej. 'Mexico')
// La consulta se codifica para URLs (urlencode)
// Nota: Se envía la búsqueda como 'CP o Ciudad, Mexico'
$nominatim_url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($query . ', Mexico') . '&format=json&limit=1&addressdetails=0';

// 3. Configurar y realizar la solicitud HTTP
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $nominatim_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// **MUY IMPORTANTE:** Nominatim requiere un encabezado User-Agent
// Esto identifica tu aplicación y evita que te bloqueen.
curl_setopt($ch, CURLOPT_USERAGENT, 'BuscadorProximidadGeo/1.0 (tu_email@ejemplo.com)'); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 4. Procesar la respuesta
if ($http_code !== 200 || $response === false) {
    echo json_encode(['success' => false, 'error' => 'Error al contactar el servicio de Nominatim. Código HTTP: ' . $http_code]);
    exit;
}

$data = json_decode($response, true);

if (!empty($data)) {
    // Tomamos el primer resultado
    $result = $data[0];
    
    $lat = (float)$result['lat'];
    $lon = (float)$result['lon'];
    
    // Devolvemos las coordenadas y una descripción. Nominatim usa 'display_name'
    echo json_encode([
        'success' => true,
        'coords' => [
            'lat' => $lat,
            'lon' => $lon,
            'name' => $result['display_name'] ?? $query // Usar el nombre de OSM o la query original
        ]
    ]);

} else {
    // No se encontraron resultados
    echo json_encode(['success' => false, 'error' => 'No se encontraron coordenadas para la consulta: ' . $query]);
}
?>
