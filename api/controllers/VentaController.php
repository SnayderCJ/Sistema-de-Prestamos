<?php
/**
 * Controlador de Ventas
 */
require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/WebhookService.php';

class VentaController {
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
                $where[] = 'v.estado = ?';
                $params[] = $filters['estado'];
            }

            if (isset($filters['metodo_pago'])) {
                $where[] = 'v.metodo_pago = ?';
                $params[] = $filters['metodo_pago'];
            }

            if (isset($filters['fecha_desde'])) {
                $where[] = 'DATE(v.fecha) >= ?';
                $params[] = $filters['fecha_desde'];
            }

            if (isset($filters['fecha_hasta'])) {
                $where[] = 'DATE(v.fecha) <= ?';
                $params[] = $filters['fecha_hasta'];
            }

            if (isset($filters['cliente_id'])) {
                $where[] = 'v.cliente_id = ?';
                $params[] = $filters['cliente_id'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM ventas v WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener ventas
            $stmt = $this->db->query(
                "SELECT v.*, 
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido,
                        (SELECT COUNT(*) FROM venta_articulos WHERE venta_id = v.id) as total_articulos
                 FROM ventas v
                 LEFT JOIN clientes c ON v.cliente_id = c.id
                 WHERE $whereClause
                 ORDER BY v.fecha DESC, v.id DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $ventas = $stmt->fetchAll();

            // Obtener artículos para cada venta
            foreach ($ventas as &$venta) {
                $articulosStmt = $this->db->query(
                    "SELECT va.*, a.codigo, a.nombre 
                     FROM venta_articulos va
                     LEFT JOIN articulos a ON va.articulo_id = a.id
                     WHERE va.venta_id = ?",
                    [$venta['id']]
                );
                $venta['articulos'] = $articulosStmt->fetchAll();
            }

            sendResponse([
                'items' => $ventas,
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
            sendError('Error al obtener ventas: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT v.*, 
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido,
                        c.cedula as cliente_cedula
                 FROM ventas v
                 LEFT JOIN clientes c ON v.cliente_id = c.id
                 WHERE v.id = ?",
                [$id]
            );

            $venta = $stmt->fetch();

            if (!$venta) {
                sendError('Venta no encontrada', 404);
                return;
            }

            // Obtener artículos
            $articulosStmt = $this->db->query(
                "SELECT va.*, a.codigo, a.nombre, a.categoria_id
                 FROM venta_articulos va
                 LEFT JOIN articulos a ON va.articulo_id = a.id
                 WHERE va.venta_id = ?",
                [$id]
            );
            $venta['articulos'] = $articulosStmt->fetchAll();

            sendResponse($venta);
        } catch (Exception $e) {
            sendError('Error al obtener venta: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['cliente_id'])) {
                sendError('Cliente es requerido', 400);
                return;
            }

            if (empty($data['articulos']) || !is_array($data['articulos']) || count($data['articulos']) === 0) {
                sendError('Debe agregar al menos un artículo', 400);
                return;
            }

            if (!in_array($data['metodo_pago'], ['contado', 'credito'])) {
                sendError('Método de pago inválido', 400);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            // Calcular totales
            $subtotal = 0;
            foreach ($data['articulos'] as $item) {
                $articuloStmt = $this->db->query(
                    "SELECT precio_venta_contado, precio_venta_credito, precio_venta, stock 
                     FROM articulos WHERE id = ?",
                    [$item['articulo_id']]
                );
                $articulo = $articuloStmt->fetch();

                if (!$articulo) {
                    throw new Exception("Artículo no encontrado: {$item['articulo_id']}");
                }

                $precio = $data['metodo_pago'] === 'contado' 
                    ? ($articulo['precio_venta_contado'] ?: $articulo['precio_venta'])
                    : ($articulo['precio_venta_credito'] ?: $articulo['precio_venta']);

                $precioUnitario = $item['precio_unitario'] ?? $precio;
                $cantidad = $item['cantidad'] ?? 1;
                $descuento = $item['descuento'] ?? 0;
                
                $subtotalItem = $precioUnitario * $cantidad;
                $descuentoItem = ($subtotalItem * $descuento) / 100;
                $subtotal += ($subtotalItem - $descuentoItem);

                // Verificar stock
                if ($articulo['stock'] < $cantidad) {
                    throw new Exception("Stock insuficiente para artículo: {$articulo['id']}");
                }
            }

            $descuentoGeneral = $data['descuento_general'] ?? 0;
            $descuentoMonto = ($subtotal * $descuentoGeneral) / 100;
            $total = $subtotal - $descuentoMonto;

            // Generar número de venta
            $numeroVenta = 'VTA-' . date('Ymd') . '-' . str_pad(
                $this->db->query("SELECT COUNT(*) + 1 as num FROM ventas WHERE DATE(fecha) = CURDATE()")->fetch()['num'],
                4,
                '0',
                STR_PAD_LEFT
            );

            // Insertar venta
            $this->db->query(
                "INSERT INTO ventas (
                    numero_venta, cliente_id, fecha, metodo_pago,
                    subtotal, descuento, total, observaciones, estado, usuario_id
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'completada', ?)",
                [
                    $numeroVenta,
                    $data['cliente_id'],
                    $data['metodo_pago'],
                    $subtotal,
                    $descuentoMonto,
                    $total,
                    $data['observaciones'] ?? null,
                    $user['id']
                ]
            );

            $ventaId = $this->db->lastInsertId();

            // Insertar artículos y actualizar stock
            foreach ($data['articulos'] as $item) {
                $articuloStmt = $this->db->query(
                    "SELECT precio_venta_contado, precio_venta_credito, precio_venta, stock 
                     FROM articulos WHERE id = ?",
                    [$item['articulo_id']]
                );
                $articulo = $articuloStmt->fetch();

                $precio = $data['metodo_pago'] === 'contado' 
                    ? ($articulo['precio_venta_contado'] ?: $articulo['precio_venta'])
                    : ($articulo['precio_venta_credito'] ?: $articulo['precio_venta']);

                $precioUnitario = $item['precio_unitario'] ?? $precio;
                $cantidad = $item['cantidad'] ?? 1;
                $descuento = $item['descuento'] ?? 0;
                
                $subtotalItem = $precioUnitario * $cantidad;
                $descuentoItem = ($subtotalItem * $descuento) / 100;

                $this->db->query(
                    "INSERT INTO venta_articulos (
                        venta_id, articulo_id, cantidad, precio_unitario, descuento, subtotal
                    ) VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $ventaId,
                        $item['articulo_id'],
                        $cantidad,
                        $precioUnitario,
                        $descuento,
                        $subtotalItem - $descuentoItem
                    ]
                );

                // Actualizar stock
                $this->db->query(
                    "UPDATE articulos SET stock = stock - ? WHERE id = ?",
                    [$cantidad, $item['articulo_id']]
                );
            }

            $this->db->getConnection()->commit();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'venta.creada',
                'ventas',
                $ventaId,
                null,
                ['numero_venta' => $numeroVenta, 'total' => $total]
            );

            // Webhook
            $this->webhookService->dispatch('venta.creada', [
                'venta_id' => $ventaId,
                'numero_venta' => $numeroVenta,
                'total' => $total
            ]);

            $this->getById($ventaId);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al crear venta: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM ventas WHERE id = ?", [$id]);
            $venta = $stmt->fetch();

            if (!$venta) {
                sendError('Venta no encontrada', 404);
                return;
            }

            if ($venta['estado'] === 'cancelada') {
                sendError('No se puede modificar una venta cancelada', 400);
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

                // Si se cancela, devolver stock
                if ($data['estado'] === 'cancelada' && $venta['estado'] !== 'cancelada') {
                    $articulosStmt = $this->db->query(
                        "SELECT articulo_id, cantidad FROM venta_articulos WHERE venta_id = ?",
                        [$id]
                    );
                    foreach ($articulosStmt->fetchAll() as $item) {
                        $this->db->query(
                            "UPDATE articulos SET stock = stock + ? WHERE id = ?",
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
                    "UPDATE ventas SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'venta.actualizada',
                    'ventas',
                    $id,
                    $venta,
                    array_merge($venta, array_combine(array_map(function($u) { return explode(' = ', $u)[0]; }, $updates), $params))
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar venta: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM ventas WHERE id = ?", [$id]);
            $venta = $stmt->fetch();

            if (!$venta) {
                sendError('Venta no encontrada', 404);
                return;
            }

            if ($venta['estado'] === 'completada') {
                sendError('No se puede eliminar una venta completada', 400);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            // Devolver stock
            $articulosStmt = $this->db->query(
                "SELECT articulo_id, cantidad FROM venta_articulos WHERE venta_id = ?",
                [$id]
            );
            foreach ($articulosStmt->fetchAll() as $item) {
                $this->db->query(
                    "UPDATE articulos SET stock = stock + ? WHERE id = ?",
                    [$item['cantidad'], $item['articulo_id']]
                );
            }

            $this->db->query("DELETE FROM venta_articulos WHERE venta_id = ?", [$id]);
            $this->db->query("DELETE FROM ventas WHERE id = ?", [$id]);

            $this->db->getConnection()->commit();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'venta.eliminada',
                'ventas',
                $id,
                $venta,
                null
            );

            sendResponse(['message' => 'Venta eliminada correctamente']);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al eliminar venta: ' . $e->getMessage(), 500);
        }
    }
}

