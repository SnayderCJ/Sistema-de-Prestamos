<?php
/**
 * Controlador de Compras
 */
require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/WebhookService.php';

class CompraController {
    private $db;
    private $auditoriaService;
    private $webhookService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auditoriaService = new AuditoriaService();
        $this->webhookService = new WebhookService();
    }

    public function getAll($filters = []) {
        try {
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 20;
            $offset = ($page - 1) * $perPage;

            $where = ['1=1'];
            $params = [];

            if (isset($filters['estado'])) {
                $where[] = 'c.estado = ?';
                $params[] = $filters['estado'];
            }

            if (isset($filters['proveedor_id'])) {
                $where[] = 'c.proveedor_id = ?';
                $params[] = $filters['proveedor_id'];
            }

            if (isset($filters['fecha_desde'])) {
                $where[] = 'DATE(c.fecha) >= ?';
                $params[] = $filters['fecha_desde'];
            }

            if (isset($filters['fecha_hasta'])) {
                $where[] = 'DATE(c.fecha) <= ?';
                $params[] = $filters['fecha_hasta'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM compras c WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener compras
            $stmt = $this->db->query(
                "SELECT c.*, 
                        p.nombre as proveedor_nombre,
                        (SELECT COUNT(*) FROM compra_articulos WHERE compra_id = c.id) as total_articulos
                 FROM compras c
                 LEFT JOIN proveedores p ON c.proveedor_id = p.id
                 WHERE $whereClause
                 ORDER BY c.fecha DESC, c.id DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $compras = $stmt->fetchAll();

            // Obtener artículos para cada compra
            foreach ($compras as &$compra) {
                $articulosStmt = $this->db->query(
                    "SELECT ca.*, a.codigo, a.nombre 
                     FROM compra_articulos ca
                     LEFT JOIN articulos a ON ca.articulo_id = a.id
                     WHERE ca.compra_id = ?",
                    [$compra['id']]
                );
                $compra['articulos'] = $articulosStmt->fetchAll();
            }

            sendResponse([
                'items' => $compras,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ]);
        } catch (Exception $e) {
            sendError('Error al obtener compras: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT c.*, p.nombre as proveedor_nombre, p.cedula as proveedor_cedula, p.rnc as proveedor_rnc
                 FROM compras c
                 LEFT JOIN proveedores p ON c.proveedor_id = p.id
                 WHERE c.id = ?",
                [$id]
            );

            $compra = $stmt->fetch();

            if (!$compra) {
                sendError('Compra no encontrada', 404);
                return;
            }

            // Obtener artículos
            $articulosStmt = $this->db->query(
                "SELECT ca.*, a.codigo, a.nombre, a.categoria_id
                 FROM compra_articulos ca
                 LEFT JOIN articulos a ON ca.articulo_id = a.id
                 WHERE ca.compra_id = ?",
                [$id]
            );
            $compra['articulos'] = $articulosStmt->fetchAll();

            sendResponse($compra);
        } catch (Exception $e) {
            sendError('Error al obtener compra: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['proveedor_id'])) {
                sendError('Proveedor es requerido', 400);
                return;
            }

            if (empty($data['articulos']) || !is_array($data['articulos']) || count($data['articulos']) === 0) {
                sendError('Debe agregar al menos un artículo', 400);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            // Calcular totales
            $subtotal = 0;
            foreach ($data['articulos'] as $item) {
                $precioUnitario = $item['precio_unitario'] ?? 0;
                $cantidad = $item['cantidad'] ?? 1;
                $descuento = $item['descuento'] ?? 0;
                
                $subtotalItem = $precioUnitario * $cantidad;
                $descuentoItem = ($subtotalItem * $descuento) / 100;
                $subtotal += ($subtotalItem - $descuentoItem);
            }

            $descuentoGeneral = $data['descuento_general'] ?? 0;
            $descuentoMonto = ($subtotal * $descuentoGeneral) / 100;
            $total = $subtotal - $descuentoMonto;

            // Generar número de compra
            $numeroCompra = 'COMP-' . date('Ymd') . '-' . str_pad(
                $this->db->query("SELECT COUNT(*) + 1 as num FROM compras WHERE DATE(fecha) = CURDATE()")->fetch()['num'],
                4,
                '0',
                STR_PAD_LEFT
            );

            // Insertar compra
            $this->db->query(
                "INSERT INTO compras (
                    numero_compra, proveedor_id, fecha, numero_factura,
                    subtotal, descuento, total, observaciones, estado, usuario_id
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'completada', ?)",
                [
                    $numeroCompra,
                    $data['proveedor_id'],
                    $data['numero_factura'] ?? null,
                    $subtotal,
                    $descuentoMonto,
                    $total,
                    $data['observaciones'] ?? null,
                    $user['id']
                ]
            );

            $compraId = $this->db->lastInsertId();

            // Insertar artículos y actualizar stock/precios
            foreach ($data['articulos'] as $item) {
                $precioUnitario = $item['precio_unitario'] ?? 0;
                $cantidad = $item['cantidad'] ?? 1;
                $descuento = $item['descuento'] ?? 0;
                
                $subtotalItem = $precioUnitario * $cantidad;
                $descuentoItem = ($subtotalItem * $descuento) / 100;

                $this->db->query(
                    "INSERT INTO compra_articulos (
                        compra_id, articulo_id, cantidad, precio_unitario, descuento, subtotal
                    ) VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $compraId,
                        $item['articulo_id'],
                        $cantidad,
                        $precioUnitario,
                        $descuento,
                        $subtotalItem - $descuentoItem
                    ]
                );

                // Actualizar stock y precio de compra del artículo
                $this->db->query(
                    "UPDATE articulos 
                     SET stock = stock + ?, 
                         precio_compra = ?
                     WHERE id = ?",
                    [$cantidad, $precioUnitario, $item['articulo_id']]
                );
            }

            $this->db->getConnection()->commit();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'compra.creada',
                'compras',
                $compraId,
                null,
                ['numero_compra' => $numeroCompra, 'total' => $total]
            );

            // Webhook
            $this->webhookService->dispatch('compra.creada', [
                'compra_id' => $compraId,
                'numero_compra' => $numeroCompra,
                'total' => $total
            ]);

            $this->getById($compraId);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al crear compra: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM compras WHERE id = ?", [$id]);
            $compra = $stmt->fetch();

            if (!$compra) {
                sendError('Compra no encontrada', 404);
                return;
            }

            if ($compra['estado'] === 'cancelada') {
                sendError('No se puede modificar una compra cancelada', 400);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['estado'])) {
                if (!in_array($data['estado'], ['pendiente', 'completada', 'cancelada'])) {
                    sendError('Estado inválido', 400);
                    return;
                }

                $updates[] = 'estado = ?';
                $params[] = $data['estado'];

                // Si se cancela, revertir stock
                if ($data['estado'] === 'cancelada' && $compra['estado'] !== 'cancelada') {
                    $articulosStmt = $this->db->query(
                        "SELECT articulo_id, cantidad FROM compra_articulos WHERE compra_id = ?",
                        [$id]
                    );
                    foreach ($articulosStmt->fetchAll() as $item) {
                        $this->db->query(
                            "UPDATE articulos SET stock = stock - ? WHERE id = ?",
                            [$item['cantidad'], $item['articulo_id']]
                        );
                    }
                }
            }

            if (isset($data['observaciones'])) {
                $updates[] = 'observaciones = ?';
                $params[] = $data['observaciones'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE compras SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'compra.actualizada',
                    'compras',
                    $id,
                    $compra,
                    array_merge($compra, array_combine(array_map(function($u) { return explode(' = ', $u)[0]; }, $updates), $params))
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar compra: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM compras WHERE id = ?", [$id]);
            $compra = $stmt->fetch();

            if (!$compra) {
                sendError('Compra no encontrada', 404);
                return;
            }

            if ($compra['estado'] === 'completada') {
                sendError('No se puede eliminar una compra completada', 400);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            // Revertir stock
            $articulosStmt = $this->db->query(
                "SELECT articulo_id, cantidad FROM compra_articulos WHERE compra_id = ?",
                [$id]
            );
            foreach ($articulosStmt->fetchAll() as $item) {
                $this->db->query(
                    "UPDATE articulos SET stock = stock - ? WHERE id = ?",
                    [$item['cantidad'], $item['articulo_id']]
                );
            }

            $this->db->query("DELETE FROM compra_articulos WHERE compra_id = ?", [$id]);
            $this->db->query("DELETE FROM compras WHERE id = ?", [$id]);

            $this->db->getConnection()->commit();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'compra.eliminada',
                'compras',
                $id,
                $compra,
                null
            );

            sendResponse(['message' => 'Compra eliminada correctamente']);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al eliminar compra: ' . $e->getMessage(), 500);
        }
    }
}

