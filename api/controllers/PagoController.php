<?php
/**
 * Controlador de Pagos
 */

require_once __DIR__ . '/../services/PrestamoService.php';

class PagoController {
    private $db;
    private $prestamoService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->prestamoService = new PrestamoService();
    }
    
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['prestamo_id'])) {
            $where[] = "p.prestamo_id = ?";
            $params[] = $filters['prestamo_id'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(p.fecha_pago) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(p.fecha_pago) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM pagos p WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener pagos
        $stmt = $this->db->query(
            "SELECT p.*, 
                    pr.numero_prestamo,
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido
             FROM pagos p
             LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
             LEFT JOIN clientes c ON pr.cliente_id = c.id
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             WHERE $whereClause
             ORDER BY p.fecha_pago DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $pagos = $stmt->fetchAll();
        
        sendPaginatedResponse($pagos, $total, $page, $perPage);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT p.*, 
                    pr.numero_prestamo,
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido
             FROM pagos p
             LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
             LEFT JOIN clientes c ON pr.cliente_id = c.id
             WHERE p.id = ?",
            [$id]
        );
        
        $pago = $stmt->fetch();
        
        if (!$pago) {
            sendError('Pago no encontrado', 404);
        }
        
        sendResponse($pago);
    }
    
    public function create($data, $user) {
        $errors = [];
        
        if (!isset($data['prestamo_id']) || !$data['prestamo_id']) {
            $errors[] = 'Préstamo es requerido';
        }
        
        if (!isset($data['monto']) || !validateMonto($data['monto'])) {
            $errors[] = 'Monto inválido';
        }
        
        if (!isset($data['metodo_pago'])) {
            $errors[] = 'Método de pago es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el préstamo existe
        $prestamoStmt = $this->db->query(
            "SELECT * FROM prestamos WHERE id = ?",
            [$data['prestamo_id']]
        );
        $prestamo = $prestamoStmt->fetch();
        
        if (!$prestamo) {
            sendError('Préstamo no encontrado', 404);
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            $monto = $data['monto'];
            $prestamoId = $data['prestamo_id'];
            
            // Obtener cuotas pendientes ordenadas por fecha de vencimiento
            $cuotasStmt = $this->db->query(
                "SELECT * FROM cuotas_prestamos 
                 WHERE prestamo_id = ? 
                 AND estado != 'pagada'
                 ORDER BY fecha_vencimiento ASC",
                [$prestamoId]
            );
            $cuotas = $cuotasStmt->fetchAll();
            
            if (empty($cuotas)) {
                sendError('No hay cuotas pendientes', 400);
            }
            
            $montoRestante = $monto;
            $cuotasPagadas = [];
            
            // Aplicar pago a cuotas
            foreach ($cuotas as $cuota) {
                if ($montoRestante <= 0) {
                    break;
                }
                
                $montoCuota = $cuota['monto_cuota'] + $cuota['mora'] - $cuota['monto_pagado'];
                
                if ($montoRestante >= $montoCuota) {
                    // Pagar cuota completa
                    $montoPago = $montoCuota;
                    $montoRestante -= $montoPago;
                    $nuevoEstado = 'pagada';
                } else {
                    // Pago parcial
                    $montoPago = $montoRestante;
                    $montoRestante = 0;
                    $nuevoEstado = 'parcial';
                }
                
                // Distribuir entre capital, interés y mora
                $capitalPago = min($montoPago, $cuota['capital'] - ($cuota['monto_pagado'] * ($cuota['capital'] / $cuota['monto_cuota'])));
                $interesPago = min($montoPago - $capitalPago, $cuota['interes']);
                $moraPago = $montoPago - $capitalPago - $interesPago;
                
                // Actualizar cuota
                $nuevoMontoPagado = $cuota['monto_pagado'] + $montoPago;
                $this->db->query(
                    "UPDATE cuotas_prestamos 
                     SET monto_pagado = ?, estado = ?
                     WHERE id = ?",
                    [$nuevoMontoPagado, $nuevoEstado, $cuota['id']]
                );
                
                $cuotasPagadas[] = [
                    'cuota_id' => $cuota['id'],
                    'monto' => $montoPago,
                    'capital' => $capitalPago,
                    'interes' => $interesPago,
                    'mora' => $moraPago
                ];
            }
            
            // Generar número de recibo
            $numeroRecibo = $this->generarNumeroRecibo();
            
            // Crear registro de pago
            $totalCapital = array_sum(array_column($cuotasPagadas, 'capital'));
            $totalInteres = array_sum(array_column($cuotasPagadas, 'interes'));
            $totalMora = array_sum(array_column($cuotasPagadas, 'mora'));
            
            $this->db->query(
                "INSERT INTO pagos (
                    prestamo_id, cuota_id, numero_recibo, monto, capital, interes, mora,
                    metodo_pago, numero_comprobante, usuario_id, sucursal_id, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $prestamoId,
                    $cuotasPagadas[0]['cuota_id'] ?? null,
                    $numeroRecibo,
                    $monto,
                    $totalCapital,
                    $totalInteres,
                    $totalMora,
                    $data['metodo_pago'],
                    $data['numero_comprobante'] ?? null,
                    $user['id'],
                    $user['sucursal_id'] ?? 1,
                    $data['observaciones'] ?? null
                ]
            );
            
            // Actualizar estado del préstamo si todas las cuotas están pagadas
            $cuotasPendientesStmt = $this->db->query(
                "SELECT COUNT(*) as total FROM cuotas_prestamos WHERE prestamo_id = ? AND estado != 'pagada'",
                [$prestamoId]
            );
            
            if ($cuotasPendientesStmt->fetch()['total'] == 0) {
                $this->db->query(
                    "UPDATE prestamos SET estado = 'pagado' WHERE id = ?",
                    [$prestamoId]
                );
            } else {
                // Actualizar a vigente si estaba vencido
                $this->db->query(
                    "UPDATE prestamos SET estado = 'vigente' WHERE id = ? AND estado = 'vencido'",
                    [$prestamoId]
                );
            }
            
            $this->db->getConnection()->commit();
            
            $pagoId = $this->db->lastInsertId();
            
            // Generar recibo automáticamente
            try {
                require_once __DIR__ . '/../services/ReciboService.php';
                $reciboService = new ReciboService();
                $reciboService->generarRecibo($pagoId);
            } catch (Exception $e) {
                error_log("Error generando recibo automático: " . $e->getMessage());
            }
            
            // Disparar webhook
            try {
                require_once __DIR__ . '/../services/WebhookService.php';
                $webhookService = new WebhookService();
                $webhookService->dispararWebhook('pago.registrado', [
                    'pago_id' => $pagoId,
                    'prestamo_id' => $prestamoId,
                    'monto' => $monto,
                    'fecha' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                error_log("Error disparando webhook: " . $e->getMessage());
            }
            
            // Enviar notificaciones automáticas (WhatsApp y Email)
            try {
                // Verificar si las notificaciones están activas
                $stmt = $this->db->query(
                    "SELECT valor FROM configuracion_sistema WHERE clave = 'whatsapp_notificaciones_activas'"
                );
                $whatsappActivo = $stmt->fetch()['valor'] ?? '1';
                
                $stmt = $this->db->query(
                    "SELECT valor FROM configuracion_sistema WHERE clave = 'email_notificaciones_activas'"
                );
                $emailActivo = $stmt->fetch()['valor'] ?? '1';
                
                if ($whatsappActivo === '1') {
                    require_once __DIR__ . '/../services/WhatsAppService.php';
                    $whatsappService = new WhatsAppService();
                    $whatsappService->enviarNotificacionPago($pagoId);
                }
                
                if ($emailActivo === '1') {
                    require_once __DIR__ . '/../services/EmailService.php';
                    $emailService = new EmailService();
                    $emailService->enviarNotificacionPago($pagoId);
                }
            } catch (Exception $e) {
                error_log("Error enviando notificaciones: " . $e->getMessage());
                // No fallar el pago si las notificaciones fallan
            }
            
            $this->getById($pagoId);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error procesando pago: " . $e->getMessage());
            sendError('Error al procesar el pago', 500);
        }
    }
    
    private function generarNumeroRecibo() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM pagos WHERE YEAR(fecha_pago) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "REC-$year-$numero";
    }
}

