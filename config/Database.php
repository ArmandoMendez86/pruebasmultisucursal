<?php // Archivo: /config/Database.php

// Es importante incluir tu archivo de constantes ANTES de usar la clase
require_once 'config.php';

class Database
{

    private $conn;
    private static $instance = null;

    private function __construct()
    {
        // Usamos directamente las constantes definidas en config.php
        $dsn = 'mysql:host=' . DB2_HOST . ';dbname=' . DB2_NAME;

        try {
            $this->conn = new PDO($dsn, DB2_USER, DB2_PASSWORD);

            // Opciones de la conexión
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("SET NAMES '" . DB_CHARSET . "'");
            $this->conn->exec("SET time_zone = '-06:00'");

        } catch (PDOException $e) {
            // Manejo de errores
            echo 'Error de conexión: ' . $e->getMessage();
            // En un entorno de producción, es mejor registrar el error que mostrarlo.
            exit;
        }
    }

    // Este método estático es parte del patrón Singleton para asegurar una única instancia
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Método para obtener la conexión y poder usarla fuera de la clase
    public function getConnection()
    {
        return $this->conn;
    }
}
?>