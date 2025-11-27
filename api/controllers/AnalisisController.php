<?php
/**
 * Controlador de Análisis de Préstamos
 */

class AnalisisController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['prestamo_id'])) {
            $where[] = "a.prestamo_id = ?";
            $params[] = $filters['prestamo_id'];
        }
        
        if (isset($filters['cliente_id'])) {
            $where[] = "a.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        
        if (isset($filters['analista_id'])) {
            $where[] = "a.analista_id = ?";
            $params[] = $filters['analista_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM analisis_prestamos a WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener análisis
        $stmt = $this->db->query(
            "SELECT a.*, 
                    p.numero_prestamo,
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    u.nombre as analista_nombre,
                    u.apellido as analista_apellido
             FROM analisis_prestamos a
             LEFT JOIN prestamos p ON a.prestamo_id = p.id
             LEFT JOIN clientes c ON a.cliente_id = c.id
             LEFT JOIN usuarios u ON a.analista_id = u.id
             WHERE $whereClause
             ORDER BY a.fecha_analisis DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $analisis = $stmt->fetchAll();
        
        // Decodificar JSON
        foreach ($analisis as &$analisisItem) {
            if ($analisisItem['historial_pagos']) {
                $analisisItem['historial_pagos'] = json_decode($analisisItem['historial_pagos'], true);
            }
        }
        
        sendPaginatedResponse($analisis, $total, $page, $perPage);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT a.*, 
                    p.numero_prestamo,
                    p.monto_aprobado,
                    c.*,
                    u.nombre as analista_nombre,
                    u.apellido as analista_apellido
             FROM analisis_prestamos a
             LEFT JOIN prestamos p ON a.prestamo_id = p.id
             LEFT JOIN clientes c ON a.cliente_id = c.id
             LEFT JOIN usuarios u ON a.analista_id = u.id
             WHERE a.id = ?",
            [$id]
        );
        
        $analisis = $stmt->fetch();
        
        if (!$analisis) {
            sendError('Análisis no encontrado', 404);
        }
        
        if ($analisis['historial_pagos']) {
            $analisis['historial_pagos'] = json_decode($analisis['historial_pagos'], true);
        }
        
        sendResponse($analisis);
    }
    
    public function create($data, $user) {
        $errors = [];
        
        if (!isset($data['prestamo_id']) || !$data['prestamo_id']) {
            $errors[] = 'Préstamo es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Obtener préstamo y cliente
        $prestamoStmt = $this->db->query(
            "SELECT p.*, c.* FROM prestamos p 
             LEFT JOIN clientes c ON p.cliente_id = c.id 
             WHERE p.id = ?",
            [$data['prestamo_id']]
        );
        $prestamo = $prestamoStmt->fetch();
        
        if (!$prestamo) {
            sendError('Préstamo no encontrado', 404);
        }
        
        // Calcular análisis
        $analisis = $this->calcularAnalisis($prestamo);
        
        // Guardar análisis
        $this->db->query(
            "INSERT INTO analisis_prestamos (
                prestamo_id, cliente_id, analista_id, score_credito,
                capacidad_pago, ratio_deuda_ingresos, historial_pagos,
                recomendacion, comentarios
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['prestamo_id'],
                $prestamo['cliente_id'],
                $user['id'],
                $analisis['score_credito'],
                $analisis['capacidad_pago'],
                $analisis['ratio_deuda_ingresos'],
                json_encode($analisis['historial_pagos']),
                $analisis['recomendacion'],
                $data['comentarios'] ?? null
            ]
        );
        
        $analisisId = $this->db->lastInsertId();
        $this->getById($analisisId);
    }
    
    public function update($id, $data, $user) {
        $updates = [];
        $params = [];
        
        $allowedFields = ['recomendacion', 'comentarios', 'score_credito', 'capacidad_pago', 'ratio_deuda_ingresos'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'historial_pagos') {
                    $updates[] = "$field = ?";
                    $params[] = json_encode($data[$field]);
                } else {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            sendError('No hay campos para actualizar', 400);
        }
        
        $params[] = $id;
        $this->db->query(
            "UPDATE analisis_prestamos SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
    
    private function calcularAnalisis($prestamo) {
        // Obtener data créditos
        $dataCreditosStmt = $this->db->query(
            "SELECT * FROM data_creditos WHERE cedula = ?",
            [$prestamo['cedula']]
        );
        $dataCreditos = $dataCreditosStmt->fetch();
        
        $scoreCredito = $dataCreditos['score'] ?? 500;
        
        // Calcular capacidad de pago
        $ingresosMensuales = $prestamo['ingresos_mensuales'] ?? 0;
        $cuotaMensual = $prestamo['cuota_mensual'] ?? 0;
        $capacidadPago = $ingresosMensuales * 0.3; // 30% de ingresos
        
        // Ratio deuda/ingresos
        $deudaTotal = $prestamo['monto_aprobado'] ?? 0;
        $ratioDeudaIngresos = $ingresosMensuales > 0 ? ($deudaTotal / ($ingresosMensuales * 12)) : 0;
        
        // Obtener historial de pagos de otros préstamos
        $historialStmt = $this->db->query(
            "SELECT COUNT(*) as total_prestamos,
                    SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as prestamos_pagados,
                    SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as prestamos_vencidos
             FROM prestamos 
             WHERE cliente_id = ? AND id != ?",
            [$prestamo['cliente_id'], $prestamo['id']]
        );
        $historial = $historialStmt->fetch();
        
        // Calcular recomendación
        $recomendacion = 'rechazado';
        
        if ($scoreCredito >= 700 && $ratioDeudaIngresos < 0.4 && $cuotaMensual <= $capacidadPago) {
            $recomendacion = 'aprobado';
        } elseif ($scoreCredito >= 600 && $ratioDeudaIngresos < 0.5 && $cuotaMensual <= $capacidadPago * 1.2) {
            $recomendacion = 'condicionado';
        }
        
        return [
            'score_credito' => $scoreCredito,
            'capacidad_pago' => $capacidadPago,
            'ratio_deuda_ingresos' => round($ratioDeudaIngresos, 4),
            'historial_pagos' => $historial,
            'recomendacion' => $recomendacion
        ];
    }
}


