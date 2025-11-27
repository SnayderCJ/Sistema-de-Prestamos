<?php
/**
 * Controlador de Garantes
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class GaranteController {
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

            if (isset($filters['prestamo_id'])) {
                $where[] = 'g.prestamo_id = ?';
                $params[] = $filters['prestamo_id'];
            }

            if (isset($filters['estado'])) {
                $where[] = 'g.estado = ?';
                $params[] = $filters['estado'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM garantes g WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener garantes
            $stmt = $this->db->query(
                "SELECT g.*, 
                        p.numero_prestamo,
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido
                 FROM garantes g
                 LEFT JOIN prestamos p ON g.prestamo_id = p.id
                 LEFT JOIN clientes c ON p.cliente_id = c.id
                 WHERE $whereClause
                 ORDER BY g.fecha_creacion DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $garantes = $stmt->fetchAll();

            sendResponse([
                'items' => $garantes,
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
            sendError('Error al obtener garantes: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT g.*, 
                        p.numero_prestamo,
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido
                 FROM garantes g
                 LEFT JOIN prestamos p ON g.prestamo_id = p.id
                 LEFT JOIN clientes c ON p.cliente_id = c.id
                 WHERE g.id = ?",
                [$id]
            );

            $garante = $stmt->fetch();

            if (!$garante) {
                sendError('Garante no encontrado', 404);
                return;
            }

            sendResponse($garante);
        } catch (Exception $e) {
            sendError('Error al obtener garante: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['prestamo_id'])) {
                sendError('Préstamo es requerido', 400);
                return;
            }

            if (empty($data['cedula'])) {
                sendError('Cédula es requerida', 400);
                return;
            }

            if (empty($data['nombre'])) {
                sendError('Nombre es requerido', 400);
                return;
            }

            if (empty($data['apellido'])) {
                sendError('Apellido es requerido', 400);
                return;
            }

            $this->db->query(
                "INSERT INTO garantes (
                    prestamo_id, cedula, nombre, apellido, telefono, email,
                    direccion, ocupacion, ingresos_mensuales, relacion_cliente, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['prestamo_id'],
                    $data['cedula'],
                    $data['nombre'],
                    $data['apellido'],
                    $data['telefono'] ?? null,
                    $data['email'] ?? null,
                    $data['direccion'] ?? null,
                    $data['ocupacion'] ?? null,
                    $data['ingresos_mensuales'] ?? null,
                    $data['relacion_cliente'] ?? null,
                    $data['estado'] ?? 'activo'
                ]
            );

            $garanteId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'garante.creado',
                'garantes',
                $garanteId,
                null,
                ['prestamo_id' => $data['prestamo_id'], 'cedula' => $data['cedula']]
            );

            $this->getById($garanteId);
        } catch (Exception $e) {
            sendError('Error al crear garante: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM garantes WHERE id = ?", [$id]);
            $garante = $stmt->fetch();

            if (!$garante) {
                sendError('Garante no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['cedula'])) {
                $updates[] = 'cedula = ?';
                $params[] = $data['cedula'];
            }

            if (isset($data['nombre'])) {
                $updates[] = 'nombre = ?';
                $params[] = $data['nombre'];
            }

            if (isset($data['apellido'])) {
                $updates[] = 'apellido = ?';
                $params[] = $data['apellido'];
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

            if (isset($data['ocupacion'])) {
                $updates[] = 'ocupacion = ?';
                $params[] = $data['ocupacion'];
            }

            if (isset($data['ingresos_mensuales'])) {
                $updates[] = 'ingresos_mensuales = ?';
                $params[] = $data['ingresos_mensuales'];
            }

            if (isset($data['relacion_cliente'])) {
                $updates[] = 'relacion_cliente = ?';
                $params[] = $data['relacion_cliente'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE garantes SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'garante.actualizado',
                    'garantes',
                    $id,
                    $garante,
                    array_merge($garante, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar garante: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM garantes WHERE id = ?", [$id]);
            $garante = $stmt->fetch();

            if (!$garante) {
                sendError('Garante no encontrado', 404);
                return;
            }

            $this->db->query("DELETE FROM garantes WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'garante.eliminado',
                'garantes',
                $id,
                $garante,
                null
            );

            sendResponse(['message' => 'Garante eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar garante: ' . $e->getMessage(), 500);
        }
    }
}
