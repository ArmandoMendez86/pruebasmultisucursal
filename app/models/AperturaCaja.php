<?php
// Archivo: /app/models/AperturaCaja.php

require_once __DIR__ . '/../../config/Database.php';

class AperturaCaja {
    private $conn;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Registra la apertura de caja para un usuario, sucursal y fecha específicas.
     * @param int $id_usuario ID del usuario que realiza la apertura.
     * @param int $id_sucursal ID de la sucursal.
     * @param string $fecha_apertura Fecha de apertura (YYYY-MM-DD).
     * @param float $monto_inicial Monto con el que se abre la caja.
     * @return int El ID del registro de apertura de caja.
     * @throws Exception Si ya existe una apertura para ese usuario, fecha y sucursal o hay un error en la base de datos.
     */
    public function registrarApertura($id_usuario, $id_sucursal, $fecha_apertura, $monto_inicial) {
        // Verificar si ya existe una apertura para este usuario, fecha y sucursal
      /*   if ($this->obtenerAperturaPorUsuarioFecha($id_usuario, $id_sucursal, $fecha_apertura)) {
            throw new Exception("Ya has realizado la apertura de caja para esta sucursal en la fecha {$fecha_apertura}.");
        } */

        $query = "INSERT INTO aperturas_caja (id_usuario, id_sucursal, fecha_apertura, monto_inicial)
                  VALUES (:id_usuario, :id_sucursal, :fecha_apertura, :monto_inicial)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindParam(':fecha_apertura', $fecha_apertura);
        $stmt->bindParam(':monto_inicial', $monto_inicial, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            throw new Exception("Error al registrar la apertura de caja.");
        }
    }

    /**
     * Obtiene el registro de apertura de caja para un usuario, sucursal y fecha específicas.
     * @param int $id_usuario ID del usuario.
     * @param int $id_sucursal ID de la sucursal.
     * @param string $fecha_apertura Fecha a buscar (YYYY-MM-DD).
     * @return array|false Los datos de la apertura de caja o false si no se encuentra.
     */
    public function obtenerAperturaPorUsuarioFecha($id_usuario, $id_sucursal, $fecha_apertura) {
        $query = "SELECT id, id_usuario, id_sucursal, fecha_apertura, monto_inicial, created_at
                  FROM aperturas_caja
                  WHERE id_usuario = :id_usuario AND id_sucursal = :id_sucursal AND fecha_apertura = :fecha_apertura";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindParam(':fecha_apertura', $fecha_apertura);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}