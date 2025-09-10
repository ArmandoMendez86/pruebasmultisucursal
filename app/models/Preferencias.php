<?php
// Archivo: /app/models/Preferencias.php
require_once __DIR__ . '/../../config/Database.php';

class Preferencias {
    private $conn;
    private $table = 'usuarios';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Devuelve preferencias de impresión por usuario.
     * Retorna ['print_method' => 'service'|'qztray', 'impresora_tickets' => string|null]
     */
    public function getByUserId($userId) {
        $sql = "SELECT print_method, impresora_tickets FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', (int)$userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['print_method' => 'service', 'impresora_tickets' => null];
        }
        $method = ($row['print_method'] === 'qztray') ? 'qztray' : 'service';
        return ['print_method' => $method, 'impresora_tickets' => $row['impresora_tickets']];
    }

    /**
     * Actualiza el método de impresión del usuario.
     */
    public function updateMethod($userId, $method) {
        $sql = "UPDATE {$this->table} SET print_method = :method WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(':method', $method, PDO::PARAM_STR);
        return $stmt->execute();
    }
}
