<?php
/**
 * Controlador de Clientes
 */

class ClienteController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['cedula'])) {
            $where[] = "cedula LIKE ?";
            $params[] = '%' . $filters['cedula'] . '%';
        }
        
        if (isset($filters['nombre'])) {
            $where[] = "(nombre LIKE ? OR apellido LIKE ?)";
            $params[] = '%' . $filters['nombre'] . '%';
            $params[] = '%' . $filters['nombre'] . '%';
        }
        
        if (isset($filters['estado_credito'])) {
            $where[] = "estado_credito = ?";
            $params[] = $filters['estado_credito'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM clientes WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener clientes
        $stmt = $this->db->query(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM prestamos WHERE cliente_id = c.id) as total_prestamos,
                    (SELECT COUNT(*) FROM prestamos WHERE cliente_id = c.id AND estado = 'vigente') as prestamos_activos,
                    (SELECT SUM(saldo_pendiente) FROM prestamos WHERE cliente_id = c.id AND estado = 'vigente') as deuda_total
             FROM clientes c
             WHERE $whereClause
             ORDER BY c.fecha_creacion DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $clientes = $stmt->fetchAll();
        
        sendPaginatedResponse($clientes, $total, $page, $perPage);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM clientes WHERE id = ?",
            [$id]
        );
        
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            sendError('Cliente no encontrado', 404);
        }
        
        // Obtener data créditos si existe
        $dataCreditosStmt = $this->db->query(
            "SELECT * FROM data_creditos WHERE cedula = ?",
            [$cliente['cedula']]
        );
        $cliente['data_creditos'] = $dataCreditosStmt->fetch();
        
        // Obtener préstamos
        $prestamosStmt = $this->db->query(
            "SELECT * FROM prestamos WHERE cliente_id = ? ORDER BY fecha_creacion DESC",
            [$id]
        );
        $cliente['prestamos'] = $prestamosStmt->fetchAll();
        
        sendResponse($cliente);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['cedula']) || !validateCedula($data['cedula'])) {
            $errors[] = 'Cédula inválida';
        }
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!isset($data['apellido']) || empty($data['apellido'])) {
            $errors[] = 'Apellido es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar si ya existe
        $stmt = $this->db->query("SELECT id FROM clientes WHERE cedula = ?", [$data['cedula']]);
        if ($stmt->fetch()) {
            sendError('Ya existe un cliente con esta cédula', 400);
        }
        
        $this->db->query(
            "INSERT INTO clientes (
                cedula, nombre, apellido, fecha_nacimiento, email, telefono,
                telefono_alternativo, direccion, ciudad, provincia, ocupacion,
                ingresos_mensuales, referencia_personal_nombre, referencia_personal_telefono, referencia_personal_direccion,
                referencia_familiar_nombre, referencia_familiar_telefono, referencia_familiar_direccion,
                referencia_laboral_nombre, referencia_laboral_telefono, referencia_laboral_direccion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['cedula'],
                sanitizeInput($data['nombre']),
                sanitizeInput($data['apellido']),
                $data['fecha_nacimiento'] ?? null,
                $data['email'] ?? null,
                $data['telefono'] ?? null,
                $data['telefono_alternativo'] ?? null,
                $data['direccion'] ?? null,
                $data['ciudad'] ?? null,
                $data['provincia'] ?? null,
                $data['ocupacion'] ?? null,
                $data['ingresos_mensuales'] ?? null,
                $data['referencia_personal_nombre'] ?? null,
                $data['referencia_personal_telefono'] ?? null,
                $data['referencia_personal_direccion'] ?? null,
                $data['referencia_familiar_nombre'] ?? null,
                $data['referencia_familiar_telefono'] ?? null,
                $data['referencia_familiar_direccion'] ?? null,
                $data['referencia_laboral_nombre'] ?? null,
                $data['referencia_laboral_telefono'] ?? null,
                $data['referencia_laboral_direccion'] ?? null
            ]
        );
        
        $clienteId = $this->db->lastInsertId();
        $this->getById($clienteId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'nombre', 'apellido', 'fecha_nacimiento', 'email', 'telefono',
            'telefono_alternativo', 'direccion', 'ciudad', 'provincia',
            'ocupacion', 'ingresos_mensuales', 'referencia_personal_nombre',
            'referencia_personal_telefono', 'referencia_familiar_nombre',
            'referencia_familiar_telefono', 'score_credito', 'estado_credito'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            sendError('No hay campos para actualizar', 400);
        }
        
        $params[] = $id;
        $this->db->query(
            "UPDATE clientes SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
    
    public function delete($id) {
        // Verificar si tiene préstamos activos
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM prestamos WHERE cliente_id = ? AND estado IN ('vigente', 'vencido')",
            [$id]
        );
        
        $result = $stmt->fetch();
        if ($result['total'] > 0) {
            sendError('No se puede eliminar un cliente con préstamos activos', 400);
        }
        
        $this->db->query("DELETE FROM clientes WHERE id = ?", [$id]);
        
        sendResponse(['message' => 'Cliente eliminado correctamente']);
    }
}

