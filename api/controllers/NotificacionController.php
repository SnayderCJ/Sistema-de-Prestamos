<?php
/**
 * Controlador de Notificaciones
 */

require_once __DIR__ . '/../services/NotificacionService.php';

class NotificacionController {
    private $notificacionService;
    
    public function __construct() {
        $this->notificacionService = new NotificacionService();
    }
    
    /**
     * Registrar token de dispositivo
     */
    public function registrarDispositivo($data, $user) {
        if (!isset($data['token']) || !isset($data['plataforma'])) {
            sendError('Token y plataforma son requeridos', 400);
            return;
        }
        
        $token = $data['token'];
        $plataforma = $data['plataforma'];
        $modelo = $data['modelo'] ?? null;
        
        if (!in_array($plataforma, ['android', 'ios'])) {
            sendError('Plataforma debe ser android o ios', 400);
            return;
        }
        
        $dispositivoId = $this->notificacionService->registrarDispositivo(
            $user['id'],
            $token,
            $plataforma,
            $modelo
        );
        
        sendResponse([
            'message' => 'Dispositivo registrado correctamente',
            'dispositivo_id' => $dispositivoId
        ]);
    }
    
    /**
     * Desactivar dispositivo
     */
    public function desactivarDispositivo($data, $user) {
        if (!isset($data['token'])) {
            sendError('Token requerido', 400);
            return;
        }
        
        $this->notificacionService->desactivarDispositivo($user['id'], $data['token']);
        
        sendResponse(['message' => 'Dispositivo desactivado correctamente']);
    }
    
    /**
     * Obtener notificaciones del usuario
     */
    public function obtenerNotificaciones($user, $filtros = []) {
        $notificaciones = $this->notificacionService->obtenerNotificaciones($user['id'], $filtros);
        sendResponse($notificaciones);
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($notificacionId, $user) {
        $this->notificacionService->marcarComoLeida($notificacionId, $user['id']);
        sendResponse(['message' => 'Notificación marcada como leída']);
    }
    
    /**
     * Marcar todas como leídas
     */
    public function marcarTodasComoLeidas($user) {
        $this->notificacionService->marcarTodasComoLeidas($user['id']);
        sendResponse(['message' => 'Todas las notificaciones marcadas como leídas']);
    }
    
    /**
     * Obtener cantidad de no leídas
     */
    public function obtenerCantidadNoLeidas($user) {
        $cantidad = $this->notificacionService->obtenerCantidadNoLeidas($user['id']);
        sendResponse(['cantidad' => $cantidad]);
    }
    
    /**
     * Enviar notificación de prueba (solo admin)
     */
    public function enviarPrueba($data, $user) {
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden enviar notificaciones de prueba', 403);
            return;
        }
        
        $titulo = $data['titulo'] ?? 'Notificación de Prueba';
        $mensaje = $data['mensaje'] ?? 'Esta es una notificación de prueba';
        $tipo = $data['tipo'] ?? 'info';
        
        $notificacionId = $this->notificacionService->enviarNotificacion(
            $user['id'],
            $titulo,
            $mensaje,
            $tipo,
            $data['datos'] ?? []
        );
        
        sendResponse([
            'message' => 'Notificación enviada',
            'notificacion_id' => $notificacionId
        ]);
    }
}

