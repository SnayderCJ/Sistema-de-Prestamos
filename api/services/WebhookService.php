<?php
/**
 * Servicio de Webhooks
 */

class WebhookService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registrar un webhook
     */
    public function registrarWebhook($usuarioId, $url, $eventos, $activo = true) {
        $this->db->query(
            "INSERT INTO webhooks (usuario_id, url, eventos, activo, fecha_creacion)
             VALUES (?, ?, ?, ?, NOW())",
            [$usuarioId, $url, json_encode($eventos), $activo ? 1 : 0]
        );
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar webhook
     */
    public function actualizarWebhook($webhookId, $url, $eventos, $activo) {
        $this->db->query(
            "UPDATE webhooks 
             SET url = ?, eventos = ?, activo = ?, fecha_actualizacion = NOW()
             WHERE id = ?",
            [$url, json_encode($eventos), $activo ? 1 : 0, $webhookId]
        );
    }
    
    /**
     * Eliminar webhook
     */
    public function eliminarWebhook($webhookId) {
        $this->db->query("DELETE FROM webhooks WHERE id = ?", [$webhookId]);
    }
    
    /**
     * Obtener webhooks de un usuario
     */
    public function obtenerWebhooks($usuarioId) {
        $stmt = $this->db->query(
            "SELECT * FROM webhooks WHERE usuario_id = ? ORDER BY fecha_creacion DESC",
            [$usuarioId]
        );
        
        $webhooks = $stmt->fetchAll();
        
        foreach ($webhooks as &$webhook) {
            $webhook['eventos'] = json_decode($webhook['eventos'], true);
        }
        
        return $webhooks;
    }
    
    /**
     * Disparar webhook para un evento
     */
    public function dispararWebhook($evento, $datos) {
        // Obtener webhooks activos que escuchan este evento
        $stmt = $this->db->query(
            "SELECT * FROM webhooks WHERE activo = 1"
        );
        
        $webhooks = $stmt->fetchAll();
        
        $resultados = [];
        
        foreach ($webhooks as $webhook) {
            $eventos = json_decode($webhook['eventos'], true);
            
            // Verificar si el webhook escucha este evento
            if (in_array($evento, $eventos) || in_array('*', $eventos)) {
                try {
                    $resultado = $this->enviarWebhook($webhook['url'], $evento, $datos);
                    $resultados[] = [
                        'webhook_id' => $webhook['id'],
                        'url' => $webhook['url'],
                        'exitoso' => $resultado['exitoso'],
                        'respuesta' => $resultado['respuesta']
                    ];
                    
                    // Registrar intento
                    $this->registrarIntento($webhook['id'], $evento, $resultado['exitoso'], $resultado['respuesta']);
                    
                } catch (Exception $e) {
                    $resultados[] = [
                        'webhook_id' => $webhook['id'],
                        'url' => $webhook['url'],
                        'exitoso' => false,
                        'error' => $e->getMessage()
                    ];
                    
                    $this->registrarIntento($webhook['id'], $evento, false, $e->getMessage());
                }
            }
        }
        
        return $resultados;
    }
    
    /**
     * Enviar webhook a una URL
     */
    private function enviarWebhook($url, $evento, $datos) {
        $payload = [
            'evento' => $evento,
            'timestamp' => time(),
            'datos' => $datos
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: ERP-Prestamos-Webhook/1.0'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión: $error");
        }
        
        return [
            'exitoso' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'respuesta' => $response
        ];
    }
    
    /**
     * Registrar intento de webhook
     */
    private function registrarIntento($webhookId, $evento, $exitoso, $respuesta) {
        $this->db->query(
            "INSERT INTO webhook_intentos (webhook_id, evento, exitoso, respuesta, fecha_intento)
             VALUES (?, ?, ?, ?, NOW())",
            [$webhookId, $evento, $exitoso ? 1 : 0, $respuesta]
        );
        
        // Si falla 3 veces consecutivas, desactivar webhook
        if (!$exitoso) {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as fallos FROM webhook_intentos 
                 WHERE webhook_id = ? AND exitoso = 0 
                 AND fecha_intento > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 ORDER BY fecha_intento DESC LIMIT 3",
                [$webhookId]
            );
            
            $result = $stmt->fetch();
            if ($result['fallos'] >= 3) {
                $this->db->query(
                    "UPDATE webhooks SET activo = 0 WHERE id = ?",
                    [$webhookId]
                );
            }
        }
    }
    
    /**
     * Obtener historial de intentos de un webhook
     */
    public function obtenerHistorial($webhookId, $limite = 50) {
        $stmt = $this->db->query(
            "SELECT * FROM webhook_intentos 
             WHERE webhook_id = ? 
             ORDER BY fecha_intento DESC 
             LIMIT ?",
            [$webhookId, $limite]
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Reactivar webhook
     */
    public function reactivarWebhook($webhookId) {
        $this->db->query(
            "UPDATE webhooks SET activo = 1 WHERE id = ?",
            [$webhookId]
        );
    }
}

