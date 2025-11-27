<?php
/**
 * Controlador de Webhooks
 */

require_once __DIR__ . '/../services/WebhookService.php';

class WebhookController {
    private $webhookService;
    
    public function __construct() {
        $this->webhookService = new WebhookService();
    }
    
    /**
     * Obtener webhooks del usuario
     */
    public function getAll($user) {
        $webhooks = $this->webhookService->obtenerWebhooks($user['id']);
        sendResponse($webhooks);
    }
    
    /**
     * Obtener webhook por ID
     */
    public function getById($id, $user) {
        $webhooks = $this->webhookService->obtenerWebhooks($user['id']);
        
        $webhook = null;
        foreach ($webhooks as $w) {
            if ($w['id'] == $id) {
                $webhook = $w;
                break;
            }
        }
        
        if (!$webhook) {
            sendError('Webhook no encontrado', 404);
            return;
        }
        
        sendResponse($webhook);
    }
    
    /**
     * Crear webhook
     */
    public function create($data, $user) {
        if (!isset($data['url']) || empty($data['url'])) {
            sendError('URL es requerida', 400);
            return;
        }
        
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            sendError('URL inválida', 400);
            return;
        }
        
        if (!isset($data['eventos']) || !is_array($data['eventos']) || empty($data['eventos'])) {
            sendError('Eventos son requeridos', 400);
            return;
        }
        
        $webhookId = $this->webhookService->registrarWebhook(
            $user['id'],
            $data['url'],
            $data['eventos'],
            $data['activo'] ?? true
        );
        
        $this->getById($webhookId, $user);
    }
    
    /**
     * Actualizar webhook
     */
    public function update($id, $data, $user) {
        if (isset($data['url']) && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            sendError('URL inválida', 400);
            return;
        }
        
        $webhook = $this->getById($id, $user);
        if (!$webhook) {
            return; // Ya se envió el error
        }
        
        $url = $data['url'] ?? $webhook['url'];
        $eventos = $data['eventos'] ?? $webhook['eventos'];
        $activo = isset($data['activo']) ? (bool)$data['activo'] : $webhook['activo'];
        
        $this->webhookService->actualizarWebhook($id, $url, $eventos, $activo);
        
        $this->getById($id, $user);
    }
    
    /**
     * Eliminar webhook
     */
    public function delete($id, $user) {
        $webhook = $this->getById($id, $user);
        if (!$webhook) {
            return;
        }
        
        $this->webhookService->eliminarWebhook($id);
        sendResponse(['message' => 'Webhook eliminado correctamente']);
    }
    
    /**
     * Obtener historial de intentos
     */
    public function getHistorial($id, $user) {
        $webhook = $this->getById($id, $user);
        if (!$webhook) {
            return;
        }
        
        $historial = $this->webhookService->obtenerHistorial($id);
        sendResponse($historial);
    }
    
    /**
     * Reactivar webhook
     */
    public function reactivar($id, $user) {
        $webhook = $this->getById($id, $user);
        if (!$webhook) {
            return;
        }
        
        $this->webhookService->reactivarWebhook($id);
        sendResponse(['message' => 'Webhook reactivado correctamente']);
    }
    
    /**
     * Probar webhook (disparar evento de prueba)
     */
    public function probar($id, $user) {
        $webhook = $this->getById($id, $user);
        if (!$webhook) {
            return;
        }
        
        $datosPrueba = [
            'mensaje' => 'Este es un evento de prueba',
            'timestamp' => time()
        ];
        
        $resultado = $this->webhookService->dispararWebhook('prueba', $datosPrueba);
        
        sendResponse([
            'message' => 'Webhook probado',
            'resultado' => $resultado
        ]);
    }
}

