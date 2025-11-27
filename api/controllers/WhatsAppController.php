<?php
/**
 * Controlador de WhatsApp CRM
 */

require_once __DIR__ . '/../services/WhatsAppService.php';

class WhatsAppController {
    private $db;
    private $whatsappService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->whatsappService = new WhatsAppService();
    }
    
    /**
     * Obtener historial de mensajes
     */
    public function getHistorial($filters = []) {
        try {
            $historial = $this->whatsappService->obtenerHistorial($filters);
            sendResponse($historial);
        } catch (Exception $e) {
            sendError('Error al obtener historial: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener conversaciones
     */
    public function getConversaciones($filters = []) {
        try {
            $conversaciones = $this->whatsappService->obtenerConversaciones($filters);
            sendResponse($conversaciones);
        } catch (Exception $e) {
            sendError('Error al obtener conversaciones: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Enviar mensaje manual
     */
    public function enviarMensaje() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['numero']) || empty($data['mensaje'])) {
                sendError('Número y mensaje son requeridos', 400);
                return;
            }
            
            $result = $this->whatsappService->enviarMensaje(
                $data['numero'],
                $data['mensaje'],
                $data['template'] ?? null
            );
            
            sendResponse([
                'success' => true,
                'message' => 'Mensaje enviado exitosamente',
                'data' => $result
            ]);
        } catch (Exception $e) {
            sendError('Error al enviar mensaje: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Enviar notificación de pago
     */
    public function enviarNotificacionPago($pagoId) {
        try {
            $this->whatsappService->enviarNotificacionPago($pagoId);
            
            sendResponse([
                'success' => true,
                'message' => 'Notificación enviada exitosamente'
            ]);
        } catch (Exception $e) {
            sendError('Error al enviar notificación: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Enviar recordatorio de pago
     */
    public function enviarRecordatorio() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['prestamo_id'])) {
                sendError('ID de préstamo es requerido', 400);
                return;
            }
            
            $this->whatsappService->enviarRecordatorioPago(
                $data['prestamo_id'],
                $data['dias_vencido'] ?? 0
            );
            
            sendResponse([
                'success' => true,
                'message' => 'Recordatorio enviado exitosamente'
            ]);
        } catch (Exception $e) {
            sendError('Error al enviar recordatorio: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Webhook para recibir mensajes
     */
    public function webhook() {
        try {
            // Verificar firma de Facebook (opcional pero recomendado)
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Verificar que es una verificación del webhook
            if (isset($_GET['hub_mode']) && $_GET['hub_mode'] === 'subscribe') {
                $verify_token = $_GET['hub_verify_token'];
                $challenge = $_GET['hub_challenge'];
                
                // Verificar token (debe coincidir con el configurado)
                $stmt = $this->db->query(
                    "SELECT valor FROM configuracion_sistema WHERE clave = 'whatsapp_webhook_token'"
                );
                $config = $stmt->fetch();
                
                if ($verify_token === ($config['valor'] ?? 'mi_token_secreto')) {
                    echo $challenge;
                    exit;
                }
            }
            
            // Procesar mensaje recibido
            if (!empty($data)) {
                $this->whatsappService->procesarWebhook($data);
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
        } catch (Exception $e) {
            error_log('Error en webhook WhatsApp: ' . $e->getMessage());
            http_response_code(200); // Siempre responder 200 para evitar reintentos
        }
    }
    
    /**
     * Obtener estadísticas
     */
    public function getEstadisticas($filters = []) {
        try {
            $fechaDesde = $filters['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
            $fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');
            
            // Total mensajes enviados
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total FROM whatsapp_historial 
                 WHERE estado = 'enviado' AND DATE(fecha_envio) BETWEEN ? AND ?",
                [$fechaDesde, $fechaHasta]
            );
            $totalEnviados = $stmt->fetch()['total'];
            
            // Total mensajes recibidos
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total FROM whatsapp_historial 
                 WHERE estado = 'recibido' AND DATE(fecha_envio) BETWEEN ? AND ?",
                [$fechaDesde, $fechaHasta]
            );
            $totalRecibidos = $stmt->fetch()['total'];
            
            // Total conversaciones únicas
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT numero) as total FROM whatsapp_historial 
                 WHERE DATE(fecha_envio) BETWEEN ? AND ?",
                [$fechaDesde, $fechaHasta]
            );
            $totalConversaciones = $stmt->fetch()['total'];
            
            // Mensajes por día
            $stmt = $this->db->query(
                "SELECT DATE(fecha_envio) as fecha, COUNT(*) as cantidad
                 FROM whatsapp_historial
                 WHERE DATE(fecha_envio) BETWEEN ? AND ?
                 GROUP BY DATE(fecha_envio)
                 ORDER BY fecha ASC",
                [$fechaDesde, $fechaHasta]
            );
            $mensajesPorDia = $stmt->fetchAll();
            
            sendResponse([
                'total_enviados' => $totalEnviados,
                'total_recibidos' => $totalRecibidos,
                'total_conversaciones' => $totalConversaciones,
                'mensajes_por_dia' => $mensajesPorDia
            ]);
        } catch (Exception $e) {
            sendError('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }
}

