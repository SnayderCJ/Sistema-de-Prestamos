<?php
/**
 * Controlador de Codeudores y Fiadores
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class CodeudorController {
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

            if (isset($filters['tipo'])) {
                $where[] = 'c.tipo = ?';
                $params[] = $filters['tipo'];
            }

            if (isset($filters['estado'])) {
                $where[] = 'c.estado = ?';
                $params[] = $filters['estado'];
            }

            if (isset($filters['prestamo_id'])) {
                $where[] = 'EXISTS (SELECT 1 FROM prestamo_codeudores WHERE codeudor_id = c.id AND prestamo_id = ?)';
                $params[] = $filters['prestamo_id'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM codeudores c WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener codeudores
            $stmt = $this->db->query(
                "SELECT c.*, 
                        (SELECT COUNT(*) FROM prestamo_codeudores WHERE codeudor_id = c.id) as total_prestamos
                 FROM codeudores c
                 WHERE $whereClause
                 ORDER BY c.nombre ASC, c.apellido ASC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $codeudores = $stmt->fetchAll();

            sendResponse([
                'items' => $codeudores,
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
            sendError('Error al obtener codeudores: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM codeudores WHERE id = ?",
                [$id]
            );

            $codeudor = $stmt->fetch();

            if (!$codeudor) {
                sendError('Codeudor/Fiador no encontrado', 404);
                return;
            }

            // Obtener préstamos asociados
            $prestamosStmt = $this->db->query(
                "SELECT p.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido
                 FROM prestamos p
                 INNER JOIN prestamo_codeudores pc ON p.id = pc.prestamo_id
                 INNER JOIN clientes c ON p.cliente_id = c.id
                 WHERE pc.codeudor_id = ?",
                [$id]
            );
            $codeudor['prestamos'] = $prestamosStmt->fetchAll();

            sendResponse($codeudor);
        } catch (Exception $e) {
            sendError('Error al obtener codeudor: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
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

            if (!in_array($data['tipo'], ['codeudor', 'fiador'])) {
                sendError('Tipo inválido. Debe ser "codeudor" o "fiador"', 400);
                return;
            }

            // Verificar cédula única
            $stmt = $this->db->query("SELECT id FROM codeudores WHERE cedula = ?", [$data['cedula']]);
            if ($stmt->fetch()) {
                sendError('La cédula ya está registrada', 400);
                return;
            }

            $this->db->query(
                "INSERT INTO codeudores (
                    tipo, cedula, nombre, apellido, telefono, email,
                    direccion, ocupacion, ingresos_mensuales, relacion_cliente, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['tipo'],
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

            $codeudorId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'codeudor.creado',
                'codeudores',
                $codeudorId,
                null,
                ['tipo' => $data['tipo'], 'cedula' => $data['cedula']]
            );

            $this->getById($codeudorId);
        } catch (Exception $e) {
            sendError('Error al crear codeudor: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM codeudores WHERE id = ?", [$id]);
            $codeudor = $stmt->fetch();

            if (!$codeudor) {
                sendError('Codeudor/Fiador no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['tipo'])) {
                if (!in_array($data['tipo'], ['codeudor', 'fiador'])) {
                    sendError('Tipo inválido', 400);
                    return;
                }
                $updates[] = 'tipo = ?';
                $params[] = $data['tipo'];
            }

            if (isset($data['cedula'])) {
                // Verificar cédula única si cambió
                $checkStmt = $this->db->query("SELECT id FROM codeudores WHERE cedula = ? AND id != ?", [$data['cedula'], $id]);
                if ($checkStmt->fetch()) {
                    sendError('La cédula ya está registrada', 400);
                    return;
                }
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
                    "UPDATE codeudores SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'codeudor.actualizado',
                    'codeudores',
                    $id,
                    $codeudor,
                    array_merge($codeudor, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar codeudor: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM codeudores WHERE id = ?", [$id]);
            $codeudor = $stmt->fetch();

            if (!$codeudor) {
                sendError('Codeudor/Fiador no encontrado', 404);
                return;
            }

            // Verificar si está asociado a préstamos
            $prestamoStmt = $this->db->query("SELECT COUNT(*) as count FROM prestamo_codeudores WHERE codeudor_id = ?", [$id]);
            $prestamoCount = $prestamoStmt->fetch()['count'];

            if ($prestamoCount > 0) {
                sendError('No se puede eliminar el codeudor porque está asociado a préstamos', 400);
                return;
            }

            $this->db->query("DELETE FROM codeudores WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'codeudor.eliminado',
                'codeudores',
                $id,
                $codeudor,
                null
            );

            sendResponse(['message' => 'Codeudor/Fiador eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar codeudor: ' . $e->getMessage(), 500);
        }
    }
}

