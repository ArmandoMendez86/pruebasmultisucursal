<?php
// Archivo: /app/models/Sucursal.php

require_once __DIR__ . '/../../config/Database.php';

class Sucursal
{
    private $conn;
    private $table_name = "sucursales";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getAll()
    {
        $query = "SELECT * FROM {$this->table_name} ORDER BY nombre ASC";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        // Campos permitidos
        $cols = ['nombre','direccion','telefono','email','logo_url'];
        $values = [];
        $placeholders = [];
        $params = [];

        foreach ($cols as $c) {
            if (array_key_exists($c, $data)) {
                $values[$c] = htmlspecialchars(strip_tags((string)$data[$c]));
            }
        }
        if (empty($values)) return false;

        foreach ($values as $k => $v) {
            $placeholders[] = ':' . $k;
            $params[':' . $k] = $v;
        }

        $columnsSql = implode(',', array_keys($values));
        $phSql      = implode(',', $placeholders);
        $query      = "INSERT INTO {$this->table_name} ({$columnsSql}) VALUES ({$phSql})";
        $stmt       = $this->conn->prepare($query);

        foreach ($params as $pk => $pv) $stmt->bindValue($pk, $pv);

        return $stmt->execute();
    }

    public function update($id, $data)
    {
        // Actualización parcial segura
        $allowed = ['nombre','direccion','telefono','email','logo_url'];
        $set = [];
        $params = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = htmlspecialchars(strip_tags((string)$data[$key]));
                $set[] = "{$key} = :{$key}";
                $params[":{$key}"] = $val;
            }
        }
        if (empty($set)) return false;

        $sql = "UPDATE {$this->table_name} SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($params as $pk => $pv) $stmt->bindValue($pk, $pv);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $query = "DELETE FROM {$this->table_name} WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
