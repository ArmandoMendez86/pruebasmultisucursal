<?php
// Archivo: /public/create_hash.php
// ----------------------------------------------------------------------
// INSTRUCCIONES:
// 1. Guarda este archivo como 'create_hash.php' dentro de tu carpeta /public.
// 2. Abre en tu navegador la URL: http://localhost/multi-sucursal/public/create_hash.php
// 3. Copia el hash que aparece en pantalla.
// 4. Ve a phpMyAdmin, a tu tabla 'usuarios'.
// 5. Edita el usuario 'linux' y pega el nuevo hash en la columna 'password'.
// 6. Guarda los cambios en la base de datos.
// 7. Intenta iniciar sesión de nuevo con el usuario 'linux' y la contraseña 'linux'.
// ----------------------------------------------------------------------

// La contraseña que quieres usar
$password_a_hashear = 'linux';

// Generar el hash usando el algoritmo recomendado por PHP
$hash = password_hash($password_a_hashear, PASSWORD_DEFAULT);

// Mostrar el resultado en un formato fácil de leer y copiar
echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head><meta charset='UTF-8'><title>Generador de Hash</title>";
echo "<style>body { font-family: monospace; background-color: #f0f0f0; padding: 20px; } .hash { background-color: #fff; border: 1px solid #ccc; padding: 15px; font-size: 1.2em; word-wrap: break-word; } </style>";
echo "</head>";
echo "<body>";
echo "<h1>Hash Generado para la contraseña: '" . htmlspecialchars($password_a_hashear) . "'</h1>";
echo "<p>Copia la siguiente cadena y pégala en la columna 'password' de tu base de datos:</p>";
echo "<div class='hash'>" . htmlspecialchars($hash) . "</div>";
echo "</body>";
echo "</html>";

?>
