<?php
// Archivo: /app/models/Producto.php

require_once __DIR__ . '/../../config/Database.php';

class Producto
{
    private $conn;
    private $table_name = "productos";
    private $images_table = "producto_imagenes";
    private $codes_table = "producto_codigos";
    private $inventory_table = "inventario_sucursal";
    private $special_prices_table = "cliente_precios_especiales";
    private $movements_table = "movimientos_inventario";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getStockByBranch($id_producto, $id_sucursal)
    {
        $sql = "SELECT stock FROM {$this->inventory_table}
            WHERE id_producto = :p AND id_sucursal = :s LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':p', $id_producto, PDO::PARAM_INT);
        $stmt->bindValue(':s', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['stock'] : 0;
    }


    private function getProductImages($productId)
    {
        $query = "SELECT id, url_imagen, orden FROM " . $this->images_table . " WHERE producto_id = :producto_id ORDER BY orden ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':producto_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($images as &$image) {
            $filePath = __DIR__ . '/../../public' . $image['url_imagen'];
            if (file_exists($filePath)) {
                $imageData = file_get_contents($filePath);
                $mimeType = mime_content_type($filePath);
                $image['base64'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                $image['filename'] = basename($image['url_imagen']);
            }
        }
        return $images;
    }

    /**
     * CORREGIDO: Gestiona las imágenes de forma inteligente.
     * Compara la lista de imágenes final con las existentes y solo realiza los cambios necesarios.
     */
    private function manageProductImages($productId, $finalImageNames)
    {
        if (!is_array($finalImageNames)) {
            $finalImageNames = [];
        }
        $finalImageBasenames = array_map('basename', $finalImageNames);

        // 1. Obtener imágenes existentes de la BD en un formato [nombre_archivo => id].
        $stmt_get = $this->conn->prepare("SELECT id, url_imagen FROM " . $this->images_table . " WHERE producto_id = :producto_id");
        $stmt_get->execute([':producto_id' => $productId]);
        $existingImagesData = $stmt_get->fetchAll(PDO::FETCH_ASSOC);

        $existingImageBasenames = [];
        foreach ($existingImagesData as $img) {
            $existingImageBasenames[basename($img['url_imagen'])] = $img['id'];
        }

        // 2. Identificar y eliminar imágenes que ya no están en la lista final.
        $filenamesToDelete = array_diff_key($existingImageBasenames, array_flip($finalImageBasenames));
        if (!empty($filenamesToDelete)) {
            $idsToDelete = array_values($filenamesToDelete);
            $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));

