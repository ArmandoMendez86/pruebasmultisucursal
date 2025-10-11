<?php
// API para sugerencias de autocompletado de direcciones usando Nominatim.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// 1. Obtener el término de búsqueda
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

// Si el término es muy corto, devolvemos resultados vacíos para evitar sobrecargar la API
if (strlen($term) < 3) {
    echo json_encode(['results' => []]);
    exit;
}

// 2. Construir la URL de la API de Nominatim
// Usamos 'search' con un límite bajo y añadimos el país (México) para mejorar la precisión.
// 'addressdetails=0' mantiene la respuesta concisa.
$nominatim_url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($term . ', Mexico') . '&format=json&limit=5&addressdetails=0';

// 3. Configurar y realizar la solicitud HTTP
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $nominatim_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// **MUY IMPORTANTE:** Nominatim requiere un encabezado User-Agent
curl_setopt($ch, CURLOPT_USERAGENT, 'BuscadorProximidadGeo/1.0 (tu_email@ejemplo.com)'); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 4. Procesar la respuesta
if ($http_code !== 200 || $response === false) {
    error_log("Error de Nominatim: Código HTTP " . $http_code);
    echo json_encode(['results' => []]);
    exit;
}

$data = json_decode($response, true);

// 5. Formatear los resultados para Select2
$formatted_results = [];
if (!empty($data)) {
    foreach ($data as $item) {
        $formatted_results[] = [
            // El ID y el Texto son necesarios para Select2
            'id' => $item['place_id'], // Usamos place_id como ID
            'text' => $item['display_name'], // La dirección completa
            // También devolvemos las coordenadas para que el frontend las use directamente
            'lat' => (float)$item['lat'],
            'lon' => (float)$item['lon']
        ];
    }
}

// Devolver los resultados en el formato esperado por Select2
echo json_encode(['results' => $formatted_results]);
?>
