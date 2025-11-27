<?php
/**
 * Controlador de Vehículos
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class VehiculoController {
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
                $where[] = 'v.prestamo_id = ?';
                $params[] = $filters['prestamo_id'];
            }

            if (isset($filters['estado'])) {
                $where[] = 'v.estado = ?';
                $params[] = $filters['estado'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM vehiculos v WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener vehículos
            $stmt = $this->db->query(
                "SELECT v.*, 
                        p.numero_prestamo,
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido
                 FROM vehiculos v
                 LEFT JOIN prestamos p ON v.prestamo_id = p.id
                 LEFT JOIN clientes c ON p.cliente_id = c.id
                 WHERE $whereClause
                 ORDER BY v.fecha_registro DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $vehiculos = $stmt->fetchAll();

            sendResponse([
                'items' => $vehiculos,
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
            sendError('Error al obtener vehículos: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT v.*, 
                        p.numero_prestamo,
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido
                 FROM vehiculos v
                 LEFT JOIN prestamos p ON v.prestamo_id = p.id
                 LEFT JOIN clientes c ON p.cliente_id = c.id
                 WHERE v.id = ?",
                [$id]
            );

            $vehiculo = $stmt->fetch();

            if (!$vehiculo) {
                sendError('Vehículo no encontrado', 404);
                return;
            }

            sendResponse($vehiculo);
        } catch (Exception $e) {
            sendError('Error al obtener vehículo: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['prestamo_id'])) {
                sendError('Préstamo es requerido', 400);
                return;
            }

            if (empty($data['placa'])) {
                sendError('Placa es requerida', 400);
                return;
            }

            if (empty($data['marca'])) {
                sendError('Marca es requerida', 400);
                return;
            }

            if (empty($data['modelo'])) {
                sendError('Modelo es requerido', 400);
                return;
            }

            $this->db->query(
                "INSERT INTO vehiculos (
                    prestamo_id, placa, marca, modelo, año, color,
                    numero_chasis, numero_motor, valor_comercial, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['prestamo_id'],
                    $data['placa'],
                    $data['marca'],
                    $data['modelo'],
                    $data['año'] ?? null,
                    $data['color'] ?? null,
                    $data['numero_chasis'] ?? null,
                    $data['numero_motor'] ?? null,
                    $data['valor_comercial'] ?? null,
                    $data['estado'] ?? 'activo'
                ]
            );

            $vehiculoId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'vehiculo.creado',
                'vehiculos',
                $vehiculoId,
                null,
                ['prestamo_id' => $data['prestamo_id'], 'placa' => $data['placa']]
            );

            $this->getById($vehiculoId);
        } catch (Exception $e) {
            sendError('Error al crear vehículo: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM vehiculos WHERE id = ?", [$id]);
            $vehiculo = $stmt->fetch();

            if (!$vehiculo) {
                sendError('Vehículo no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['placa'])) {
                $updates[] = 'placa = ?';
                $params[] = $data['placa'];
            }

            if (isset($data['marca'])) {
                $updates[] = 'marca = ?';
                $params[] = $data['marca'];
            }

            if (isset($data['modelo'])) {
                $updates[] = 'modelo = ?';
                $params[] = $data['modelo'];
            }

            if (isset($data['año'])) {
                $updates[] = 'año = ?';
                $params[] = $data['año'];
            }

            if (isset($data['color'])) {
                $updates[] = 'color = ?';
                $params[] = $data['color'];
            }

            if (isset($data['numero_chasis'])) {
                $updates[] = 'numero_chasis = ?';
                $params[] = $data['numero_chasis'];
            }

            if (isset($data['numero_motor'])) {
                $updates[] = 'numero_motor = ?';
                $params[] = $data['numero_motor'];
            }

            if (isset($data['valor_comercial'])) {
                $updates[] = 'valor_comercial = ?';
                $params[] = $data['valor_comercial'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE vehiculos SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'vehiculo.actualizado',
                    'vehiculos',
                    $id,
                    $vehiculo,
                    array_merge($vehiculo, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar vehículo: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM vehiculos WHERE id = ?", [$id]);
            $vehiculo = $stmt->fetch();

            if (!$vehiculo) {
                sendError('Vehículo no encontrado', 404);
                return;
            }

            $this->db->query("DELETE FROM vehiculos WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'vehiculo.eliminado',
                'vehiculos',
                $id,
                $vehiculo,
                null
            );

            sendResponse(['message' => 'Vehículo eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar vehículo: ' . $e->getMessage(), 500);
        }
    }
}
