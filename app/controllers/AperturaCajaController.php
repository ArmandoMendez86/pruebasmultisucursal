<?php
// Archivo: /app/controllers/AperturaCajaController.php

require_once __DIR__ . '/../models/AperturaCaja.php';

class AperturaCajaController
{
    private $aperturaCajaModel;

    public function __construct()
    {
        $this->aperturaCajaModel = new AperturaCaja();
    }

    /**
     * Verifica si el usuario logueado ha abierto su caja para la sucursal y fecha actual.
     * Retorna el monto inicial si ya está abierta, o false si no.
     */
    public function checkApertura()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $id_usuario = $_SESSION['user_id'];
        $id_sucursal = $_SESSION['branch_id'];
        $fecha_actual = date('Y-m-d');

        try {
            $apertura = $this->aperturaCajaModel->obtenerAperturaPorUsuarioFecha($id_usuario, $id_sucursal, $fecha_actual);
            if ($apertura) {
                echo json_encode(['success' => true, 'opened' => true, 'monto_inicial' => $apertura['monto_inicial']]);
            } else {
                echo json_encode(['success' => true, 'opened' => false]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al verificar la apertura de caja: ' . $e->getMessage()]);
        }
    }

    /**
     * Registra una nueva apertura de caja para el usuario logueado.
     */
    public function registrarApertura()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['monto_inicial'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Datos inválidos. Se requiere el monto inicial.']);
            return;
        }
        
        $id_usuario = $_SESSION['user_id'];
        $id_sucursal = $_SESSION['branch_id'];
        $monto_inicial = floatval($data['monto_inicial']);
        $fecha_apertura = date('Y-m-d');

        try {
            $id_apertura = $this->aperturaCajaModel->registrarApertura($id_usuario, $id_sucursal, $fecha_apertura, $monto_inicial);
            echo json_encode(['success' => true, 'message' => 'Apertura de caja registrada exitosamente para la fecha ' . $fecha_apertura . '.', 'id_apertura' => $id_apertura]);
        } catch (Exception $e) {
            http_response_code(409); // Conflicto
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene el monto de apertura de caja para un usuario y fecha específicos (usado en reportes).
     */
    public function getMontoApertura()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        // Si se especifica un usuario (para reportes de admin), usarlo. Si no, usar el del usuario logueado.
        $id_usuario_reporte = $_GET['user_id'] ?? $_SESSION['user_id'];
        $fecha = $_GET['date'] ?? date('Y-m-d');

        try {
            $apertura = $this->aperturaCajaModel->obtenerAperturaPorUsuarioFecha($id_usuario_reporte, $id_sucursal, $fecha);
            if ($apertura) {
                echo json_encode(['success' => true, 'monto_inicial' => $apertura['monto_inicial']]);
            } else {
                echo json_encode(['success' => true, 'monto_inicial' => 0]); // Retorna 0 si no hay apertura
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener el monto de apertura: ' . $e->getMessage()]);
        }
    }
}