<?php
// app/models/Dashboard.php

require_once __DIR__ . '/../../config/Database.php';

class Dashboard
{
    private $conn;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene las ventas completadas del día con sus métodos de pago para una sucursal.
     */
    public function getVentasHoyConPagos($id_sucursal)
    {
        $sql = "SELECT metodo_pago, total FROM ventas WHERE id_sucursal = :id_sucursal AND DATE(fecha) = CURDATE() AND estado = 'Completada'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el total de la deuda de todos los clientes.
     */
    public function getCuentasPorCobrar()
    {
        $sql = "SELECT SUM(deuda_actual) as total_deuda FROM clientes WHERE tiene_credito = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_deuda'] ?? 0;
    }

    /**
     * Obtiene el total de gastos del día para una sucursal.
     */
    public function getGastosHoy($id_sucursal)
    {
        $sql = "SELECT SUM(monto) as total_gastos FROM gastos WHERE id_sucursal = :id_sucursal AND DATE(fecha) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_gastos'] ?? 0;
    }

    /**
     * Obtiene el conteo de ventas completadas del día para una sucursal.
     */
    public function getConteoVentasHoy($id_sucursal)
    {
        $sql = "SELECT COUNT(id) as total_ventas FROM ventas WHERE id_sucursal = :id_sucursal AND DATE(fecha) = CURDATE() AND estado = 'Completada'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_ventas'] ?? 0;
    }

    /**
     * Obtiene los 5 productos más vendidos en una sucursal.
     */
    public function getTopProductos($id_sucursal)
    {
        $sql = "SELECT p.nombre, SUM(vd.cantidad) as total_vendido
                FROM venta_detalles vd
                JOIN productos p ON vd.id_producto = p.id
                JOIN ventas v ON vd.id_venta = v.id
                WHERE v.id_sucursal = :id_sucursal AND v.estado = 'Completada'
                GROUP BY p.nombre
                ORDER BY total_vendido DESC
                LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los 5 clientes con mayores compras en una sucursal.
     */
    public function getTopClientes($id_sucursal)
    {
        $sql = "SELECT c.nombre, SUM(v.total) as total_comprado
                FROM ventas v
                JOIN clientes c ON v.id_cliente = c.id
                WHERE v.id_sucursal = :id_sucursal AND v.estado = 'Completada' AND c.id != 1
                GROUP BY c.nombre
                ORDER BY total_comprado DESC
                LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
