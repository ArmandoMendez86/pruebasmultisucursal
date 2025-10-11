<?php
// =======================================================================
// CONFIGURACIÓN: AJUSTA ESTOS VALORES A TU ENTORNO
// =======================================================================

// AUMENTO DEL LÍMITE DE TIEMPO: Permite que el script corra indefinidamente 
// desde la consola, ignorando el límite de 30 segundos del servidor web.
set_time_limit(0); 
date_default_timezone_set('America/Mexico_City'); // Ajustar zona horaria

// 1. Configuración de la Base de Datos (MySQL/MariaDB)
$servername = "localhost";
$username   = "u111680873_geocode";
$password   = "Geocode861215#-";
$dbname     = "u111680873_geocode";
$logFile = 'geocodificacion_errors.log'; // Archivo de log para errores de API

// 2. URL del servicio de Geocodificación (Nominatim de OpenStreetMap)
$nominatimUrl = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=mx&limit=1';

// 3. Límite de control
const DELAY_SECONDS = 1; // Pausa obligatoria
const BATCH_SIZE = 100;

// =======================================================================
// FUNCIÓN PRINCIPAL
// =======================================================================

/**
 * Registra un error en el archivo de log.
 */
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}


echo "--- INICIANDO PROCESO DE GEOCODIFICACIÓN MASIVA ---\n";
echo "--- Los errores se registrarán en: {$logFile} ---\n";

// 1. Conexión a la Base de Datos
try {
    $pdo = new PDO("mysql:host=$dbHost;port=3306;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión a la base de datos establecida.\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// 2. Consulta para obtener las direcciones sin coordenadas (BATCH)
$sqlSelect = "
    SELECT 
        cd.id, 
        cd.codigo_postal, 
        cd.estado,
        c.nombre AS nombre_cliente
    FROM 
        cliente_direcciones cd
    LEFT JOIN  
        clientes c ON cd.id_cliente = c.id
    WHERE 
        (cd.latitud IS NULL OR cd.longitud IS NULL)
        AND cd.codigo_postal IS NOT NULL AND TRIM(cd.codigo_postal) != ''
        AND cd.estado IS NOT NULL AND TRIM(cd.estado) != ''
    LIMIT :limit
";
$stmtSelect = $pdo->prepare($sqlSelect);
$stmtSelect->bindParam(':limit', $batchSize, PDO::PARAM_INT);
$batchSize = BATCH_SIZE;

// 3. Sentencia para actualizar las coordenadas
$sqlUpdate = "
    UPDATE 
        cliente_direcciones 
    SET 
        latitud = :latitud, 
        longitud = :longitud 
    WHERE 
        id = :id
";
$stmtUpdate = $pdo->prepare($sqlUpdate);


// 4. Bucle principal de geocodificación
$processedCount = 0;
while (true) {
    $stmtSelect->execute();
    $direcciones = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    if (empty($direcciones)) {
        echo "\n¡PROCESO FINALIZADO! Todas las direcciones válidas han sido geocodificadas.\n";
        break;
    }

    echo "\n>>> Procesando lote de " . count($direcciones) . " registros válidos...\n";

    foreach ($direcciones as $dir) {
        $processedCount++;
        
        $query = urlencode($dir['codigo_postal'] . ', ' . $dir['estado'] . ', México');
        $fullUrl = $nominatimUrl . '&q=' . $query;

        // 4.1. Llamada a la API externa
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TuAplicacionDeClientes/1.0 (ejemplo@email.com)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // ** CAMBIO: Establecer tiempos de espera (Timeouts) **
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Máximo 5 segundos para transferencia
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Máximo 3 segundos para conectar
        
        // Obtener el código de estado HTTP
        curl_setopt($ch, CURLOPT_HEADER, true); 
        $response = curl_exec($ch);
        
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Separar encabezados del cuerpo
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseBody = $response !== false ? substr($response, $headerSize) : ''; // Manejo si la respuesta es FALSE

        // ** 4.2. MANEJO DE ERRORES DE CONEXIÓN O BLOQUEO **
        if ($curlError) {
             // Esto capturará los timeouts
             logError("CURL ERROR: ID {$dir['id']} - {$dir['codigo_postal']}. Error: {$curlError}. URL: {$fullUrl}");
             echo " [CURL FAIL] ID {$dir['id']} - Error de red o Timeout. Revisar log.\n";
             // Dormimos más tiempo por si es un error temporal de la red
             sleep(5); 
             continue;
        }

        if ($httpCode === 429) {
            logError("BLOQUEO DE API: ID {$dir['id']} - {$dir['codigo_postal']}. HTTP 429 (Too Many Requests). La IP ha sido bloqueada temporalmente.");
            die("\n--- ERROR CRÍTICO: La API de Nominatim te ha BLOQUEADO (HTTP 429). Espera 24 horas para reintentar. ---\n");
        }
        
        if ($httpCode !== 200) {
            logError("HTTP ERROR: ID {$dir['id']} - {$dir['codigo_postal']}. HTTP Code: {$httpCode}. Body: {$responseBody}. URL: {$fullUrl}");
            echo " [HTTP FAIL] ID {$dir['id']} - Error {$httpCode}. Revisar log.\n";
            sleep(DELAY_SECONDS);
            continue;
        }

        $results = json_decode($responseBody, true);
        
        $lat = null;
        $lon = null;

        // ** 4.3. PROCESAMIENTO DE RESPUESTA EXITOSA (HTTP 200) **
        if (!empty($results) && isset($results[0]['lat']) && isset($results[0]['lon'])) {
            $lat = $results[0]['lat'];
            $lon = $results[0]['lon'];

            // Actualizar la base de datos
            $stmtUpdate->execute([
                ':latitud' => $lat,
                ':longitud' => $lon,
                ':id' => $dir['id']
            ]);
            
            $cliente_nombre = $dir['nombre_cliente'] ? $dir['nombre_cliente'] : 'N/A';
            echo " [OK] ID {$dir['id']} ({$cliente_nombre} - {$dir['codigo_postal']}) -> Coordenadas: {$lat}, {$lon}\n";

        } else {
            // Error de API, pero la conexión fue exitosa (Nominatim no encontró el lugar)
            logError("NOT FOUND: ID {$dir['id']} - {$dir['codigo_postal']}. No encontrado por Nominatim. URL: {$fullUrl}");
            echo " [NOT FOUND] ID {$dir['id']} (CP: {$dir['codigo_postal']}) - No encontrado. Revisar log.\n";
        }

        // 4.4. PAUSA OBLIGATORIA
        sleep(DELAY_SECONDS); 
    }
}

echo "\n--- PROCESO COMPLETADO. Total de registros válidos procesados: {$processedCount} ---\n";
?>
