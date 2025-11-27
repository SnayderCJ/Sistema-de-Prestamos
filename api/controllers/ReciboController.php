<?php
/**
 * Controlador de Recibos
 */
require_once __DIR__ . '/../services/AuditoriaService.php';

class ReciboController {
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

            if (isset($filters['pago_id'])) {
                $where[] = 'r.pago_id = ?';
                $params[] = $filters['pago_id'];
            }

            if (isset($filters['prestamo_id'])) {
                $where[] = 'p.prestamo_id = ?';
                $params[] = $filters['prestamo_id'];
            }

            if (isset($filters['fecha_desde'])) {
                $where[] = 'DATE(r.fecha_emision) >= ?';
                $params[] = $filters['fecha_desde'];
            }

            if (isset($filters['fecha_hasta'])) {
                $where[] = 'DATE(r.fecha_emision) <= ?';
                $params[] = $filters['fecha_hasta'];
            }

            $whereClause = implode(' AND ', $where);

            // Contar total
            $countStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM recibos r
                 LEFT JOIN pagos p ON r.pago_id = p.id
                 WHERE $whereClause",
                $params
            );
            $total = $countStmt->fetch()['total'];

            // Obtener recibos
            $stmt = $this->db->query(
                "SELECT r.*, 
                        p.numero_pago,
                        pr.numero_prestamo,
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido
                 FROM recibos r
                 LEFT JOIN pagos p ON r.pago_id = p.id
                 LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
                 LEFT JOIN clientes c ON pr.cliente_id = c.id
                 WHERE $whereClause
                 ORDER BY r.fecha_emision DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            $recibos = $stmt->fetchAll();

            sendResponse([
                'items' => $recibos,
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
            sendError('Error al obtener recibos: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query(
                "SELECT r.*, 
                        p.numero_pago,
                        pr.numero_prestamo,
                        c.nombre as cliente_nombre, 
                        c.apellido as cliente_apellido,
                        c.cedula as cliente_cedula
                 FROM recibos r
                 LEFT JOIN pagos p ON r.pago_id = p.id
                 LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
                 LEFT JOIN clientes c ON pr.cliente_id = c.id
                 WHERE r.id = ?",
                [$id]
            );

            $recibo = $stmt->fetch();

            if (!$recibo) {
                sendError('Recibo no encontrado', 404);
                return;
            }

            sendResponse($recibo);
        } catch (Exception $e) {
            sendError('Error al obtener recibo: ' . $e->getMessage(), 500);
        }
    }

    public function create($data, $user) {
        try {
            // Validaciones
            if (empty($data['pago_id'])) {
                sendError('Pago es requerido', 400);
                return;
            }

            // Generar número de recibo
            $numeroRecibo = 'REC-' . date('Ymd') . '-' . str_pad(
                $this->db->query("SELECT COUNT(*) + 1 as num FROM recibos WHERE DATE(fecha_emision) = CURDATE()")->fetch()['num'],
                4,
                '0',
                STR_PAD_LEFT
            );

            $this->db->query(
                "INSERT INTO recibos (
                    pago_id, numero_recibo, fecha_emision, monto,
                    metodo_pago, observaciones, estado
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?)",
                [
                    $data['pago_id'],
                    $numeroRecibo,
                    $data['monto'] ?? null,
                    $data['metodo_pago'] ?? null,
                    $data['observaciones'] ?? null,
                    $data['estado'] ?? 'emitido'
                ]
            );

            $reciboId = $this->db->lastInsertId();

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'recibo.creado',
                'recibos',
                $reciboId,
                null,
                ['numero_recibo' => $numeroRecibo, 'pago_id' => $data['pago_id']]
            );

            $this->getById($reciboId);
        } catch (Exception $e) {
            sendError('Error al crear recibo: ' . $e->getMessage(), 500);
        }
    }

    public function update($id, $data, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM recibos WHERE id = ?", [$id]);
            $recibo = $stmt->fetch();

            if (!$recibo) {
                sendError('Recibo no encontrado', 404);
                return;
            }

            $this->db->getConnection()->beginTransaction();

            $updates = [];
            $params = [];

            if (isset($data['monto'])) {
                $updates[] = 'monto = ?';
                $params[] = $data['monto'];
            }

            if (isset($data['metodo_pago'])) {
                $updates[] = 'metodo_pago = ?';
                $params[] = $data['metodo_pago'];
            }

            if (isset($data['observaciones'])) {
                $updates[] = 'observaciones = ?';
                $params[] = $data['observaciones'];
            }

            if (isset($data['estado'])) {
                $updates[] = 'estado = ?';
                $params[] = $data['estado'];
            }

            if (count($updates) > 0) {
                $params[] = $id;
                $this->db->query(
                    "UPDATE recibos SET " . implode(', ', $updates) . " WHERE id = ?",
                    $params
                );

                // Auditoría
                $this->auditoriaService->registrar(
                    $user['id'],
                    'recibo.actualizado',
                    'recibos',
                    $id,
                    $recibo,
                    array_merge($recibo, $data)
                );
            }

            $this->db->getConnection()->commit();

            $this->getById($id);
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al actualizar recibo: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id, $user) {
        try {
            $stmt = $this->db->query("SELECT * FROM recibos WHERE id = ?", [$id]);
            $recibo = $stmt->fetch();

            if (!$recibo) {
                sendError('Recibo no encontrado', 404);
                return;
            }

            $this->db->query("DELETE FROM recibos WHERE id = ?", [$id]);

            // Auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'recibo.eliminado',
                'recibos',
                $id,
                $recibo,
                null
            );

            sendResponse(['message' => 'Recibo eliminado correctamente']);
        } catch (Exception $e) {
            sendError('Error al eliminar recibo: ' . $e->getMessage(), 500);
        }
    }
}
