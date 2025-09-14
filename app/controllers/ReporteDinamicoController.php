<?php
// Archivo: /app/controllers/ReporteDinamicoController.php
require_once __DIR__ . '/../models/ReporteDinamico.php';

class ReporteDinamicoController
{
    private $model;

    public function __construct()
    {
        $this->model = new ReporteDinamico();
    }

    private function ensureSuper()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    public function getReportBuilderMeta()
    {
        $this->ensureSuper();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->model->getMeta()]);
    }

    public function getDynamicReportServerSide()
    {
        $this->ensureSuper();
        header('Content-Type: application/json');

        // Acepta application/json además de form-data
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: [];
            $_POST = array_merge($_POST, $body); // mezclamos por simplicidad
        }

        try {
            $result = $this->model->getServerSide($_POST);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error al generar reporte']);
        }
    }

    public function listPresets()
    {
        $this->ensureSuper(); // misma seguridad
        header('Content-Type: application/json');
        try {
            $rows = $this->model->listPresets();
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al listar', 'error' => $e->getMessage()]);
        }
    }

    public function savePreset()
    {
        $this->ensureSuper();
        header('Content-Type: application/json');

        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: [];
            $_POST = array_merge($_POST, $body);
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $config = $_POST['config'] ?? null; // objeto/array
        if ($nombre === '' || $config === null) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Faltan datos']);
            return;
        }

        // si tienes id de usuario en sesión, úsalo:
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $uid = $_SESSION['id_usuario'] ?? null;

        try {
            $id = $this->model->savePreset($nombre, $descripcion, $config, $uid);
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar', 'error' => $e->getMessage()]);
        }
    }

    public function getPreset()
    {
        $this->ensureSuper();
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }
        try {
            $row = $this->model->getPreset($id);
            echo json_encode(['success' => true, 'data' => $row]);
        } catch (Throwable $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No encontrado', 'error' => $e->getMessage()]);
        }
    }

    public function deletePreset()
    {
        $this->ensureSuper();
        header('Content-Type: application/json');
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: [];
            $_POST = array_merge($_POST, $body);
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }
        try {
            $ok = $this->model->deletePreset($id);
            echo json_encode(['success' => $ok]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar', 'error' => $e->getMessage()]);
        }
    }

    //Consultas tipo workbench SQL

    public function runRawSQL()
    {
        $this->ensureSuper(); // mismo guard que usas para el builder
        header('Content-Type: application/json');

        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: [];
            $_POST = array_merge($_POST, $body);
        }

        $sql = $_POST['sql'] ?? '';
        $limit = (int) ($_POST['limit'] ?? 1000);

        try {
            $data = $this->model->runRawSQL($sql, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function listSqlPresets()
    {
        $this->ensureSuper();
        header('Content-Type: application/json');
        try {
            $rows = $this->model->listSqlPresets();
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al listar', 'error' => $e->getMessage()]);
        }
    }



}
