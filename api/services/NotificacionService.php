<?php
/**
 * Servicio de Notificaciones Push
 */

class NotificacionService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Enviar notificación push a un usuario
     */
    public function enviarNotificacion($usuarioId, $titulo, $mensaje, $tipo = 'info', $datos = []) {
        // Guardar notificación en base de datos
        $this->db->query(
            "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, datos, leida, fecha_creacion)
             VALUES (?, ?, ?, ?, ?, 0, NOW())",
            [$usuarioId, $titulo, $mensaje, $tipo, json_encode($datos)]
        );
        
        $notificacionId = $this->db->lastInsertId();
        
        // Obtener tokens de dispositivos del usuario
        $stmt = $this->db->query(
            "SELECT token, plataforma FROM dispositivos_push WHERE usuario_id = ? AND activo = 1",
            [$usuarioId]
        );
        
        $dispositivos = $stmt->fetchAll();
        
        // Enviar push a cada dispositivo
        foreach ($dispositivos as $dispositivo) {
            $this->enviarPush($dispositivo, $titulo, $mensaje, $datos);
        }
        
        return $notificacionId;
    }
    
    /**
     * Enviar notificación push a múltiples usuarios
     */
    public function enviarNotificacionMasiva($usuarioIds, $titulo, $mensaje, $tipo = 'info', $datos = []) {
        $notificacionesEnviadas = [];
        
        foreach ($usuarioIds as $usuarioId) {
            try {
                $notificacionId = $this->enviarNotificacion($usuarioId, $titulo, $mensaje, $tipo, $datos);
                $notificacionesEnviadas[] = $notificacionId;
            } catch (Exception $e) {
                error_log("Error enviando notificación a usuario $usuarioId: " . $e->getMessage());
            }
        }
        
        return $notificacionesEnviadas;
    }
    
    /**
     * Enviar push a un dispositivo específico
     */
    private function enviarPush($dispositivo, $titulo, $mensaje, $datos = []) {
        if ($dispositivo['plataforma'] === 'android') {
            return $this->enviarPushAndroid($dispositivo['token'], $titulo, $mensaje, $datos);
        } elseif ($dispositivo['plataforma'] === 'ios') {
            return $this->enviarPushIOS($dispositivo['token'], $titulo, $mensaje, $datos);
        }
        
        return false;
    }
    
    /**
     * Enviar push a Android usando FCM
     */
    private function enviarPushAndroid($token, $titulo, $mensaje, $datos = []) {
        $fcmServerKey = $this->obtenerFCMKey();
        
        if (!$fcmServerKey) {
            error_log("FCM Server Key no configurado");
            return false;
        }
        
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $titulo,
                'body' => $mensaje,
                'sound' => 'default',
                'badge' => 1
            ],
            'data' => $datos,
            'priority' => 'high'
        ];
        
        $headers = [
            'Authorization: key=' . $fcmServerKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return isset($result['success']) && $result['success'] > 0;
        }
        
        error_log("Error enviando push Android: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Enviar push a iOS usando APNs
     */
    private function enviarPushIOS($token, $titulo, $mensaje, $datos = []) {
        $apnsKey = $this->obtenerAPNsKey();
        $apnsKeyId = $this->obtenerAPNsKeyId();
        $apnsTeamId = $this->obtenerAPNsTeamId();
        $apnsBundleId = $this->obtenerAPNsBundleId();
        
        if (!$apnsKey || !$apnsKeyId || !$apnsTeamId || !$apnsBundleId) {
            error_log("APNs no configurado correctamente");
            return false;
        }
        
        // Generar JWT para autenticación APNs
        $jwt = $this->generarJWTAPNs($apnsKeyId, $apnsTeamId, $apnsKey);
        
        $url = 'https://api.push.apple.com/3/device/' . $token;
        
        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $titulo,
                    'body' => $mensaje
                ],
                'sound' => 'default',
                'badge' => 1
            ],
            'data' => $datos
        ];
        
        $headers = [
            'Authorization: Bearer ' . $jwt,
            'Content-Type: application/json',
            'apns-topic: ' . $apnsBundleId,
            'apns-priority: 10'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return true;
        }
        
        error_log("Error enviando push iOS: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Registrar token de dispositivo
     */
    public function registrarDispositivo($usuarioId, $token, $plataforma, $modelo = null) {
        // Verificar si el dispositivo ya existe
        $stmt = $this->db->query(
            "SELECT id FROM dispositivos_push WHERE token = ?",
            [$token]
        );
        
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Actualizar dispositivo existente
            $this->db->query(
                "UPDATE dispositivos_push 
                 SET usuario_id = ?, plataforma = ?, modelo = ?, activo = 1, fecha_actualizacion = NOW()
                 WHERE id = ?",
                [$usuarioId, $plataforma, $modelo, $existente['id']]
            );
            return $existente['id'];
        } else {
            // Crear nuevo dispositivo
            $this->db->query(
                "INSERT INTO dispositivos_push (usuario_id, token, plataforma, modelo, activo, fecha_registro)
                 VALUES (?, ?, ?, ?, 1, NOW())",
                [$usuarioId, $token, $plataforma, $modelo]
            );
            return $this->db->lastInsertId();
        }
    }
    
    /**
     * Desactivar dispositivo
     */
    public function desactivarDispositivo($usuarioId, $token) {
        $this->db->query(
            "UPDATE dispositivos_push SET activo = 0 WHERE usuario_id = ? AND token = ?",
            [$usuarioId, $token]
        );
    }
    
    /**
     * Obtener notificaciones de un usuario
     */
    public function obtenerNotificaciones($usuarioId, $filtros = []) {
        $where = ["usuario_id = ?"];
        $params = [$usuarioId];
        
        if (isset($filtros['leida'])) {
            $where[] = "leida = ?";
            $params[] = $filtros['leida'] ? 1 : 0;
        }
        
        if (isset($filtros['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Paginación
        $page = isset($filtros['page']) ? (int)$filtros['page'] : 1;
        $perPage = isset($filtros['per_page']) ? (int)$filtros['per_page'] : 20;
        $offset = ($page - 1) * $perPage;
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM notificaciones WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener notificaciones
        $stmt = $this->db->query(
            "SELECT * FROM notificaciones 
             WHERE $whereClause
             ORDER BY fecha_creacion DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $notificaciones = $stmt->fetchAll();
        
        // Decodificar JSON
        foreach ($notificaciones as &$notificacion) {
            if ($notificacion['datos']) {
                $notificacion['datos'] = json_decode($notificacion['datos'], true);
            }
        }
        
        return [
            'data' => $notificaciones,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($notificacionId, $usuarioId) {
        $this->db->query(
            "UPDATE notificaciones SET leida = 1, fecha_lectura = NOW() 
             WHERE id = ? AND usuario_id = ?",
            [$notificacionId, $usuarioId]
        );
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasComoLeidas($usuarioId) {
        $this->db->query(
            "UPDATE notificaciones SET leida = 1, fecha_lectura = NOW() 
             WHERE usuario_id = ? AND leida = 0",
            [$usuarioId]
        );
    }
    
    /**
     * Obtener cantidad de notificaciones no leídas
     */
    public function obtenerCantidadNoLeidas($usuarioId) {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM notificaciones 
             WHERE usuario_id = ? AND leida = 0",
            [$usuarioId]
        );
        
        return $stmt->fetch()['total'];
    }
    
    /**
     * Enviar alerta de préstamo vencido
     */
    public function alertarPrestamoVencido($prestamoId) {
        $stmt = $this->db->query(
            "SELECT p.id, p.numero_prestamo, c.nombre, c.apellido, u.id as usuario_id
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN usuarios u ON p.supervisor_aprobador_id = u.id
             WHERE p.id = ?",
            [$prestamoId]
        );
        
        $prestamo = $stmt->fetch();
        
        if ($prestamo && $prestamo['usuario_id']) {
            $titulo = "Préstamo Vencido";
            $mensaje = "El préstamo #{$prestamo['numero_prestamo']} de {$prestamo['nombre']} {$prestamo['apellido']} está vencido";
            
            $this->enviarNotificacion(
                $prestamo['usuario_id'],
                $titulo,
                $mensaje,
                'warning',
                ['prestamo_id' => $prestamoId, 'tipo' => 'prestamo_vencido']
            );
        }
    }
    
    /**
     * Enviar recordatorio de pago
     */
    public function recordarPago($prestamoId, $diasAntes = 3) {
        $stmt = $this->db->query(
            "SELECT p.id, p.numero_prestamo, c.nombre, c.apellido, u.id as usuario_id,
                    cp.fecha_vencimiento
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN usuarios u ON p.supervisor_aprobador_id = u.id
             LEFT JOIN cuotas_prestamos cp ON p.id = cp.prestamo_id
             WHERE p.id = ? AND cp.estado = 'pendiente'
             AND DATEDIFF(cp.fecha_vencimiento, CURDATE()) = ?
             LIMIT 1",
            [$prestamoId, $diasAntes]
        );
        
        $prestamo = $stmt->fetch();
        
        if ($prestamo && $prestamo['usuario_id']) {
            $titulo = "Recordatorio de Pago";
            $mensaje = "El préstamo #{$prestamo['numero_prestamo']} tiene un pago próximo el " . date('d/m/Y', strtotime($prestamo['fecha_vencimiento']));
            
            $this->enviarNotificacion(
                $prestamo['usuario_id'],
                $titulo,
                $mensaje,
                'info',
                ['prestamo_id' => $prestamoId, 'tipo' => 'recordatorio_pago']
            );
        }
    }
    
    /**
     * Obtener configuración FCM
     */
    private function obtenerFCMKey() {
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'fcm_server_key'"
        );
        $config = $stmt->fetch();
        return $config ? $config['valor'] : null;
    }
    
    /**
     * Obtener configuración APNs
     */
    private function obtenerAPNsKey() {
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'apns_key'"
        );
        $config = $stmt->fetch();
        return $config ? $config['valor'] : null;
    }
    
    private function obtenerAPNsKeyId() {
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'apns_key_id'"
        );
        $config = $stmt->fetch();
        return $config ? $config['valor'] : null;
    }
    
    private function obtenerAPNsTeamId() {
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'apns_team_id'"
        );
        $config = $stmt->fetch();
        return $config ? $config['valor'] : null;
    }
    
    private function obtenerAPNsBundleId() {
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'apns_bundle_id'"
        );
        $config = $stmt->fetch();
        return $config ? $config['valor'] : null;
    }
    
    /**
     * Generar JWT para APNs
     */
    private function generarJWTAPNs($keyId, $teamId, $privateKey) {
        $header = [
            'alg' => 'ES256',
            'kid' => $keyId
        ];
        
        $payload = [
            'iss' => $teamId,
            'iat' => time()
        ];
        
        // En producción, usar librería JWT real
        // Por ahora retornamos un token básico
        return base64_encode(json_encode($header)) . '.' . base64_encode(json_encode($payload));
    }
}

