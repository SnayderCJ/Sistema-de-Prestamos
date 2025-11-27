<?php
/**
 * Controlador de Préstamos
 */

require_once __DIR__ . '/../services/PrestamoService.php';
require_once __DIR__ . '/../services/TasaService.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class PrestamoController {
    private $db;
    private $prestamoService;
    private $tasaService;
    private $auditoriaService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->prestamoService = new PrestamoService();
        $this->tasaService = new TasaService();
        $this->auditoriaService = new AuditoriaService();
    }
    
    public function getAll($user, $page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        // Filtros por rol
        if ($user['rol'] === 'supervisor') {
            $where[] = "p.supervisor_aprobador_id = ?";
            $params[] = $user['id'];
        } elseif ($user['rol'] === 'analista') {
            $where[] = "p.usuario_creador_id = ?";
            $params[] = $user['id'];
        }
        
        // Filtros adicionales
        if (isset($filters['estado'])) {
            $where[] = "p.estado = ?";
            $params[] = $filters['estado'];
        }
        
        if (isset($filters['cliente_id'])) {
            $where[] = "p.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        
        if (isset($filters['cedula'])) {
            $where[] = "c.cedula = ?";
            $params[] = $filters['cedula'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(p.fecha_creacion) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(p.fecha_creacion) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total 
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener préstamos
        $stmt = $this->db->query(
            "SELECT p.*, 
                    c.cedula as cliente_cedula, 
                    c.nombre as cliente_nombre, 
                    c.apellido as cliente_apellido,
                    t.nombre as tasa_nombre,
                    u.nombre as creador_nombre,
                    u.apellido as creador_apellido,
                    s.nombre as sucursal_nombre
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN tasas_interes t ON p.tasa_interes_id = t.id
             LEFT JOIN usuarios u ON p.usuario_creador_id = u.id
             LEFT JOIN sucursales s ON p.sucursal_id = s.id
             WHERE $whereClause
             ORDER BY p.fecha_creacion DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $prestamos = $stmt->fetchAll();
        
        // Calcular estadísticas adicionales
        foreach ($prestamos as &$prestamo) {
            $prestamo['saldo_pendiente'] = $this->prestamoService->calcularSaldoPendiente($prestamo['id']);
            $prestamo['cuotas_pagadas'] = $this->prestamoService->contarCuotasPagadas($prestamo['id']);
            $prestamo['cuotas_vencidas'] = $this->prestamoService->contarCuotasVencidas($prestamo['id']);
        }
        
        sendPaginatedResponse($prestamos, $total, $page, $perPage);
    }
    
    public function getById($id, $user) {
        $stmt = $this->db->query(
            "SELECT p.*, 
                    c.*,
                    t.nombre as tasa_nombre,
                    t.tipo_tasa,
                    u.nombre as creador_nombre,
                    u.apellido as creador_apellido,
                    s.nombre as sucursal_nombre
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN tasas_interes t ON p.tasa_interes_id = t.id
             LEFT JOIN usuarios u ON p.usuario_creador_id = u.id
             LEFT JOIN sucursales s ON p.sucursal_id = s.id
             WHERE p.id = ?",
            [$id]
        );
        
        $prestamo = $stmt->fetch();
        
        if (!$prestamo) {
            sendError('Préstamo no encontrado', 404);
        }
        
        // Verificar permisos
        if ($user['rol'] === 'analista' && $prestamo['usuario_creador_id'] != $user['id']) {
            sendError('No tiene permisos para ver este préstamo', 403);
        }
        
        // Obtener cuotas
        $cuotasStmt = $this->db->query(
            "SELECT * FROM cuotas_prestamos WHERE prestamo_id = ? ORDER BY numero_cuota",
            [$id]
        );
        $prestamo['cuotas'] = $cuotasStmt->fetchAll();
        
        // Obtener pagos
        $pagosStmt = $this->db->query(
            "SELECT * FROM pagos WHERE prestamo_id = ? ORDER BY fecha_pago DESC",
            [$id]
        );
        $prestamo['pagos'] = $pagosStmt->fetchAll();
        
        // Calcular estadísticas
        $prestamo['saldo_pendiente'] = $this->prestamoService->calcularSaldoPendiente($id);
        $prestamo['mora_total'] = $this->prestamoService->calcularMoraTotal($id);
        
        sendResponse($prestamo);
    }
    
    public function create($data, $user) {
        // Validaciones
        $errors = [];
        
        if (!isset($data['cliente_id']) || !$data['cliente_id']) {
            $errors[] = 'Cliente es requerido';
        }
        
        if (!isset($data['monto_solicitado']) || !validateMonto($data['monto_solicitado'], MONTO_MINIMO_PRESTAMO, MONTO_MAXIMO_PRESTAMO)) {
            $errors[] = 'Monto inválido';
        }
        
        if (!isset($data['plazo_meses']) || $data['plazo_meses'] < PLAZO_MINIMO_MESES || $data['plazo_meses'] > PLAZO_MAXIMO_MESES) {
            $errors[] = 'Plazo inválido';
        }
        
        if (!isset($data['tasa_interes_id']) || !$data['tasa_interes_id']) {
            $errors[] = 'Tasa de interés es requerida';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el cliente existe
        $clienteStmt = $this->db->query("SELECT * FROM clientes WHERE id = ?", [$data['cliente_id']]);
        $cliente = $clienteStmt->fetch();
        
        if (!$cliente) {
            sendError('Cliente no encontrado', 404);
        }
        
        // Verificar que la tasa existe y está activa
        $tasaStmt = $this->db->query("SELECT * FROM tasas_interes WHERE id = ? AND activa = 1", [$data['tasa_interes_id']]);
        $tasa = $tasaStmt->fetch();
        
        if (!$tasa) {
            sendError('Tasa de interés no válida', 400);
        }
        
        // Calcular valores del préstamo
        $montoAprobado = $data['monto_solicitado'];
        $tasaMensual = $tasa['tasa_mensual'];
        $plazoMeses = $data['plazo_meses'];
        
        $calculos = $this->prestamoService->calcularPrestamo($montoAprobado, $tasaMensual, $plazoMeses);
        
        // Generar número de préstamo
        $numeroPrestamo = $this->generarNumeroPrestamo();
        
        // Insertar préstamo
        $this->db->getConnection()->beginTransaction();
        
        try {
            $stmt = $this->db->query(
                "INSERT INTO prestamos (
                    numero_prestamo, cliente_id, sucursal_id, usuario_creador_id,
                    tasa_interes_id, monto_solicitado, monto_aprobado,
                    tasa_interes_mensual, plazo_meses, cuota_mensual,
                    monto_total_pagar, interes_total, estado,
                    tipo_prestamo, garantia_tipo, garantia_descripcion,
                    dias_gracia, dias_para_legal, oficial_actuante, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, ?, ?, ?)",
                [
                    $numeroPrestamo,
                    $data['cliente_id'],
                    $user['sucursal_id'] ?? 1,
                    $user['id'],
                    $data['tasa_interes_id'],
                    $montoAprobado,
                    $montoAprobado,
                    $tasaMensual,
                    $plazoMeses,
                    $calculos['cuota_mensual'],
                    $calculos['monto_total'],
                    $calculos['interes_total'],
                    $data['tipo_prestamo'] ?? 'personal',
                    $data['garantia_tipo'] ?? null,
                    $data['garantia_descripcion'] ?? null,
                    $data['dias_gracia'] ?? 0,
                    $data['dias_para_legal'] ?? null,
                    $data['oficial_actuante'] ?? null,
                    $data['observaciones'] ?? null
                ]
            );
            
            $prestamoId = $this->db->lastInsertId();
            
            // Crear cuotas
            $this->prestamoService->crearCuotas($prestamoId, $calculos, $plazoMeses);
            
            $this->db->getConnection()->commit();
            
            // Registrar en auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'crear_prestamo',
                'prestamos',
                $prestamoId,
                null,
                ['numero_prestamo' => $numeroPrestamo, 'monto' => $montoAprobado]
            );
            
            // Disparar webhook
            try {
                require_once __DIR__ . '/../services/WebhookService.php';
                $webhookService = new WebhookService();
                $webhookService->dispararWebhook('prestamo.creado', [
                    'prestamo_id' => $prestamoId,
                    'numero_prestamo' => $numeroPrestamo,
                    'monto' => $montoAprobado,
                    'cliente_id' => $data['cliente_id']
                ]);
            } catch (Exception $e) {
                error_log("Error disparando webhook: " . $e->getMessage());
            }
            
            // Obtener préstamo creado
            $this->getById($prestamoId, $user);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error creando préstamo: " . $e->getMessage());
            
            // Registrar error en auditoría
            $this->auditoriaService->registrar(
                $user['id'],
                'error_crear_prestamo',
                'prestamos',
                null,
                null,
                ['error' => $e->getMessage()]
            );
            
            sendError('Error al crear el préstamo', 500);
        }
    }
    
    public function update($id, $data, $user) {
        // Solo supervisores pueden aprobar/rechazar
        if (isset($data['estado']) && in_array($data['estado'], ['aprobado', 'rechazado'])) {
            $auth = new AuthMiddleware();
            $auth->requireRole(['supervisor', 'admin']);
            
            if ($data['estado'] === 'aprobado') {
                $this->db->query(
                    "UPDATE prestamos 
                     SET estado = 'aprobado', 
                         supervisor_aprobador_id = ?,
                         fecha_aprobacion = NOW()
                     WHERE id = ?",
                    [$user['id'], $id]
                );
            } else {
                $motivoRechazo = $data['motivo_rechazo'] ?? 'Sin motivo especificado';
                $this->db->query(
                    "UPDATE prestamos 
                     SET estado = 'rechazado', 
                         motivo_rechazo = ?,
                         supervisor_aprobador_id = ?
                     WHERE id = ?",
                    [$motivoRechazo, $user['id'], $id]
                );
            }
        }
        
        // Otros campos actualizables
        $allowedFields = [
            'observaciones', 'garantia_tipo', 'garantia_descripcion',
            'tipo_prestamo', 'dias_gracia', 'dias_para_legal', 'oficial_actuante'
        ];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (!empty($updates)) {
            $params[] = $id;
            $this->db->query(
                "UPDATE prestamos SET " . implode(', ', $updates) . " WHERE id = ?",
                $params
            );
        }
        
        $this->getById($id, $user);
    }
    
    public function delete($id, $user) {
        $auth = new AuthMiddleware();
        $auth->requireRole(['admin']);
        
        // Solo se pueden eliminar préstamos pendientes
        $stmt = $this->db->query("SELECT estado FROM prestamos WHERE id = ?", [$id]);
        $prestamo = $stmt->fetch();
        
        if (!$prestamo) {
            sendError('Préstamo no encontrado', 404);
        }
        
        if ($prestamo['estado'] !== 'pendiente') {
            sendError('Solo se pueden eliminar préstamos pendientes', 400);
        }
        
        $this->db->query("DELETE FROM prestamos WHERE id = ?", [$id]);
        
        sendResponse(['message' => 'Préstamo eliminado correctamente']);
    }
    
    private function generarNumeroPrestamo() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM prestamos WHERE YEAR(fecha_creacion) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "PREST-$year-$numero";
    }
}

