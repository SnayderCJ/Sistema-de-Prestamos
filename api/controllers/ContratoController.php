<?php
/**
 * Controlador de Contratos
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class ContratoController {
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
                $where[] = 'c.prestamo_id = ?';
                $params[] = $filters['prestamo_id'];
            }

            if (isset($filters['estado'])) {
                $where[] = 'c.estado = ?';
                $params[] = $filters['estado'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM contratos c WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener contratos
            $stmt = $this->db->query(
                "SELECT c.*, 
                        p.numero_prestamo,
                        c2.nombre as cliente_nombre, 
                        c2.apellido as cliente_apellido
                 FROM contratos c
                 LEFT JOIN prestamos p ON c.prestamo_id = p.id
                 LEFT JOIN clientes c2 ON p.cliente_id = c2.id
                 WHERE $whereClause
                 ORDER BY c.fecha_creacion DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $contratos = $stmt->fetchAll();

            sendResponse([
                'items' => $contratos,
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
            sendError('Error al obtener contratos: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT c.*, 
                        p.numero_prestamo,
                        c2.nombre as cliente_nombre, 
                        c2.apellido as cliente_apellido
                 FROM contratos c
                 LEFT JOIN prestamos p ON c.prestamo_id = p.id
                 LEFT JOIN clientes c2 ON p.cliente_id = c2.id
                 WHERE c.id = ?",
                [$id]
            );

            $contrato = $stmt->fetch();

            if (!$contrato) {
                sendError('Contrato no encontrado', 404);
                return;
            }

            sendResponse($contrato);
        } catch (Exception $e) {
            sendError('Error al obtener contrato: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['prestamo_id'])) {
                sendError('Préstamo es requerido', 400);
                return;
            }

            if (empty($data['tipo_contrato'])) {
                sendError('Tipo de contrato es requerido', 400);
                return;
            }

            $this->db->query(
                "INSERT INTO contratos (
                    prestamo_id, tipo_contrato, fecha_inicio, fecha_fin,
                    monto_total, condiciones, estado, archivo_contrato
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['prestamo_id'],
                    $data['tipo_contrato'],
                    $data['fecha_inicio'] ?? date('Y-m-d'),
                    $data['fecha_fin'] ?? null,
                    $data['monto_total'] ?? null,
                    $data['condiciones'] ?? null,
                    $data['estado'] ?? 'activo',
                    $data['archivo_contrato'] ?? null
                ]
            );

            $contratoId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'contrato.creado',
                'contratos',
                $contratoId,
                null,
                ['prestamo_id' => $data['prestamo_id'], 'tipo_contrato' => $data['tipo_contrato']]
            );

            $this->getById($contratoId);
        } catch (Exception $e) {
            sendError('Error al crear contrato: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM contratos WHERE id = ?", [$id]);
            $contrato = $stmt->fetch();

            if (!$contrato) {
                sendError('Contrato no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['tipo_contrato'])) {
                $updates[] = 'tipo_contrato = ?';
                $params[] = $data['tipo_contrato'];
            }

            if (isset($data['fecha_inicio'])) {
                $updates[] = 'fecha_inicio = ?';
                $params[] = $data['fecha_inicio'];
            }

            if (isset($data['fecha_fin'])) {
                $updates[] = 'fecha_fin = ?';
                $params[] = $data['fecha_fin'];
            }

            if (isset($data['monto_total'])) {
                $updates[] = 'monto_total = ?';
                $params[] = $data['monto_total'];
            }

            if (isset($data['condiciones'])) {
                $updates[] = 'condiciones = ?';
                $params[] = $data['condiciones'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            if (isset($data['archivo_contrato'])) {
                $updates[] = 'archivo_contrato = ?';
                $params[] = $data['archivo_contrato'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE contratos SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'contrato.actualizado',
                    'contratos',
                    $id,
                    $contrato,
                    array_merge($contrato, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar contrato: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM contratos WHERE id = ?", [$id]);
            $contrato = $stmt->fetch();

            if (!$contrato) {
                sendError('Contrato no encontrado', 404);
                return;
            }

            $this->db->query("DELETE FROM contratos WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'contrato.eliminado',
                'contratos',
                $id,
                $contrato,
                null
            );

            sendResponse(['message' => 'Contrato eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar contrato: ' . $e->getMessage(), 500);
        }
    }
}
