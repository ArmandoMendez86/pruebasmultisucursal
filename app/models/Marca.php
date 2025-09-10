<?php
// Archivo: /app/models/Marca.php

require_once __DIR__ . '/../../config/Database.php';

class Marca
{
    private $conn;
    private $table_name = "marcas";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene todas las marcas de la base de datos.
     * @return array Array de objetos de marca.
     */
    public function getAll()
    {
        $query = "SELECT id, nombre FROM " . $this->table_name . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva marca.
     * @param array $data Array asociativo con 'nombre'.
     * @return bool True si la creación fue exitosa, false en caso contrario.
     */
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " (nombre) VALUES (:nombre)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $data['nombre']);
        return $stmt->execute();
    }

    /**
     * Actualiza una marca existente.
     * @param int $id ID de la marca a actualizar.
     * @param array $data Array asociativo con 'nombre'.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     .
     */
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $data['nombre']);
        return $stmt->execute();
    }

    /**
     * Elimina una marca.
     * @param int $id ID de la marca a eliminar.
     * @return bool True si la eliminación fue exitosa, false en caso contrario.
     */
    public function delete($id)
    {
        // Considerar la lógica para manejar productos asociados a esta marca
        // Por ahora, si la FK está configurada con ON DELETE CASCADE, la BD lo manejará.
        // Si no, necesitarás actualizar los productos a NULL o a otra marca por defecto.
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
