<?php
// Archivo: /app/controllers/LoginController.php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Sucursal.php';
// Se elimina la necesidad de incluir el modelo de AperturaCaja
// require_once __DIR__ . '/../models/AperturaCaja.php'; 

class LoginController
{

    public function login()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'));

        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
            return;
        }

        try {
            $usuarioModel = new Usuario();
            $user = $usuarioModel->findByUsername($data->username);

            if ($user && password_verify($data->password, $user->password)) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->nombre;
                $_SESSION['rol'] = $user->rol;
                $_SESSION['branch_id'] = $user->id_sucursal;
                
                // --- LÓGICA AÑADIDA ---
                // 2. Obtenemos el nombre de la sucursal y lo guardamos en la sesión.
                $sucursalModel = new Sucursal();
                $sucursal = $sucursalModel->getById($user->id_sucursal);
                $nombreSucursal = $sucursal ? $sucursal['nombre'] : 'Sucursal Desconocida';
                $_SESSION['branch_name'] = $nombreSucursal;
                $_SESSION['logo_url'] = $sucursal['logo_url'];
                // --- FIN LÓGICA AÑADIDA ---

                $userData = [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'username' => $user->username,
                    'rol' => $user->rol,
                    'id_sucursal' => $user->id_sucursal
                ];

                http_response_code(200);
                // La respuesta JSON ya no incluye la bandera 'requires_cash_opening'.
                echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.', 'user' => $userData]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ocurrió un error en el servidor.', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout()
    {
        header('Content-Type: application/json');

        // Destruimos todas las variables de sesión.
        $_SESSION = array();

        // Si se desea destruir la sesión completamente, borre también la cookie de sesión.
        // Nota: ¡Esto destruirá la sesión, y no solo los datos de la sesión!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finalmente, destruir la sesión.
        session_destroy();

        echo json_encode(['success' => true, 'message' => 'Sesión cerrada exitosamente.']);
    }

    /**
     * Verifica si existe una sesión activa y devuelve los datos del usuario.
     */
    public function checkSession()
    {
        header('Content-Type: application/json');

        if (isset($_SESSION['user_id'])) {
            // Si hay una sesión, devolvemos los datos guardados.
            $userData = [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['user_name'],
                'rol' => $_SESSION['rol'],
                'id_sucursal' => $_SESSION['branch_id']
            ];
            echo json_encode(['success' => true, 'user' => $userData]);
        } else {
            // Si no hay sesión, lo indicamos.
            echo json_encode(['success' => false, 'message' => 'No hay sesión activa.']);
        }
    }
}
