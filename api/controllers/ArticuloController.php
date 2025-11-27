<?php
/**
 * Controlador de Artículos
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class ArticuloController {
    private $db;
    private $auditoriaService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auditoriaService = new AuditoriaService();
    }

    public function getAll($filters = []) {
        try {
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 20;
            $offset = ($page - 1) * $perPage;

            $where = ['1=1'];
            $params = [];

            if (isset($filters['estado'])) {
                $where[] = 'a.estado = ?';
                $params[] = $filters['estado'];
            }

            if (isset($filters['categoria_id'])) {
                $where[] = 'a.categoria_id = ?';
                $params[] = $filters['categoria_id'];
            }

            if (isset($filters['search'])) {
                $where[] = '(a.codigo LIKE ? OR a.nombre LIKE ?)';
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM articulos a WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener artículos
            $stmt = $this->db->query(
                "SELECT a.*, c.nombre as categoria_nombre
                 FROM articulos a
                 LEFT JOIN categorias_articulos c ON a.categoria_id = c.id
                 WHERE $whereClause
                 ORDER BY a.nombre ASC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $articulos = $stmt->fetchAll();

            sendResponse([
                'items' => $articulos,
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
            sendError('Error al obtener artículos: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT a.*, c.nombre as categoria_nombre
                 FROM articulos a
                 LEFT JOIN categorias_articulos c ON a.categoria_id = c.id
                 WHERE a.id = ?",
                [$id]
            );

            $articulo = $stmt->fetch();

            if (!$articulo) {
                sendError('Artículo no encontrado', 404);
                return;
            }

            sendResponse($articulo);
        } catch (Exception $e) {
            sendError('Error al obtener artículo: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['codigo'])) {
                sendError('Código es requerido', 400);
                return;
            }

            if (empty($data['nombre'])) {
                sendError('Nombre es requerido', 400);
                return;
            }

            if (empty($data['categoria_id'])) {
                sendError('Categoría es requerida', 400);
                return;
            }

            // Verificar código único
            $stmt = $this->db->query("SELECT id FROM articulos WHERE codigo = ?", [$data['codigo']]);
            if ($stmt->fetch()) {
                sendError('El código ya existe', 400);
                return;
            }

            $precioCompra = $data['precio_compra'] ?? 0;
            $utilidadContado = $data['utilidad_contado'] ?? 30;
            $utilidadCredito = $data['utilidad_credito'] ?? 40;

            // Calcular precios si no se proporcionan manualmente
            $precioVentaContado = $data['precio_venta_contado'] ?? ($precioCompra * (1 + $utilidadContado / 100));
            $precioVentaCredito = $data['precio_venta_credito'] ?? ($precioCompra * (1 + $utilidadCredito / 100));

            $this->db->query(
                "INSERT INTO articulos (
                    codigo, nombre, categoria_id, descripcion,
                    precio_compra, utilidad_contado, utilidad_credito,
                    precio_venta_contado, precio_venta_credito, precio_venta,
                    stock, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['codigo'],
                    $data['nombre'],
                    $data['categoria_id'],
                    $data['descripcion'] ?? null,
                    $precioCompra,
                    $utilidadContado,
                    $utilidadCredito,
                    $precioVentaContado,
                    $precioVentaCredito,
                    $precioVentaContado, // Precio por defecto
                    $data['stock'] ?? 0,
                    $data['estado'] ?? 'activo'
                ]
            );

            $articuloId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'articulo.creado',
                'articulos',
                $articuloId,
                null,
                ['codigo' => $data['codigo'], 'nombre' => $data['nombre']]
            );

            $this->getById($articuloId);
        } catch (Exception $e) {
            sendError('Error al crear artículo: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM articulos WHERE id = ?", [$id]);
            $articulo = $stmt->fetch();

            if (!$articulo) {
                sendError('Artículo no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['codigo'])) {
                // Verificar código único si cambió
                $checkStmt = $this->db->query("SELECT id FROM articulos WHERE codigo = ? AND id != ?", [$data['codigo'], $id]);
                if ($checkStmt->fetch()) {
                    sendError('El código ya existe', 400);
                    return;
                }
                $updates[] = 'codigo = ?';
                $params[] = $data['codigo'];
            }

            if (isset($data['nombre'])) {
                $updates[] = 'nombre = ?';
                $params[] = $data['nombre'];
            }

            if (isset($data['categoria_id'])) {
                $updates[] = 'categoria_id = ?';
                $params[] = $data['categoria_id'];
            }

            if (isset($data['descripcion'])) {
                $updates[] = 'descripcion = ?';
                $params[] = $data['descripcion'];
            }

            if (isset($data['precio_compra'])) {
                $updates[] = 'precio_compra = ?';
                $params[] = $data['precio_compra'];
            }

            if (isset($data['utilidad_contado'])) {
                $updates[] = 'utilidad_contado = ?';
                $params[] = $data['utilidad_contado'];
            }

            if (isset($data['utilidad_credito'])) {
                $updates[] = 'utilidad_credito = ?';
                $params[] = $data['utilidad_credito'];
            }

            if (isset($data['precio_venta_contado'])) {
                $updates[] = 'precio_venta_contado = ?';
                $params[] = $data['precio_venta_contado'];
            }

            if (isset($data['precio_venta_credito'])) {
                $updates[] = 'precio_venta_credito = ?';
                $params[] = $data['precio_venta_credito'];
            }

            if (isset($data['stock'])) {
                $updates[] = 'stock = ?';
                $params[] = $data['stock'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            // Recalcular precios si cambió precio_compra o utilidades
            if (isset($data['precio_compra']) || isset($data['utilidad_contado']) || isset($data['utilidad_credito'])) {
                $precioCompra = $data['precio_compra'] ?? $articulo['precio_compra'];
                $utilidadContado = $data['utilidad_contado'] ?? $articulo['utilidad_contado'];
                $utilidadCredito = $data['utilidad_credito'] ?? $articulo['utilidad_credito'];

                if (!isset($data['precio_venta_contado'])) {
                    $updates[] = 'precio_venta_contado = ?';
                    $params[] = $precioCompra * (1 + $utilidadContado / 100);
                }

                if (!isset($data['precio_venta_credito'])) {
                    $updates[] = 'precio_venta_credito = ?';
                    $params[] = $precioCompra * (1 + $utilidadCredito / 100);
                }
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE articulos SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'articulo.actualizado',
                    'articulos',
                    $id,
                    $articulo,
                    array_merge($articulo, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar artículo: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM articulos WHERE id = ?", [$id]);
            $articulo = $stmt->fetch();

            if (!$articulo) {
                sendError('Artículo no encontrado', 404);
                return;
            }

            // Verificar si está en uso
            $ventaStmt = $this->db->query("SELECT COUNT(*) as count FROM venta_articulos WHERE articulo_id = ?", [$id]);
            $ventaCount = $ventaStmt->fetch()['count'];

            $compraStmt = $this->db->query("SELECT COUNT(*) as count FROM compra_articulos WHERE articulo_id = ?", [$id]);
            $compraCount = $compraStmt->fetch()['count'];

            if ($ventaCount > 0 || $compraCount > 0) {
                sendError('No se puede eliminar el artículo porque está en uso', 400);
                return;
            }

            $this->db->query("DELETE FROM articulos WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'articulo.eliminado',
                'articulos',
                $id,
                $articulo,
                null
            );

            sendResponse(['message' => 'Artículo eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar artículo: ' . $e->getMessage(), 500);
        }
    }
}