            // Eliminar de la base de datos
            $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->images_table . " WHERE id IN ($placeholders)");
            $stmt_delete->execute($idsToDelete);

            // Eliminar archivos físicos del servidor
            foreach (array_keys($filenamesToDelete) as $filename) {
                $filePath = __DIR__ . '/../../public/img/productos/' . $filename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        // 3. Insertar imágenes nuevas y actualizar el orden de todas.
        $orden = 0;
        $stmt_insert = $this->conn->prepare("INSERT INTO " . $this->images_table . " (producto_id, url_imagen, orden) VALUES (:producto_id, :url_imagen, :orden)");
        $stmt_update_order = $this->conn->prepare("UPDATE " . $this->images_table . " SET orden = :orden WHERE id = :id");

        foreach ($finalImageBasenames as $filename) {
            if (array_key_exists($filename, $existingImageBasenames)) {
                // La imagen ya existe, solo se actualiza su orden.
                $imageId = $existingImageBasenames[$filename];
                $stmt_update_order->execute([':orden' => $orden, ':id' => $imageId]);
            } else {
                // Es una imagen nueva: se mueve de /temp y se inserta en la BD.
                $tempPath = __DIR__ . '/../../public/img/temp/' . $filename;
                if (file_exists($tempPath)) {
                    $finalDir = __DIR__ . '/../../public/img/productos/';
                    if (!is_dir($finalDir))
                        mkdir($finalDir, 0775, true);
                    $finalPath = $finalDir . $filename;

                    if (rename($tempPath, $finalPath)) {
                        $url_db = '/img/productos/' . $filename;
                        $stmt_insert->execute([
                            ':producto_id' => $productId,
                            ':url_imagen' => $url_db,
                            ':orden' => $orden
                        ]);
                    }
                }
            }
            $orden++;
        }
    }

    public function create($data, $id_sucursal_actual)
    {
        $this->conn->beginTransaction();
        try {
            // Normaliza valores numéricos
            $num = function ($k) {
                return isset($k) && $k !== '' ? (float) $k : null;
            };
            $data['costo'] = $num($data['costo'] ?? null);
            $data['precio_menudeo'] = $num($data['precio_menudeo'] ?? null);
            $data['precio_mayoreo'] = $num($data['precio_mayoreo'] ?? null);
            $data['precio_1'] = $num($data['precio_1'] ?? null);
            $data['precio_2'] = $num($data['precio_2'] ?? null);
            $data['precio_3'] = $num($data['precio_3'] ?? null);
            $data['precio_4'] = $num($data['precio_4'] ?? null);
            $data['precio_5'] = $num($data['precio_5'] ?? null);

            $query_producto = "INSERT INTO {$this->table_name}
            (id_categoria, id_marca, nombre, descripcion, sku, costo,
             precio_menudeo, precio_mayoreo, precio_1, precio_2, precio_3, precio_4, precio_5)
            VALUES
            (:id_categoria, :id_marca, :nombre, :descripcion, :sku, :costo,
             :precio_menudeo, :precio_mayoreo, :precio_1, :precio_2, :precio_3, :precio_4, :precio_5)";
            $stmt = $this->conn->prepare($query_producto);
            $stmt->bindParam(':id_categoria', $data['id_categoria']);
            $stmt->bindParam(':id_marca', $data['id_marca']);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':sku', $data['sku']);
            $stmt->bindParam(':costo', $data['costo']);
            $stmt->bindParam(':precio_menudeo', $data['precio_menudeo']);
            $stmt->bindParam(':precio_mayoreo', $data['precio_mayoreo']);
            $stmt->bindParam(':precio_1', $data['precio_1']);
            $stmt->bindParam(':precio_2', $data['precio_2']);
            $stmt->bindParam(':precio_3', $data['precio_3']);
            $stmt->bindParam(':precio_4', $data['precio_4']);
            $stmt->bindParam(':precio_5', $data['precio_5']);
            $stmt->execute();
            $id_producto_nuevo = $this->conn->lastInsertId();

            // Códigos de barras
            if (!empty($data['codigos_barras']) && is_array($data['codigos_barras'])) {
                $query_cod = "INSERT INTO {$this->codes_table} (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
                $stmt_cod = $this->conn->prepare($query_cod);
                foreach ($data['codigos_barras'] as $codigo) {
                    $codigo = trim($codigo);
                    if ($codigo !== '') {
                        $stmt_cod->execute([':id_producto' => $id_producto_nuevo, ':codigo_barras' => $codigo]);
                    }
                }
            }

            // Imágenes
            if (isset($data['imagenes'])) {
                $this->manageProductImages($id_producto_nuevo, $data['imagenes']);
            }

            // Inventario inicial para todas las sucursales
            foreach ($this->getAllSucursalIds() as $id_sucursal) {
                $stock_inicial = isset($data['stock']) ? (int) $data['stock'] : 0;
                $query_inv = "INSERT INTO {$this->inventory_table} (id_producto, id_sucursal, stock, stock_minimo)
                          VALUES (:id_producto, :id_sucursal, :stock, :stock_minimo)
                          ON DUPLICATE KEY UPDATE stock = VALUES(stock), stock_minimo = VALUES(stock_minimo)";
                $stmt_inv = $this->conn->prepare($query_inv);
                $stmt_inv->execute([
                    ':id_producto' => $id_producto_nuevo,
                    ':id_sucursal' => $id_sucursal,
                    ':stock' => $id_sucursal == $id_sucursal_actual ? $stock_inicial : 0,
                    ':stock_minimo' => (int) ($data['stock_minimo'] ?? 0)
                ]);
            }

            $this->conn->commit();
            return $id_producto_nuevo;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function update($id, $data, $id_sucursal)
    {
        $this->conn->beginTransaction();
        try {
            // Normaliza valores numéricos
            $num = function ($k) {
                return isset($k) && $k !== '' ? (float) $k : null;
            };
            $data['costo'] = $num($data['costo'] ?? null);
            $data['precio_menudeo'] = $num($data['precio_menudeo'] ?? null);
            $data['precio_mayoreo'] = $num($data['precio_mayoreo'] ?? null);
            $data['precio_1'] = $num($data['precio_1'] ?? null);
            $data['precio_2'] = $num($data['precio_2'] ?? null);
            $data['precio_3'] = $num($data['precio_3'] ?? null);
            $data['precio_4'] = $num($data['precio_4'] ?? null);
            $data['precio_5'] = $num($data['precio_5'] ?? null);

            $query_producto = "UPDATE {$this->table_name}
            SET id_categoria = :id_categoria,
                id_marca     = :id_marca,
                nombre       = :nombre,
                descripcion  = :descripcion,
                sku          = :sku,
                costo        = :costo,
                precio_menudeo = :precio_menudeo,
                precio_mayoreo = :precio_mayoreo,
                precio_1 = :precio_1,
                precio_2 = :precio_2,
                precio_3 = :precio_3,
                precio_4 = :precio_4,
                precio_5 = :precio_5
            WHERE id = :id";
            $stmt = $this->conn->prepare($query_producto);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':id_categoria', $data['id_categoria']);
            $stmt->bindParam(':id_marca', $data['id_marca']);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':sku', $data['sku']);
            $stmt->bindParam(':costo', $data['costo']);
            $stmt->bindParam(':precio_menudeo', $data['precio_menudeo']);
            $stmt->bindParam(':precio_mayoreo', $data['precio_mayoreo']);
            $stmt->bindParam(':precio_1', $data['precio_1']);
            $stmt->bindParam(':precio_2', $data['precio_2']);
            $stmt->bindParam(':precio_3', $data['precio_3']);
            $stmt->bindParam(':precio_4', $data['precio_4']);
            $stmt->bindParam(':precio_5', $data['precio_5']);
            $stmt->execute();

            // Stock y mínimos
            if (isset($data['stock'])) {
                $query_inv = "UPDATE {$this->inventory_table}
                          SET stock = :stock, stock_minimo = :stock_minimo
                          WHERE id_producto = :id_producto AND id_sucursal = :id_sucursal";
                $stmt_inv = $this->conn->prepare($query_inv);
                $stmt_inv->execute([
                    ':stock' => (int) $data['stock'],
                    ':stock_minimo' => (int) ($data['stock_minimo'] ?? 0),
                    ':id_producto' => $id,
                    ':id_sucursal' => $id_sucursal
                ]);
            }

            // Códigos de barras: borra y re-inserta la lista enviada
            $this->conn->prepare("DELETE FROM {$this->codes_table} WHERE id_producto = :id")->execute([':id' => $id]);
            if (!empty($data['codigos_barras']) && is_array($data['codigos_barras'])) {
                $query_cod = "INSERT INTO {$this->codes_table} (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
                $stmt_cod = $this->conn->prepare($query_cod);
                foreach ($data['codigos_barras'] as $codigo) {
                    $codigo = trim($codigo);
                    if ($codigo !== '') {
                        $stmt_cod->execute([':id_producto' => $id, ':codigo_barras' => $codigo]);
                    }
                }
            }

            // Imágenes
            if (isset($data['imagenes'])) {
                $this->manageProductImages($id, $data['imagenes']);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }


    public function getById($id, $id_sucursal)
    {
        // CONSULTA CORREGIDA: Se añaden todos los campos del producto, incluyendo id_categoria e id_marca.
        $query = "SELECT 
                p.id, p.sku, p.nombre, p.descripcion,
                p.id_categoria, p.id_marca,
                p.costo, p.precio_menudeo, p.precio_mayoreo,
                p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5,
                inv.stock, 
                inv.stock_minimo, 
                GROUP_CONCAT(pc.codigo_barras) as codigos_barras 
              FROM " . $this->table_name . " p 
              LEFT JOIN " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal 
              LEFT JOIN " . $this->codes_table . " pc ON p.id = pc.id_producto 
              WHERE p.id = :id 
              GROUP BY 
                p.id, p.sku, p.nombre, p.descripcion,
                p.id_categoria, p.id_marca,
                p.costo, p.precio_menudeo, p.precio_mayoreo,
                p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5,
                inv.stock, 
                inv.stock_minimo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            $producto['codigos_barras'] = !empty($producto['codigos_barras']) ? explode(',', $producto['codigos_barras']) : [];
            $producto['imagenes'] = $this->getProductImages($id);
        }

        return $producto;
    }
    public function delete($id)
    {
        $this->conn->beginTransaction();
        try {
            $images = $this->getProductImages($id);
            foreach ($images as $img) {
                if (isset($img['url_imagen'])) {
                    $filePath = __DIR__ . '/../../public' . $img['url_imagen'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }

            $stmt_delete_images = $this->conn->prepare("DELETE FROM " . $this->images_table . " WHERE producto_id = :id");
            $stmt_delete_images->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_delete_images->execute();

            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getAllServerSide($id_sucursal, $params)
    {
        $baseQuery = "
        FROM {$this->table_name} p
        LEFT JOIN categorias c ON c.id = p.id_categoria
        LEFT JOIN {$this->inventory_table} inv ON inv.id_producto = p.id AND inv.id_sucursal = :id_sucursal
        LEFT JOIN {$this->codes_table} pc ON pc.id_producto = p.id
    ";

        $where = "WHERE p.activo = 1";
        $bindings = [':id_sucursal' => (int) $id_sucursal];

        // Términos para AND: terms[] o search[value] separado por + o espacios
        $terms = [];
        if (isset($params['terms']) && is_array($params['terms'])) {
            $terms = array_values(array_filter(array_map('trim', $params['terms']), fn($t) => $t !== ''));
        } else {
            $sv = $params['search']['value'] ?? '';
            if (is_string($sv) && $sv !== '') {
                $terms = preg_split('/\s*\+\s*|\s+/', $sv);
                $terms = array_values(array_filter(array_map('trim', $terms), fn($t) => $t !== ''));
            }
        }

        if ($terms) {
            $cols = ['p.nombre', 'p.sku', 'pc.codigo_barras'];
            $blocks = [];
            foreach ($terms as $i => $t) {
                $pi = ":t{$i}";
                $or = [];
                foreach ($cols as $c) {
                    $or[] = "$c LIKE $pi";
                }
                $blocks[] = '(' . implode(' OR ', $or) . ')';
                $bindings[$pi] = "%{$t}%";
            }
            $where .= ' AND ' . implode(' AND ', $blocks);
        }

        // Filtros por columna: columns[i][search][value]
        $colSearches = $params['columns'] ?? [];
        $map = [
            0 => 'p.sku',
            1 => 'p.nombre',
            2 => 'pc.codigo_barras',
            3 => 'c.nombre',
            // 4 => 'inv.stock',      // opcional si deseas filtrar numérico
            // 5 => 'p.precio_menudeo'
        ];
        foreach ($map as $i => $col) {
            $val = isset($colSearches[$i]['search']['value']) ? trim($colSearches[$i]['search']['value']) : '';
            if ($val !== '') {
                $k = ":cs{$i}";
                $where .= " AND {$col} LIKE {$k}";
                $bindings[$k] = "%{$val}%";
            }
        }

        // Totales
        $stmtTotal = $this->conn->prepare("SELECT COUNT(DISTINCT p.id) $baseQuery WHERE p.activo = 1");
        $stmtTotal->execute([':id_sucursal' => (int) $id_sucursal]);
        $recordsTotal = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $this->conn->prepare("SELECT COUNT(DISTINCT p.id) $baseQuery $where");
        foreach ($bindings as $k => $v) {
            $stmtFiltered->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtFiltered->execute();
        $recordsFiltered = (int) $stmtFiltered->fetchColumn();

        // Orden
        $order = "ORDER BY p.nombre ASC";
        if (isset($params['order'][0]['column'], $params['order'][0]['dir'])) {
            $idx = (int) $params['order'][0]['column'];
            $dir = strtolower($params['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
            $orderable = [
                0 => 'p.sku',
                1 => 'p.nombre',
                2 => 'pc.codigo_barras',
                3 => 'c.nombre',
                4 => 'inv.stock',
                5 => 'p.precio_menudeo'
            ];
            if (array_key_exists($idx, $orderable)) {
                $order = "ORDER BY {$orderable[$idx]} $dir, p.nombre ASC";
            }
        }

        // Paginación
        $length = isset($params['length']) ? (int) $params['length'] : 10;
        $start = isset($params['start']) ? (int) $params['start'] : 0;
        $limitClause = ($length > 0) ? "LIMIT :start, :length" : "";

        // Datos
        $select = "
        SELECT
            p.id, p.sku, p.nombre, p.costo,
            p.precio_menudeo, p.precio_mayoreo,
            COALESCE(inv.stock, 0) AS stock,
            c.nombre AS categoria_nombre,
            GROUP_CONCAT(DISTINCT pc.codigo_barras) AS codigos_barras
        $baseQuery
        $where
        GROUP BY 
            p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo, 
            stock, categoria_nombre -- <-- AQUÍ ESTÁ LA CORRECCIÓN
        $order
        $limitClause
    ";

        $stmtData = $this->conn->prepare($select);
        foreach ($bindings as $k => $v) {
            $stmtData->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        if ($length > 0) {
            $stmtData->bindValue(':start', max(0, $start), PDO::PARAM_INT);
            $stmtData->bindValue(':length', max(1, $length), PDO::PARAM_INT);
        }
        $stmtData->execute();
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        // ----- TERMINA LA CORRECCIÓN -----

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function getAll($id_sucursal)
    {
        // La consulta SQL corregida
        $query = "SELECT 
                p.id, 
                p.sku, 
                p.nombre, 
                p.costo, 
                p.precio_menudeo, 
                p.precio_mayoreo, 
                p.activo, 
                c.nombre as categoria_nombre, 
                m.nombre as marca_nombre, 
                inv.stock, 
                inv.stock_minimo, 
                GROUP_CONCAT(pc.codigo_barras SEPARATOR ', ') as codigos_barras, 
                (SELECT pi.url_imagen 
                 FROM " . $this->images_table . " pi 
                 WHERE pi.producto_id = p.id 
                 ORDER BY pi.orden ASC, pi.id ASC 
                 LIMIT 1) AS imagen_url 
              FROM productos p 
              LEFT JOIN categorias c ON p.id_categoria = c.id 
              LEFT JOIN marcas m ON p.id_marca = m.id 
              LEFT JOIN inventario_sucursal inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal 
              LEFT JOIN " . $this->codes_table . " pc ON p.id = pc.id_producto 
              GROUP BY 
                p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo, p.activo, 
                categoria_nombre, marca_nombre, inv.stock, inv.stock_minimo -- <-- AQUÍ ESTÁ LA CORRECCIÓN
              ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByBarcodeOrSku($code, $id_sucursal)
    {
        $query = "SELECT p.*, inv.stock, inv.stock_minimo, (SELECT pi.url_imagen FROM " . $this->images_table . " pi WHERE pi.producto_id = p.id ORDER BY pi.orden ASC, pi.id ASC LIMIT 1) AS imagen_url FROM " . $this->table_name . " p LEFT JOIN " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal LEFT JOIN " . $this->codes_table . " pc ON p.id = pc.id_producto WHERE (p.sku = :code OR pc.codigo_barras = :code) LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getForPOS($id_producto, $id_sucursal, $id_cliente)
    {
        $query = "SELECT p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo, p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5, inv.stock, (SELECT pi.url_imagen FROM " . $this->images_table . " pi WHERE pi.producto_id = p.id ORDER BY pi.orden ASC, pi.id ASC LIMIT 1) AS imagen_url FROM " . $this->table_name . " p LEFT JOIN " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal WHERE p.id = :id_producto LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) {
            return null;
        }
        $precio_especial = null;
        if ($id_cliente != 1) {
            $query_precio = "SELECT precio_especial FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente AND id_producto = :id_producto";
            $stmt_precio = $this->conn->prepare($query_precio);
            $stmt_precio->bindParam(':id_cliente', $id_cliente);
            $stmt_precio->bindParam(':id_producto', $id_producto);
            $stmt_precio->execute();
            $resultado_precio = $stmt_precio->fetch(PDO::FETCH_ASSOC);
            if ($resultado_precio) {
                $precio_especial = $resultado_precio['precio_especial'];
            }
        }
        if ($precio_especial !== null) {
            $producto['precio_final'] = $precio_especial;
            $producto['tipo_precio_aplicado'] = 'Especial';
        } else {
            $producto['precio_final'] = $producto['precio_1'] ?? $producto['precio_menudeo'];
            $producto['tipo_precio_aplicado'] = 'P1';
        }
        return $producto;
    }

    public function updateStock($id_producto, $id_sucursal, $new_stock, $tipo_movimiento, $cantidad_movida, $stock_anterior, $motivo, $referencia_id = null)
    {
        try {
            $query_update_stock = "INSERT INTO " . $this->inventory_table . " (id_producto, id_sucursal, stock) VALUES (:id_producto, :id_sucursal, :new_stock) ON DUPLICATE KEY UPDATE stock = :new_stock_update";
            $stmt_update = $this->conn->prepare($query_update_stock);
            $stmt_update->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt_update->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
            $stmt_update->bindParam(':new_stock', $new_stock);
            $stmt_update->bindParam(':new_stock_update', $new_stock);
            $stmt_update->execute();
            $this->addInventoryMovement($id_producto, $id_sucursal, $_SESSION['user_id'], $tipo_movimiento, $cantidad_movida, $stock_anterior, $new_stock, $motivo, $referencia_id);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addInventoryMovement($id_producto, $id_sucursal, $id_usuario, $tipo_movimiento, $cantidad, $stock_anterior, $stock_nuevo, $motivo = null, $referencia_id = null)
    {
        try {
            $query = "INSERT INTO " . $this->movements_table . " (id_producto, id_sucursal, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia_id) VALUES (:id_producto, :id_sucursal, :id_usuario, :tipo_movimiento, :cantidad, :stock_anterior, :stock_nuevo, :motivo, :referencia_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_movimiento', $tipo_movimiento);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':stock_anterior', $stock_anterior);
            $stmt->bindParam(':stock_nuevo', $stock_nuevo);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':referencia_id', $referencia_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getInventoryMovements($id_sucursal)
    {
        $query = "SELECT mov.fecha, p.nombre as producto_nombre, mov.tipo_movimiento, mov.cantidad, mov.stock_anterior, mov.stock_nuevo, mov.motivo, u.nombre as usuario_nombre, mov.referencia_id FROM " . $this->movements_table . " mov JOIN productos p ON mov.id_producto = p.id JOIN usuarios u ON mov.id_usuario = u.id WHERE mov.id_sucursal = :id_sucursal ORDER BY mov.fecha DESC LIMIT 100";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findStockInAllBranches($searchTerm)
    {
        // Consulta SQL corregida con el GROUP BY extendido
        $query = "SELECT 
                p.id AS producto_id, 
                p.sku, 
                p.nombre AS producto_nombre, 
                s.nombre AS sucursal_nombre, 
                inv.stock 
              FROM {$this->table_name} p 
              JOIN {$this->inventory_table} inv ON p.id = inv.id_producto 
              JOIN sucursales s ON inv.id_sucursal = s.id 
              LEFT JOIN {$this->codes_table} pc ON p.id = pc.id_producto 
              WHERE (p.sku LIKE :searchTerm OR p.nombre LIKE :searchTerm OR pc.codigo_barras LIKE :searchTerm) AND p.activo = 1 
              GROUP BY 
                p.id, p.sku, p.nombre, s.id, s.nombre, inv.stock -- <-- AQUÍ ESTÁ LA CORRECCIÓN
              ORDER BY p.nombre, s.nombre";

        $stmt = $this->conn->prepare($query);
        $likeTerm = "%{$searchTerm}%";
        $stmt->bindParam(':searchTerm', $likeTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function search($id_sucursal, $q, $limit = 50, $offset = 0)
    {
        $like = '%' . $q . '%';
        $query = "SELECT p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo, p.activo, c.nombre as categoria_nombre, m.nombre as marca_nombre, inv.stock, inv.stock_minimo, GROUP_CONCAT(pc.codigo_barras SEPARATOR ', ') as codigos_barras, (SELECT pi.url_imagen FROM " . $this->images_table . " pi WHERE pi.producto_id = p.id ORDER BY pi.orden ASC, pi.id ASC LIMIT 1) AS imagen_url FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id LEFT JOIN marcas m ON p.id_marca = m.id LEFT JOIN " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal LEFT JOIN " . $this->codes_table . " pc ON p.id = pc.id_producto WHERE (p.nombre LIKE :like OR p.sku LIKE :like OR pc.codigo_barras LIKE :like) GROUP BY p.id ORDER BY p.nombre ASC LIMIT :offset, :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindParam(':like', $like);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Búsqueda avanzada: AND entre términos, OR sobre columnas (nombre, sku, código de barras).
     * Usa producto_codigos.codigo_barras. No toca la lógica de search() existente.
     */
    public function searchAND(int $id_sucursal, array $terms, int $limit = 50, int $offset = 0): array
    {
        // Normalizar términos
        $clean = [];
        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ($t !== '')
                $clean[] = $t;
        }
        $terms = array_values(array_unique($clean));
        if (count($terms) === 0) {
            return $this->search($id_sucursal, '', $limit, $offset);
        }
        if (count($terms) === 1) {
            return $this->search($id_sucursal, $terms[0], $limit, $offset);
        }

        // Columnas objetivo
        // p = productos, pc = producto_codigos, inv = inventario_sucursal
        $cols = ['p.nombre', 'p.sku', 'pc.codigo_barras'];

        // Construir (col LIKE :t0 OR ...) AND (col LIKE :t1 OR ...) ...
        $andBlocks = [];
        $params = [];
        foreach ($terms as $i => $term) {
            $pi = ':t' . $i;
            $or = [];
            foreach ($cols as $c) {
                $or[] = "$c LIKE $pi";
            }
            $andBlocks[] = '(' . implode(' OR ', $or) . ')';
            $params[$pi] = '%' . $term . '%';
        }
        $whereTerms = implode(' AND ', $andBlocks);

        $sql = "
        SELECT
            p.id, p.sku, p.nombre, p.costo,
            p.precio_menudeo, p.precio_mayoreo,
            p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5,
            COALESCE(inv.stock, 0)        AS stock,
            COALESCE(inv.stock_minimo, 0) AS stock_minimo,
            GROUP_CONCAT(DISTINCT pc.codigo_barras) AS codigos_barras
        FROM {$this->table_name} p
        LEFT JOIN {$this->inventory_table} inv
               ON inv.id_producto = p.id AND inv.id_sucursal = :id_sucursal
        LEFT JOIN {$this->codes_table} pc
               ON pc.id_producto = p.id
        WHERE p.activo = 1
          AND $whereTerms
        GROUP BY p.id
        ORDER BY p.nombre ASC
        LIMIT :offset, :limit
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int) $offset), PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, (int) $limit), PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    private function getAllSucursalIds()
    {
        $stmt = $this->conn->prepare("SELECT id FROM sucursales");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function createInitialStockRecord($id_producto, $id_sucursal, $stock = 0, $stock_minimo = 0)
    {
        $query = "INSERT INTO " . $this->inventory_table . " (id_producto, id_sucursal, stock, stock_minimo) VALUES (:id_producto, :id_sucursal, :stock, :stock_minimo) ON DUPLICATE KEY UPDATE stock = VALUES(stock), stock_minimo = VALUES(stock_minimo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':stock_minimo', $stock_minimo);
        return $stmt->execute();
    }

    /** Lista simple para selects/autocomplete */
    public function getAllSimple()
    {
        $query = "SELECT id, nombre, sku FROM " . $this->table_name . " WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Búsqueda simple por nombre o SKU */
    public function searchSimpleByNameOrSku($term, $limit = 50)
    {
        $like = "%" . $term . "%";
        $query = "SELECT id, nombre, sku FROM " . $this->table_name . " 
              WHERE activo = 1 AND (nombre LIKE :term OR sku LIKE :term)
              ORDER BY nombre ASC
              LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':term', $like, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
