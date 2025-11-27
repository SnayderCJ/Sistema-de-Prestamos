<?php
/**
 * Controlador de Categorías de Artículos
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class CategoriaArticuloController {
    private $db;
    private $auditoriaService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auditoriaService = new AuditoriaService();
    }

    public function getAll($filters = []) {
        try {
            $stmt = $this->db->query(
                "SELECT c.*,
                        (SELECT COUNT(*) FROM articulos WHERE categoria_id = c.id) as total_articulos
                 FROM categorias_articulos c
                 ORDER BY c.nombre ASC"
            );

            $categorias = $stmt->fetchAll();

            sendResponse($categorias);
        } catch (Exception $e) {
            sendError('Error al obtener categorías: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM categorias_articulos WHERE id = ?",
                [$id]
            );

            $categoria = $stmt->fetch();

            if (!$categoria) {
                sendError('Categoría no encontrada', 404);
                return;
            }

            sendResponse($categoria);
        } catch (Exception $e) {
            sendError('Error al obtener categoría: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['nombre'])) {
                sendError('Nombre es requerido', 400);
                return;
            }

            $this->db->query(
                "INSERT INTO categorias_articulos (nombre, descripcion, estado)
                 VALUES (?, ?, ?)",
                [
                    $data['nombre'],
                    $data['descripcion'] ?? null,
                    $data['estado'] ?? 'activo'
                ]
            );

            $categoriaId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'categoria_articulo.creada',
                'categorias_articulos',
                $categoriaId,
                null,
                ['nombre' => $data['nombre']]
            );

            $this->getById($categoriaId);
        } catch (Exception $e) {
            sendError('Error al crear categoría: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM categorias_articulos WHERE id = ?", [$id]);
            $categoria = $stmt->fetch();

            if (!$categoria) {
                sendError('Categoría no encontrada', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['nombre'])) {
                $updates[] = 'nombre = ?';
                $params[] = $data['nombre'];
            }

            if (isset($data['descripcion'])) {
                $updates[] = 'descripcion = ?';
                $params[] = $data['descripcion'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE categorias_articulos SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'categoria_articulo.actualizada',
                    'categorias_articulos',
                    $id,
                    $categoria,
                    array_merge($categoria, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar categoría: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM categorias_articulos WHERE id = ?", [$id]);
            $categoria = $stmt->fetch();

            if (!$categoria) {
                sendError('Categoría no encontrada', 404);
                return;
            }

            // Verificar si está en uso
            $articuloStmt = $this->db->query("SELECT COUNT(*) as count FROM articulos WHERE categoria_id = ?", [$id]);
            $articuloCount = $articuloStmt->fetch()['count'];

            if ($articuloCount > 0) {
                sendError('No se puede eliminar la categoría porque está asociada a artículos', 400);
                return;
            }

            $this->db->query("DELETE FROM categorias_articulos WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'categoria_articulo.eliminada',
                'categorias_articulos',
                $id,
                $categoria,
                null
            );

            sendResponse(['message' => 'Categoría eliminada correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar categoría: ' . $e->getMessage(), 500);
        }
    }
}

