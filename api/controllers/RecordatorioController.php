<?php
/**
 * Controlador de Recordatorios
 */

require_once __DIR__ . '/../services/RecordatorioService.php';

class RecordatorioController {
    private $recordatorioService;
    
    public function __construct() {
        $this->recordatorioService = new RecordatorioService();
    }
    
    /**
     * Procesar recordatorios automáticos
     * Endpoint para ejecutar desde cron job
     */
    public function procesar() {
        try {
            $resultado = $this->recordatorioService->procesarRecordatorios();
            
            sendResponse([
                'success' => true,
                'message' => 'Recordatorios procesados',
                'data' => $resultado
            ]);
        } catch (Exception $e) {
            sendError('Error al procesar recordatorios: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Enviar recordatorio manual
     */
    public function enviarManual() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['prestamo_id'])) {
                sendError('ID de préstamo es requerido', 400);
                return;
            }
            
            require_once __DIR__ . '/../services/WhatsAppService.php';
            require_once __DIR__ . '/../services/EmailService.php';
            
            $whatsappService = new WhatsAppService();
            $emailService = new EmailService();
            
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT p.*, c.telefono, c.email, c.nombre, c.apellido,
                        DATEDIFF(CURDATE(), MAX(cp.fecha_vencimiento)) as dias_vencido
                 FROM prestamos p
                 INNER JOIN clientes c ON p.cliente_id = c.id
                 LEFT JOIN cuotas_prestamos cp ON p.id = cp.prestamo_id AND cp.estado != 'pagada'
                 WHERE p.id = ?
                 GROUP BY p.id",
                [$data['prestamo_id']]
            );
            $prestamo = $stmt->fetch();
            
            if (!$prestamo) {
                sendError('Préstamo no encontrado', 404);
                return;
            }
            
            $diasVencido = $prestamo['dias_vencido'] ?? 0;
            
            // Enviar por WhatsApp
            if (!empty($prestamo['telefono'])) {
                try {
                    $whatsappService->enviarRecordatorioPago($data['prestamo_id'], $diasVencido);
                } catch (Exception $e) {
                    error_log('Error WhatsApp: ' . $e->getMessage());
                }
            }
            
            // Enviar por Email
            if (!empty($prestamo['email'])) {
                try {
                    $recordatorioService = new RecordatorioService();
                    $recordatorioService->enviarEmailRecordatorio($prestamo, $diasVencido);
                } catch (Exception $e) {
                    error_log('Error Email: ' . $e->getMessage());
                }
            }
            
            sendResponse([
                'success' => true,
                'message' => 'Recordatorio enviado exitosamente'
            ]);
        } catch (Exception $e) {
            sendError('Error al enviar recordatorio: ' . $e->getMessage(), 500);
        }
    }
}

