<?php
/**
 * Controlador de Proveedores
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class ProveedorController {
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
                $where[] = 'p.estado = ?';
                $params[] = $filters['estado'];
            }

            if (isset($filters['search'])) {
                $where[] = '(p.nombre LIKE ? OR p.cedula LIKE ? OR p.rnc LIKE ?)';
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM proveedores p WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener proveedores
            $stmt = $this->db->query(
                "SELECT p.*,
                        (SELECT COUNT(*) FROM compras WHERE proveedor_id = p.id) as total_compras
                 FROM proveedores p
                 WHERE $whereClause
                 ORDER BY p.nombre ASC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $proveedores = $stmt->fetchAll();

            sendResponse([
                'items' => $proveedores,
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
            sendError('Error al obtener proveedores: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM proveedores WHERE id = ?",
                [$id]
            );

            $proveedor = $stmt->fetch();

            if (!$proveedor) {
                sendError('Proveedor no encontrado', 404);
                return;
            }

            sendResponse($proveedor);
        } catch (Exception $e) {
            sendError('Error al obtener proveedor: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['nombre'])) {
                sendError('Nombre es requerido', 400);
                return;
            }

            if (empty($data['cedula']) && empty($data['rnc'])) {
                sendError('Cédula o RNC es requerido', 400);
                return;
            }

            $this->db->query(
                "INSERT INTO proveedores (
                    nombre, cedula, rnc, telefono, email,
                    direccion, contacto, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['nombre'],
                    $data['cedula'] ?? null,
                    $data['rnc'] ?? null,
                    $data['telefono'] ?? null,
                    $data['email'] ?? null,
                    $data['direccion'] ?? null,
                    $data['contacto'] ?? null,
                    $data['estado'] ?? 'activo'
                ]
            );

            $proveedorId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'proveedor.creado',
                'proveedores',
                $proveedorId,
                null,
                ['nombre' => $data['nombre']]
            );

            $this->getById($proveedorId);
        } catch (Exception $e) {
            sendError('Error al crear proveedor: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM proveedores WHERE id = ?", [$id]);
            $proveedor = $stmt->fetch();

            if (!$proveedor) {
                sendError('Proveedor no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['nombre'])) {
                $updates[] = 'nombre = ?';
                $params[] = $data['nombre'];
            }

            if (isset($data['cedula'])) {
                $updates[] = 'cedula = ?';
                $params[] = $data['cedula'];
            }

            if (isset($data['rnc'])) {
                $updates[] = 'rnc = ?';
                $params[] = $data['rnc'];
            }

            if (isset($data['telefono'])) {
                $updates[] = 'telefono = ?';
                $params[] = $data['telefono'];
            }

            if (isset($data['email'])) {
                $updates[] = 'email = ?';
                $params[] = $data['email'];
            }

            if (isset($data['direccion'])) {
                $updates[] = 'direccion = ?';
                $params[] = $data['direccion'];
            }

            if (isset($data['contacto'])) {
                $updates[] = 'contacto = ?';
                $params[] = $data['contacto'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE proveedores SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'proveedor.actualizado',
                    'proveedores',
                    $id,
                    $proveedor,
                    array_merge($proveedor, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar proveedor: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM proveedores WHERE id = ?", [$id]);
            $proveedor = $stmt->fetch();

            if (!$proveedor) {
                sendError('Proveedor no encontrado', 404);
                return;
            }

            // Verificar si está en uso
            $compraStmt = $this->db->query("SELECT COUNT(*) as count FROM compras WHERE proveedor_id = ?", [$id]);
            $compraCount = $compraStmt->fetch()['count'];

            if ($compraCount > 0) {
                sendError('No se puede eliminar el proveedor porque está asociado a compras', 400);
                return;
            }

            $this->db->query("DELETE FROM proveedores WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'proveedor.eliminado',
                'proveedores',
                $id,
                $proveedor,
                null
            );

            sendResponse(['message' => 'Proveedor eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar proveedor: ' . $e->getMessage(), 500);
        }
    }
}

