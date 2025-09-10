<?php
// Archivo: /app/models/Gasto.php

require_once __DIR__ . '/../../config/Database.php';

class Gasto {
    private $conn;
    private $table_name = "gastos";

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    // --- INICIO: Nuevo método para Server-Side ---
    public function getAllServerSide($id_sucursal, $params) {
        $baseQuery = "FROM " . $this->table_name;
        $whereClause = " WHERE id_sucursal = :id_sucursal";
        $bindings = [':id_sucursal' => $id_sucursal];

        // Búsqueda (Filtro)
        if (!empty($params['search']['value'])) {
            $searchValue = '%' . $params['search']['value'] . '%';
            $whereClause .= " AND (categoria_gasto LIKE :search_value OR descripcion LIKE :search_value)";
            $bindings[':search_value'] = $searchValue;
        }

        // Conteo total de registros (sin filtro de búsqueda)
        $stmtTotal = $this->conn->prepare("SELECT COUNT(id) " . $baseQuery . " WHERE id_sucursal = :id_sucursal");
        $stmtTotal->execute([':id_sucursal' => $id_sucursal]);
        $recordsTotal = $stmtTotal->fetchColumn();

        // Conteo de registros filtrados
        $stmtFiltered = $this->conn->prepare("SELECT COUNT(id) " . $baseQuery . " " . $whereClause);
        $stmtFiltered->execute($bindings);
        $recordsFiltered = $stmtFiltered->fetchColumn();

        // Ordenamiento
        $columns = ['fecha', 'categoria_gasto', 'descripcion', 'monto'];
        $orderClause = " ORDER BY " . $columns[$params['order'][0]['column']] . " " . $params['order'][0]['dir'];

        // Paginación
        $limitClause = " LIMIT :limit OFFSET :offset";
        $bindings[':limit'] = intval($params['length']);
        $bindings[':offset'] = intval($params['start']);

        // Consulta principal para obtener los datos
        $query = "SELECT id, fecha, categoria_gasto, descripcion, monto " . $baseQuery . $whereClause . $orderClause . $limitClause;

        $stmtData = $this->conn->prepare($query);
        foreach ($bindings as $key => &$val) {
             $stmtData->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtData->execute();
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }
    // --- FIN: Nuevo método para Server-Side ---

    /**
     * @deprecated Reemplazado por getAllServerSide para la tabla principal.
     */
    public function getAllBySucursal($id_sucursal) {
        $query = "SELECT id, fecha, categoria_gasto, descripcion, monto 
                  FROM " . $this->table_name . " 
                  WHERE id_sucursal = :id_sucursal 
                  ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " (id_usuario, id_sucursal, categoria_gasto, descripcion, monto) VALUES (:id_usuario, :id_sucursal, :categoria_gasto, :descripcion, :monto)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $data['id_usuario']);
        $stmt->bindParam(':id_sucursal', $data['id_sucursal']);
        $stmt->bindParam(':categoria_gasto', $data['categoria_gasto']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':monto', $data['monto']);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET 
                    categoria_gasto = :categoria_gasto,
                    descripcion = :descripcion,
                    monto = :monto
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':categoria_gasto', $data['categoria_gasto']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':monto', $data['monto']);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
