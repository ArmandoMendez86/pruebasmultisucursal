<?php
// Archivo: /app/controllers/ConfiguracionController.php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Sucursal.php';

class ConfiguracionController
{
    private $usuarioModel;
    private $sucursalModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->sucursalModel = new Sucursal();
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // --- Impresora preferida del usuario ---
    public function getPrinterConfig()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']); return; }
        $id_usuario = $_SESSION['user_id'];
        $prefs = $this->usuarioModel->getPrinter($id_usuario);
        echo json_encode(['success'=>true,'data'=>$prefs]);
    }

    public function updatePrinterConfig()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']); return; }
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $printerName = $data['impresora_tickets'] ?? null;
        $id_usuario = $_SESSION['user_id'];
        if ($this->usuarioModel->updatePrinter($id_usuario, $printerName)) {
            echo json_encode(['success'=>true,'message'=>'Impresora guardada exitosamente.']);
        } else {
            http_response_code(500); echo json_encode(['success'=>false,'message'=>'No se pudo guardar la impresora.']);
        }
    }

    // --- Configuración de la sucursal ---
    public function getBranchConfig()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']); return; }
        $id_sucursal = $_SESSION['branch_id'];
        $config = $this->sucursalModel->getById($id_sucursal);
        if ($config) echo json_encode(['success'=>true,'data'=>$config]);
        else { http_response_code(404); echo json_encode(['success'=>false,'message'=>'No se encontró la configuración de la sucursal.']); }
    }

    public function updateBranchConfig()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']); return; }

        $in = json_decode(file_get_contents('php://input'), true) ?? [];
        $payload = [
            'nombre'    => array_key_exists('nombre', $in) ? $in['nombre'] : null,
            'direccion' => array_key_exists('direccion', $in) ? $in['direccion'] : null,
            'telefono'  => array_key_exists('telefono', $in) ? $in['telefono'] : null,
            'email'     => array_key_exists('email', $in) ? $in['email'] : null,
            'logo_url'  => array_key_exists('logo_url', $in) ? $in['logo_url'] : null,
        ];
        $payload = array_filter($payload, fn($v) => $v !== null);

        if (isset($payload['nombre']) && trim((string)$payload['nombre']) === '') {
            http_response_code(400); echo json_encode(['success'=>false,'message'=>'El nombre de la sucursal es requerido.']); return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        $ok = $this->sucursalModel->update($id_sucursal, $payload);

        if ($ok) {
            if (isset($payload['nombre']))   $_SESSION['branch_name'] = $payload['nombre'];
            if (isset($payload['logo_url'])) $_SESSION['logo_url']   = $payload['logo_url'];
            echo json_encode(['success'=>true,'message'=>'Configuración guardada exitosamente.']);
        } else {
            http_response_code(500); echo json_encode(['success'=>false,'message'=>'No se pudo guardar la configuración.']);
        }
    }

    public function uploadBranchLogo()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso no autorizado.']); return; }
        if (!isset($_FILES['logo']) || !is_uploaded_file($_FILES['logo']['tmp_name'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'No se envió archivo.']); return; }
        if ($_FILES['logo']['size'] > 5 * 1024 * 1024) { http_response_code(413); echo json_encode(['success'=>false,'message'=>'La imagen excede 5MB.']); return; }

        $destDir = __DIR__ . '/../../public/img/';
        [$ok, $res] = $this->optimizarYGuardar($_FILES['logo'], $destDir, 1600, 1600, 80);
        if (!$ok) { http_response_code(415); echo json_encode(['success'=>false,'message'=>$res]); return; }

        $relative = '/img/' . $res;
        $id_sucursal = $_SESSION['branch_id'];
        $updateOk = $this->sucursalModel->update($id_sucursal, ['logo_url' => $relative]);
        if (!$updateOk) { @unlink($destDir . $res); http_response_code(500); echo json_encode(['success'=>false,'message'=>'No se pudo guardar en la base de datos.']); return; }

        $_SESSION['logo_url'] = $relative; // refresca navegación

        echo json_encode(['success'=>true,'message'=>'Logo subido y optimizado.','url'=>$relative]);
    }

    // ---- Helpers de imagen ----
    private function optimizarYGuardar(array $file, string $destDir, int $maxW, int $maxH, int $qualityWebp = 80)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $permitidos = ['image/jpeg','image/png','image/webp'];
        if (!in_array($mime, $permitidos, true)) return [false,'Formato no permitido. Usa JPG, PNG o WebP.'];

        switch ($mime) {
            case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
            case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); imagesavealpha($src, true); break;
            case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
            default: return [false,'Formato no soportado.'];
        }
        if (!$src) return [false,'No se pudo leer la imagen.'];

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file['tmp_name']);
            if (!empty($exif['Orientation'])) $src = $this->aplicarOrientacion($src, (int)$exif['Orientation']);
        }

        $w = imagesx($src); $h = imagesy($src);
        $ratio = min($maxW / $w, $maxH / $h, 1);
        $nw = (int)round($w * $ratio); $nh = (int)round($h * $ratio);

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
        $base = 'logo_' . bin2hex(random_bytes(6)) . '_' . time();

        if (function_exists('imagewebp')) {
            $fileName = $base . '.webp';
            if (!imagewebp($dst, $destDir . $fileName, $qualityWebp)) return [false,'No se pudo guardar WebP.'];
        } else {
            $fileName = $base . '.jpg';
            $bg = imagecreatetruecolor($nw, $nh);
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $dst, 0, 0, 0, 0, $nw, $nh);
            if (!imagejpeg($bg, $destDir . $fileName, 80)) return [false,'No se pudo guardar JPG.'];
            imagedestroy($bg);
        }

        @chmod($destDir . $fileName, 0644);
        imagedestroy($src); imagedestroy($dst);
        return [true, $fileName];
    }

    private function aplicarOrientacion($img, int $orientation)
    {
        switch ($orientation) {
            case 2: imageflip($img, IMG_FLIP_HORIZONTAL); break;
            case 3: $img = imagerotate($img, 180, 0); break;
            case 4: imageflip($img, IMG_FLIP_VERTICAL); break;
            case 5: $img = imagerotate($img, -90, 0); imageflip($img, IMG_FLIP_HORIZONTAL); break;
            case 6: $img = imagerotate($img, -90, 0); break;
            case 7: $img = imagerotate($img, 90, 0); imageflip($img, IMG_FLIP_HORIZONTAL); break;
            case 8: $img = imagerotate($img, 90, 0); break;
        }
        return $img;
    }
}
?>
