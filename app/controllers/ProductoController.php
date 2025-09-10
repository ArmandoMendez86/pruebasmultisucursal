<?php
// Archivo: /app/controllers/ProductoController.php

require_once __DIR__ . '/../models/Producto.php';

class ProductoController
{
    private $productoModel;

    public function __construct()
    {
        $this->productoModel = new Producto();
    }

    public function uploadProductImage()
    {
        header('Content-Type: text/plain');

        // Se busca 'product_images' que es lo que manda FilePond.
        if (!isset($_FILES['product_images'])) {
            http_response_code(400);
            echo "Error: No se recibió el campo 'product_images'.";
            return;
        }

        $file = $_FILES['product_images'];
        $tempPath = $file['tmp_name'];

        $uploadDir = __DIR__ . '/../../public/img/temp/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true)) {
                http_response_code(500);
                echo "Error: No se pudo crear el directorio temporal.";
                return;
            }
        }

        // Se sanea el nombre del archivo para mayor seguridad.
        $safeName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($file['name']));
        $fileName = uniqid('prod_') . '_' . $safeName;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($tempPath, $destPath)) {
            http_response_code(200);
            echo $fileName;
        } else {
            http_response_code(500);
            echo "Error al mover el archivo subido.";
        }
    }

    public function deleteProductImage()
    {
        $fileName = file_get_contents('php://input');
        if (empty($fileName)) {
            http_response_code(400);
            return;
        }

        $filePath = __DIR__ . '/../../public/img/temp/' . basename($fileName);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        http_response_code(204); // No Content
    }

    public function getProductForPOS()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        if (!isset($_GET['id_producto']) || !isset($_GET['id_cliente'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros (producto o cliente).']);
            return;
        }

        $id_producto = filter_var($_GET['id_producto'], FILTER_SANITIZE_NUMBER_INT);
        $id_cliente = filter_var($_GET['id_cliente'], FILTER_SANITIZE_NUMBER_INT);
        $id_sucursal = $_SESSION['branch_id'];

        try {
            $producto = $this->productoModel->getForPOS($id_producto, $id_sucursal, $id_cliente);

            if ($producto) {
                echo json_encode(['success' => true, 'data' => $producto]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado o sin stock.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    public function getAll()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $id_sucursal = $_SESSION['branch_id'];
            $productos = $this->productoModel->getAll($id_sucursal);
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los productos: ' . $e->getMessage()]);
        }
    }

    /*  public function getProductsServerSide()
     {
         header('Content-Type: application/json');
         if (!isset($_SESSION['user_id'])) {
             http_response_code(403);
             echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
             return;
         }

         $params = $_REQUEST;
         $id_sucursal = $_SESSION['branch_id'];

         try {
             $result = $this->productoModel->getAllServerSide($id_sucursal, $params);

             $json_data = [
                 "draw" => intval($params['draw']),
                 "recordsTotal" => intval($result['recordsTotal']),
                 "recordsFiltered" => intval($result['recordsFiltered']),
                 "data" => $result['data']
             ];

             echo json_encode($json_data);
         } catch (Exception $e) {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
         }
     } */
    public function getProductsServerSide()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            return;
        }

        try {
            $id_sucursal = $_SESSION['branch_id'] ?? 0;
            $params = $_POST; // DataTables envía draw, start, length, order, search, columns, y ahora terms[]
            $result = $this->productoModel->getAllServerSide($id_sucursal, $params);

            echo json_encode([
                'draw' => isset($params['draw']) ? (int) $params['draw'] : 0,
                'recordsTotal' => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data' => $result['data']
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'draw' => isset($_POST['draw']) ? (int) $_POST['draw'] : 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }


    public function getByBarcode()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (!isset($_GET['code'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Código no proporcionado.']);
            return;
        }

        $code = htmlspecialchars(strip_tags($_GET['code']));
        $id_sucursal = $_SESSION['branch_id'];
        $producto = $this->productoModel->findByBarcodeOrSku($code, $id_sucursal);

        if ($producto) {
            echo json_encode(['success' => true, 'data' => $producto]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }
    }

    public function create()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre']) || !isset($data['precio_menudeo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nombre y precio menudeo son requeridos.']);
            return;
        }

        try {
            $id_sucursal_actual = $_SESSION['branch_id'];
            $newProductId = $this->productoModel->create($data, $id_sucursal_actual);

            if ($newProductId) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Producto creado y asignado a todas las sucursales.', 'id' => $newProductId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el producto.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'sku') !== false) {
                echo json_encode(['success' => false, 'message' => 'Error: El SKU ingresado ya existe para otro producto.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
            }
        }
    }

    public function getById()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado.']);
            return;
        }

        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $id_sucursal = $_SESSION['branch_id'];
        $producto = $this->productoModel->getById($id, $id_sucursal);

        if ($producto) {
            echo json_encode(['success' => true, 'data' => $producto]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }
    }

    public function update()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado.']);
            return;
        }

        $id = $data['id'];
        $id_sucursal = $_SESSION['branch_id'];
        try {
            if ($this->productoModel->update($id, $data, $id_sucursal)) {
                echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el producto.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = (array) json_decode(file_get_contents('php://input'));
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado.']);
            return;
        }

        $id = $data['id'];
        if ($this->productoModel->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el producto.']);
        }
    }

    public function adjustStock()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id_producto = $data['id_producto'] ?? null;
        $new_stock = $data['new_stock'] ?? null;
        $tipo_movimiento = $data['tipo_movimiento'] ?? 'ajuste';
        $cantidad_movida = $data['cantidad_movida'] ?? 0;
        $motivo = $data['motivo'] ?? 'Ajuste manual';
        $stock_anterior = $data['stock_anterior'] ?? 0;

        if (is_null($id_producto) || is_null($new_stock) || !is_numeric($new_stock)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos de ajuste de stock incompletos o inválidos.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Super' && !empty($data['id_sucursal'])) {
            $id_sucursal = filter_var($data['id_sucursal'], FILTER_SANITIZE_NUMBER_INT);
        }

        try {
            $success = $this->productoModel->updateStock(
                $id_producto,
                $id_sucursal,
                $new_stock,
                $tipo_movimiento,
                $cantidad_movida,
                $stock_anterior,
                $motivo
            );

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Stock ajustado exitosamente y movimiento registrado.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo ajustar el stock.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al ajustar stock: ' . $e->getMessage()]);
        }
    }

    public function getInventoryMovements()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        try {
            $movements = $this->productoModel->getInventoryMovements($id_sucursal);
            echo json_encode(['success' => true, 'data' => $movements]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener el historial de movimientos: ' . $e->getMessage()]);
        }
    }

    public function getStockAcrossBranches()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        if (!isset($_GET['term']) || empty(trim($_GET['term']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Término de búsqueda no proporcionado.']);
            return;
        }

        $searchTerm = trim(htmlspecialchars(strip_tags($_GET['term'])));
        try {
            $results = $this->productoModel->findStockInAllBranches($searchTerm);
            $groupedResults = [];
            foreach ($results as $row) {
                $sku = $row['sku'];
                if (!isset($groupedResults[$sku])) {
                    $groupedResults[$sku] = [
                        'sku' => $sku,
                        'producto_nombre' => $row['producto_nombre'],
                        'sucursales' => []
                    ];
                }
                $groupedResults[$sku]['sucursales'][] = [
                    'nombre' => $row['sucursal_nombre'],
                    'stock' => (int) $row['stock']
                ];
            }
            echo json_encode(['success' => true, 'data' => array_values($groupedResults)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    public function searchProducts()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? max(1, min(200, (int) $_GET['limit'])) : 50;
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

        // terms[] explícito o derivado de q por "+" o espacios
        $terms = [];
        if (isset($_GET['terms']) && is_array($_GET['terms'])) {
            $terms = array_values(array_filter(array_map('trim', $_GET['terms']), fn($t) => $t !== ''));
        } elseif ($q !== '') {
            $terms = preg_split('/\s*\+\s*|\s+/', $q);
            $terms = array_values(array_filter(array_map('trim', $terms), fn($t) => $t !== ''));
        }

        try {
            $id_sucursal = $_SESSION['branch_id'] ?? 0;

            if (count($terms) <= 1) {
                // Compatibilidad: 0-1 término usa la búsqueda existente
                $productos = $this->productoModel->search($id_sucursal, $q, $limit, $offset);
            } else {
                // 2+ términos => AND entre términos, OR en columnas
                $productos = $this->productoModel->searchAND($id_sucursal, $terms, $limit, $offset);
            }

            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar productos',
                'error' => $e->getMessage(), // útil para depurar
            ]);
        }
    }


}