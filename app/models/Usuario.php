<?php
// Archivo: /app/models/Usuario.php

require_once __DIR__ . '/../../config/Database.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Busca un usuario por su username para el proceso de login.
     */
    public function findByUsername($username) {
        $query = "SELECT id, id_sucursal, nombre, username, password, rol 
                  FROM " . $this->table_name . " 
                  WHERE username = :username AND activo = 1 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $username = htmlspecialchars(strip_tags($username));
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_OBJ);
        }

        return null;
    }

    /**
     * Obtiene todos los usuarios con el nombre de su sucursal para la gestión.
     */
    public function getAll() {
        $query = "SELECT 
                    u.id, 
                    u.nombre, 
                    u.username, 
                    u.rol, 
                    u.id_sucursal,
                    s.nombre as sucursal_nombre,
                    u.activo
                  FROM 
                    " . $this->table_name . " u
                  LEFT JOIN 
                    sucursales s ON u.id_sucursal = s.id
                  ORDER BY u.nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un solo usuario por su ID.
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                    (nombre, username, password, rol, id_sucursal, activo) 
                  VALUES 
                    (:nombre, :username, :password, :rol, :id_sucursal, 1)";
        
        $stmt = $this->conn->prepare($query);

        $nombre = htmlspecialchars(strip_tags($data['nombre']));
        $username = htmlspecialchars(strip_tags($data['username']));
        $rol = htmlspecialchars(strip_tags($data['rol']));
        $id_sucursal = htmlspecialchars(strip_tags($data['id_sucursal']));
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':id_sucursal', $id_sucursal);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update($data) {
        $password_part = "";
        if (!empty($data['password'])) {
            $password_part = ", password = :password";
        }

        $query = "UPDATE " . $this->table_name . " SET 
                    nombre = :nombre, 
                    username = :username, 
                    rol = :rol, 
                    id_sucursal = :id_sucursal
                    {$password_part}
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $id = htmlspecialchars(strip_tags($data['id']));
        $nombre = htmlspecialchars(strip_tags($data['nombre']));
        $username = htmlspecialchars(strip_tags($data['username']));
        $rol = htmlspecialchars(strip_tags($data['rol']));
        $id_sucursal = htmlspecialchars(strip_tags($data['id_sucursal']));

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':id_sucursal', $id_sucursal);

        if (!empty($data['password'])) {
            $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $password_hash);
        }

        return $stmt->execute();
    }

    /**
     * Elimina un usuario por su ID.
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Obtiene solo la configuración de la impresora de un usuario.
     */
    public function getPrinter($id) {
        $query = "SELECT impresora_tickets FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['impresora_tickets'] : null;
    }

    /**
     * Actualiza solo la configuración de la impresora de un usuario.
     */
    public function updatePrinter($id, $printerName) {
        $query = "UPDATE " . $this->table_name . " SET impresora_tickets = :impresora_tickets WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':impresora_tickets', $printerName);
        return $stmt->execute();
    }

     public function getUsersByBranch($id_sucursal) {
        $query = "SELECT id, nombre 
                  FROM " . $this->table_name . " 
                  WHERE id_sucursal = :id_sucursal AND activo = 1
                  ORDER BY nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
