<?php
// Archivo: /app/models/Venta.php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Cliente.php';

class Venta
{
    private $conn;
    private $productoModel;
    private $clienteModel;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
        $this->productoModel = new Producto();
        $this->clienteModel = new Cliente();
    }

    public function create($data)
    {
        try {
            $estadoVenta = $data['estado'] ?? 'Completada';
            $idDireccion = $data['id_direccion_envio'] ?? null;
            $ivaAplicado = $data['iva_aplicado'] ?? 0;
            $paymentsJson = !empty($data['payments']) ? json_encode($data['payments']) : null;

            $stmt_venta = $this->conn->prepare("INSERT INTO ventas (id_cliente, id_usuario, id_sucursal, id_direccion_envio, total, metodo_pago, iva_aplicado, estado) VALUES (:id_cliente, :id_usuario, :id_sucursal, :id_direccion_envio, :total, :metodo_pago, :iva_aplicado, :estado)");
            $stmt_venta->bindParam(':id_cliente', $data['id_cliente']);
            $stmt_venta->bindParam(':id_usuario', $data['id_usuario']);
            $stmt_venta->bindParam(':id_sucursal', $data['id_sucursal']);
            $stmt_venta->bindParam(':id_direccion_envio', $idDireccion);
            $stmt_venta->bindParam(':total', $data['total']);
            $stmt_venta->bindParam(':metodo_pago', $paymentsJson);
            $stmt_venta->bindParam(':iva_aplicado', $ivaAplicado, PDO::PARAM_INT);
            $stmt_venta->bindParam(':estado', $estadoVenta);
            $stmt_venta->execute();
            $idVenta = $this->conn->lastInsertId();

            // INSERT de detalles con costo y descripción
            $stmt_detalle = $this->conn->prepare("
            INSERT INTO venta_detalles
                (id_venta, id_producto, cantidad, precio_unitario, subtotal, costo, descripcion)
            VALUES
                (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal, :costo, :descripcion)
            ");

            foreach ($data['cart'] as $item) {
                $precio_unitario = $item['precio_final'];
                $subtotal_item = $item['quantity'] * $precio_unitario;

                // costo siempre desde catálogo
                $producto = $this->productoModel->getById($item['id'], $data['id_sucursal']);
                $costo = isset($producto['costo']) ? (float) $producto['costo'] : 0;

                // descripción opcional
                $descripcion = (isset($item['descripcion']) && $item['descripcion'] !== '') ? $item['descripcion'] : null;

                $stmt_detalle->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
                $stmt_detalle->bindParam(':id_producto', $item['id'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':cantidad', $item['quantity']);
                $stmt_detalle->bindParam(':precio_unitario', $precio_unitario);
                $stmt_detalle->bindParam(':subtotal', $subtotal_item);
                $stmt_detalle->bindParam(':costo', $costo);
                $stmt_detalle->bindParam(':descripcion', $descripcion);
                $stmt_detalle->execute();
            }

            return $idVenta;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //Homologando a triggers de venta a credito, despues implementar en base de datos
    public function update($data)
    {
        try {
            $idVenta = (int) $data['id_venta'];
            $estadoVenta = $data['estado'] ?? 'Completada';
            $idDireccion = $data['id_direccion_envio'] ?? null;
            $idSucursal = (int) $data['id_sucursal'];
            $ivaAplicado = (int) ($data['iva_aplicado'] ?? 0);
            $payments = isset($data['payments']) && is_array($data['payments']) ? $data['payments'] : [];
            $paymentsJson = !empty($payments) ? json_encode($payments) : null;

            // Bloquea la venta para actualizarla con seguridad
            $stmt_current_sale = $this->conn->prepare("SELECT estado FROM ventas WHERE id = :id_venta FOR UPDATE");
            $stmt_current_sale->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
            $stmt_current_sale->execute();
            $currentSale = $stmt_current_sale->fetch(PDO::FETCH_ASSOC);

            if (!$currentSale) {
                throw new Exception("Venta no encontrada para actualizar.");
            }

            // Borra y re-inserta detalles
            $stmt_delete_details = $this->conn->prepare("DELETE FROM venta_detalles WHERE id_venta = :id_venta");
            $stmt_delete_details->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
            $stmt_delete_details->execute();

            // Actualiza cabecera
            $stmt_venta = $this->conn->prepare("
            UPDATE ventas
            SET id_cliente = :id_cliente,
                id_usuario = :id_usuario,
                id_sucursal = :id_sucursal,
                id_direccion_envio = :id_direccion_envio,
                total = :total,
                metodo_pago = :metodo_pago,
                iva_aplicado = :iva_aplicado,
                estado = :estado
            WHERE id = :id_venta
        ");
            $stmt_venta->bindParam(':id_cliente', $data['id_cliente'], PDO::PARAM_INT);
            $stmt_venta->bindParam(':id_usuario', $data['id_usuario'], PDO::PARAM_INT);
            $stmt_venta->bindParam(':id_sucursal', $idSucursal, PDO::PARAM_INT);
            $stmt_venta->bindParam(':id_direccion_envio', $idDireccion, PDO::PARAM_INT);
            $stmt_venta->bindParam(':total', $data['total']);
            $stmt_venta->bindParam(':metodo_pago', $paymentsJson);
            $stmt_venta->bindParam(':iva_aplicado', $ivaAplicado, PDO::PARAM_INT);
            $stmt_venta->bindParam(':estado', $estadoVenta);
            $stmt_venta->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
            $stmt_venta->execute();

            // Reinsertar detalles
            $stmt_detalle = $this->conn->prepare("
            INSERT INTO venta_detalles
                (id_venta, id_producto, cantidad, precio_unitario, subtotal, costo, descripcion)
            VALUES
                (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal, :costo, :descripcion)
        ");

            foreach ($data['cart'] as $item) {
                $precio_unitario = $item['precio_final'];
                $subtotal_item = $item['quantity'] * $precio_unitario;

                // costo desde catálogo
                $producto = $this->productoModel->getById($item['id'], $idSucursal);
                $costo = isset($producto['costo']) ? (float) $producto['costo'] : 0;

                $descripcion = (isset($item['descripcion']) && $item['descripcion'] !== '') ? $item['descripcion'] : null;

                $stmt_detalle->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
                $stmt_detalle->bindParam(':id_producto', $item['id'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':cantidad', $item['quantity']);
                $stmt_detalle->bindParam(':precio_unitario', $precio_unitario);
                $stmt_detalle->bindParam(':subtotal', $subtotal_item);
                $stmt_detalle->bindParam(':costo', $costo);
                $stmt_detalle->bindParam(':descripcion', $descripcion);
                $stmt_detalle->execute();
            }

            // === NUEVO: espejar la lógica del trigger para UPDATE ===
            if ($estadoVenta === 'Completada' && !empty($payments)) {
                $idCliente = (int) $data['id_cliente'];

                // Sumar monto de "Crédito" (puede venir combinado con otros métodos)
                $creditPaymentAmount = 0.0;
                foreach ($payments as $p) {
                    if (isset($p['method']) && $p['method'] === 'Crédito') {
                        $creditPaymentAmount += (float) $p['amount'];
                    }
                }

                if ($creditPaymentAmount > 0) {
                    // Upsert en ventas_credito para esta venta
                    $existsStmt = $this->conn->prepare("SELECT id FROM ventas_credito WHERE id_venta = :id_venta LIMIT 1");
                    $existsStmt->execute([':id_venta' => $idVenta]);
                    $existingId = $existsStmt->fetchColumn();

                    if ($existingId) {
                        $upd = $this->conn->prepare("
                        UPDATE ventas_credito
                        SET id_cliente = :id_cliente,
                            monto_credito = :monto,
                            saldo_pendiente = :monto,
                            estatus = 'Abierta'
                        WHERE id_venta = :id_venta
                    ");
                        $upd->execute([
                            ':id_cliente' => $idCliente,
                            ':monto' => $creditPaymentAmount,
                            ':id_venta' => $idVenta
                        ]);
                    } else {
                        $ins = $this->conn->prepare("
                        INSERT INTO ventas_credito (id_venta, id_cliente, monto_credito, saldo_pendiente, estatus)
                        VALUES (:id_venta, :id_cliente, :monto, :monto, 'Abierta')
                    ");
                        $ins->execute([
                            ':id_venta' => $idVenta,
                            ':id_cliente' => $idCliente,
                            ':monto' => $creditPaymentAmount
                        ]);
                    }

                    // Resincronizar cache de deuda del cliente (seguro ante duplicados/ediciones)
                    $resync = $this->conn->prepare("
                    UPDATE clientes c
                    LEFT JOIN (
                        SELECT id_cliente, SUM(saldo_pendiente) AS deuda
                        FROM ventas_credito
                        GROUP BY id_cliente
                    ) x ON x.id_cliente = c.id
                    SET c.deuda_actual = COALESCE(x.deuda, 0)
                    WHERE c.id = :id_cliente
                ");
                    $resync->execute([':id_cliente' => $idCliente]);
                }
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * NUEVA FUNCIÓN: Duplica una venta existente y sus detalles.
     * @param int $id_venta_original El ID de la venta a duplicar.
     * @param int $id_usuario El ID del usuario que realiza la duplicación.
     * @param int $id_sucursal El ID de la sucursal actual.
     * @return int|false El ID de la nueva venta creada, o false si falla.
     * @throws Exception Si ocurre un error en la base de datos.
     */
    public function duplicateById($id_venta_original, $id_usuario, $id_sucursal, $id_cliente_destino = null)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Obtener la cabecera de la venta original
            $stmt_original_header = $this->conn->prepare("SELECT * FROM ventas WHERE id = :id_venta");
            $stmt_original_header->bindParam(':id_venta', $id_venta_original);
            $stmt_original_header->execute();
            $original_header = $stmt_original_header->fetch(PDO::FETCH_ASSOC);

            if (!$original_header) {
                throw new Exception("La venta original no existe.");
            }

            // 2. Crear una nueva cabecera de venta
            $stmt_new_venta = $this->conn->prepare(
                "INSERT INTO ventas (id_cliente, id_usuario, id_sucursal, id_direccion_envio, total, iva_aplicado, estado) 
             VALUES (:id_cliente, :id_usuario, :id_sucursal, :id_direccion_envio, :total, :iva_aplicado, 'Pendiente')"
            );

            // --- CORRECCIÓN AQUÍ ---
            $stmt_new_venta->bindValue(':id_cliente', $id_cliente_destino !== null ? $id_cliente_destino : $original_header['id_cliente']);

            $stmt_new_venta->bindParam(':id_usuario', $id_usuario); // Usuario actual
            $stmt_new_venta->bindParam(':id_sucursal', $id_sucursal); // Sucursal actual
            $stmt_new_venta->bindParam(':id_direccion_envio', $original_header['id_direccion_envio']);
            $stmt_new_venta->bindParam(':total', $original_header['total']);
            $stmt_new_venta->bindParam(':iva_aplicado', $original_header['iva_aplicado'], PDO::PARAM_INT);
            $stmt_new_venta->execute();
            $new_sale_id = $this->conn->lastInsertId();

            // 3. Obtener los detalles de la venta original
            $stmt_original_details = $this->conn->prepare("SELECT * FROM venta_detalles WHERE id_venta = :id_venta");
            $stmt_original_details->bindParam(':id_venta', $id_venta_original);
            $stmt_original_details->execute();
            $original_details = $stmt_original_details->fetchAll(PDO::FETCH_ASSOC);

            // 4. Insertar los detalles en la nueva venta
            $stmt_new_detail = $this->conn->prepare(
                "INSERT INTO venta_detalles (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
             VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)"
            );

            foreach ($original_details as $item) {
                $stmt_new_detail->bindParam(':id_venta', $new_sale_id);
                $stmt_new_detail->bindParam(':id_producto', $item['id_producto']);
                $stmt_new_detail->bindParam(':cantidad', $item['cantidad']);
                $stmt_new_detail->bindParam(':precio_unitario', $item['precio_unitario']);
                $stmt_new_detail->bindParam(':subtotal', $item['subtotal']);
                $stmt_new_detail->execute();
            }

            $this->conn->commit();
            return $new_sale_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getPendingSales($id_sucursal)
    {
        $query = "SELECT v.id, v.fecha, v.total, c.nombre as cliente_nombre 
                  FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  WHERE v.estado = 'Pendiente' AND v.id_sucursal = :id_sucursal
                  ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaleForPOS($id_venta)
    {
        $resultado = [];
        $query_venta = "SELECT v.*, c.nombre as cliente_nombre FROM ventas v JOIN clientes c ON v.id_cliente = c.id WHERE v.id = :id_venta AND v.estado = 'Pendiente'";
        $stmt_venta = $this->conn->prepare($query_venta);
        $stmt_venta->bindParam(':id_venta', $id_venta);
        $stmt_venta->execute();
        $resultado['header'] = $stmt_venta->fetch(PDO::FETCH_ASSOC);

        if (!$resultado['header'])
            return null;

        $query_items = "SELECT vd.*, p.nombre, p.precio_menudeo, p.precio_mayoreo, p.sku,
                               GROUP_CONCAT(pc.codigo_barras SEPARATOR ', ') AS codigos_barras
                        FROM venta_detalles vd
                        JOIN productos p ON vd.id_producto = p.id
                        LEFT JOIN producto_codigos pc ON p.id = pc.id_producto
                        WHERE vd.id_venta = :id_venta
                        GROUP BY vd.id, vd.id_venta, vd.id_producto, vd.cantidad, vd.precio_unitario, vd.subtotal, p.nombre, p.precio_menudeo, p.precio_mayoreo, p.sku";
        $stmt_items = $this->conn->prepare($query_items);
        $stmt_items->bindParam(':id_venta', $id_venta);
        $stmt_items->execute();
        $resultado['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public function getDetailsForTicket($id_venta)
    {
        $resultado = [];

        $query_venta = "SELECT
                            v.id, v.fecha, v.total, v.metodo_pago, v.iva_aplicado, v.estado,
                            v.id_cliente,
                            c.nombre as cliente,
                            u.nombre as vendedor,
                            s.nombre as sucursal_nombre, s.direccion as sucursal_direccion, s.telefono as sucursal_telefono,
                            cd.direccion as cliente_direccion
                        FROM ventas v
                        JOIN clientes c ON v.id_cliente = c.id
                        JOIN usuarios u ON v.id_usuario = u.id
                        JOIN sucursales s ON v.id_sucursal = s.id
                        LEFT JOIN cliente_direcciones cd ON v.id_direccion_envio = cd.id
                        WHERE v.id = :id_venta";
        $stmt_venta = $this->conn->prepare($query_venta);
        $stmt_venta->bindParam(':id_venta', $id_venta);
        $stmt_venta->execute();
        $resultado['venta'] = $stmt_venta->fetch(PDO::FETCH_ASSOC);

        $query_items = "SELECT vd.id_producto, vd.cantidad, vd.precio_unitario, vd.subtotal, p.nombre as producto_nombre, p.sku
                        FROM venta_detalles vd
                        JOIN productos p ON vd.id_producto = p.id
                        WHERE vd.id_venta = :id_venta";
        $stmt_items = $this->conn->prepare($query_items);
        $stmt_items->bindParam(':id_venta', $id_venta);
        $stmt_items->execute();
        $resultado['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public function deletePendingSale($id_venta, $id_sucursal)
    {
        $query = "DELETE FROM ventas WHERE id = :id_venta AND estado = 'Pendiente' AND id_sucursal = :id_sucursal";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_venta', $id_venta);
        $stmt->bindParam(':id_sucursal', $id_sucursal);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function cancelSale($id_venta, $id_usuario_cancela, $id_sucursal)
    {
        try {
            $this->conn->beginTransaction();

            $saleDetails = $this->getDetailsForTicket($id_venta);

            if (!$saleDetails || !$saleDetails['venta']) {
                throw new Exception("Venta no encontrada.");
            }

            $venta = $saleDetails['venta'];
            $items = $saleDetails['items'];
            $id_cliente = $venta['id_cliente'];
            $metodo_pago_json = $venta['metodo_pago'];

            if ($venta['estado'] === 'Cancelada') {
                throw new Exception("La venta ya ha sido cancelada previamente.");
            }
            if ($venta['estado'] === 'Pendiente' || $venta['estado'] === 'Cotizacion') {
                throw new Exception("Las ventas pendientes o cotizaciones no pueden ser canceladas por este método. Deben ser eliminadas.");
            }

            foreach ($items as $item) {
                $product = $this->productoModel->getById($item['id_producto'], $id_sucursal);
                $old_stock = $product['stock'] ?? 0;
                $new_stock = $old_stock + $item['cantidad'];

                $this->productoModel->updateStock(
                    $item['id_producto'],
                    $id_sucursal,
                    $new_stock,
                    'devolucion',
                    $item['cantidad'],
                    $old_stock,
                    'Devolución por cancelación de Venta #' . $id_venta,
                    $id_venta
                );
            }

            if ($metodo_pago_json) {
                $payments = json_decode($metodo_pago_json, true);
                $creditPaymentAmount = 0;
                foreach ($payments as $payment) {
                    if (($payment['method'] ?? '') === 'Crédito') {
                        $creditPaymentAmount += (float) $payment['amount'];
                    }
                }

                if ($creditPaymentAmount > 0) {
                    // 1) Bloquear si ya hubo abonos aplicados a esta venta
                    $abonosStmt = $this->conn->prepare("
            SELECT COUNT(*) FROM aplicaciones_pagos_credito WHERE id_venta = :id_venta
        ");
                    $abonosStmt->execute([':id_venta' => $id_venta]);
                    $abonos = (int) $abonosStmt->fetchColumn();
                    if ($abonos > 0) {
                        throw new Exception("No se puede cancelar: la venta ya tiene abonos aplicados.");
                    }

                    // 2) Cerrar el ledger de esta venta (dejar saldo en 0 y estatus en 'Cerrada')
                    $stmtLedger = $this->conn->prepare("
            UPDATE ventas_credito
            SET saldo_pendiente = 0, estatus = 'Cerrada'
            WHERE id_venta = :id_venta
        ");
                    $stmtLedger->execute([':id_venta' => $id_venta]);

                    // 3) Re-sincronizar la caché clientes.deuda_actual según el ledger
                    $stmtResync = $this->conn->prepare("
            UPDATE clientes c
            LEFT JOIN (
                SELECT id_cliente, SUM(saldo_pendiente) AS deuda
                FROM ventas_credito
                GROUP BY id_cliente
            ) x ON x.id_cliente = c.id
            SET c.deuda_actual = COALESCE(x.deuda, 0)
            WHERE c.id = :id_cliente
        ");
                    $stmtResync->execute([':id_cliente' => $id_cliente]);
                }
            }

            $query_cancel = "UPDATE ventas SET estado = 'Cancelada', id_usuario_cancela = :id_usuario_cancela, fecha_cancelacion = NOW() WHERE id = :id_venta AND id_sucursal = :id_sucursal";
            $stmt_cancel = $this->conn->prepare($query_cancel);
            $stmt_cancel->bindParam(':id_usuario_cancela', $id_usuario_cancela);
            $stmt_cancel->bindParam(':id_venta', $id_venta);
            $stmt_cancel->bindParam(':id_sucursal', $id_sucursal);
            $stmt_cancel->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
