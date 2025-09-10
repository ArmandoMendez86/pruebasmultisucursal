<?php
// Archivo: /app/controllers/ImpresionController.php
require_once __DIR__ . '/../models/Preferencias.php';

class ImpresionController {
    private $prefs;

    public function __construct() {
        $this->prefs = new Preferencias();
    }

    // GET: index.php?action=getPrintPrefs
    public function getPrintPrefs() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']);
            return;
        }
        try {
            $data = $this->prefs->getByUserId($_SESSION['user_id']);
            echo json_encode(['success'=>true,'data'=>$data]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'message'=>'Error interno']);
        }
    }

    // POST JSON: {print_method:'service'|'qztray'}
    public function updatePrintPrefs() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']);
            return;
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $method = isset($payload['print_method'])
            ? filter_var($payload['print_method'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            : null;

        if (!in_array($method, ['service','qztray'], true)) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'print_method inválido']);
            return;
        }
        try {
            $ok = $this->prefs->updateMethod($_SESSION['user_id'], $method);
            if ($ok) {
                echo json_encode(['success'=>true,'message'=>'Preferencia actualizada']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'message'=>'No se pudo actualizar']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'message'=>'Error interno']);
        }
    }
}
