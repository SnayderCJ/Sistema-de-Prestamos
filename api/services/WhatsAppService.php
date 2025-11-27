<?php
/**
 * Servicio de WhatsApp Business API
 * Integración con WhatsApp Business API para CRM y notificaciones
 */

class WhatsAppService {
    private $db;
    private $apiUrl;
    private $apiToken;
    private $phoneNumberId;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Obtener configuración de WhatsApp
        $stmt = $this->db->query(
            "SELECT clave, valor FROM configuracion_sistema 
             WHERE clave IN ('whatsapp_api_url', 'whatsapp_api_token', 'whatsapp_phone_number_id')"
        );
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $this->apiUrl = $config['whatsapp_api_url'] ?? 'https://graph.facebook.com/v18.0';
        $this->apiToken = $config['whatsapp_api_token'] ?? '';
        $this->phoneNumberId = $config['whatsapp_phone_number_id'] ?? '';
    }
    
    /**
     * Enviar mensaje de texto
     */
    public function enviarMensaje($numero, $mensaje, $template = null) {
        if (empty($this->apiToken) || empty($this->phoneNumberId)) {
            throw new Exception('WhatsApp no está configurado. Configure las credenciales en Configuración del Sistema.');
        }
        
        // Formatear número (debe incluir código de país sin +)
        $numero = $this->formatearNumero($numero);
        
        $url = $this->apiUrl . '/' . $this->phoneNumberId . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $numero,
            'type' => 'text',
            'text' => [
                'body' => $mensaje
            ]
        ];
        
        // Si hay template, usar template message
        if ($template) {
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $numero,
                'type' => 'template',
                'template' => $template
            ];
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión con WhatsApp: $error");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception("Error enviando mensaje WhatsApp: " . ($errorData['error']['message'] ?? "HTTP $httpCode"));
        }
        
        $result = json_decode($response, true);
        
        // Guardar en historial
        $this->guardarHistorial($numero, $mensaje, 'enviado', $result);
        
        return $result;
    }
    
    /**
     * Enviar notificación de pago
     */
    public function enviarNotificacionPago($pagoId) {
        $stmt = $this->db->query(
            "SELECT p.*, pr.numero_prestamo, c.nombre as cliente_nombre, c.apellido as cliente_apellido, 
                    c.telefono as cliente_telefono, c.email as cliente_email
             FROM pagos p
             INNER JOIN prestamos pr ON p.prestamo_id = pr.id
             INNER JOIN clientes c ON pr.cliente_id = c.id
             WHERE p.id = ?",
            [$pagoId]
        );
        $pago = $stmt->fetch();
        
        if (!$pago) {
            throw new Exception('Pago no encontrado');
        }
        
        $mensaje = $this->generarMensajePago($pago);
        
        // Enviar por WhatsApp si tiene teléfono
        if (!empty($pago['cliente_telefono'])) {
            try {
                $this->enviarMensaje($pago['cliente_telefono'], $mensaje);
            } catch (Exception $e) {
                error_log('Error enviando WhatsApp: ' . $e->getMessage());
            }
        }
        
        // Enviar por Email
        if (!empty($pago['cliente_email'])) {
            try {
                $emailService = new EmailService();
                $emailService->enviarEmail(
                    $pago['cliente_email'],
                    'Confirmación de Pago - Préstamo ' . $pago['numero_prestamo'],
                    $mensaje
                );
            } catch (Exception $e) {
                error_log('Error enviando Email: ' . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * Generar mensaje de notificación de pago
     */
    private function generarMensajePago($pago) {
        $fecha = date('d/m/Y', strtotime($pago['fecha_pago']));
        $monto = number_format($pago['monto'] ?? 0, 2);
        
        $mensaje = "✅ *PAGO REGISTRADO EXITOSAMENTE*\n\n";
        $mensaje .= "Estimado/a {$pago['cliente_nombre']} {$pago['cliente_apellido']}\n\n";
        $mensaje .= "Le confirmamos el registro de su pago:\n\n";
        $mensaje .= "📋 *Detalles del Pago:*\n";
        $mensaje .= "• Préstamo: {$pago['numero_prestamo']}\n";
        $mensaje .= "• Recibo: {$pago['numero_recibo']}\n";
        $mensaje .= "• Fecha: {$fecha}\n";
        $mensaje .= "• Monto: RD$ {$monto}\n";
        $mensaje .= "• Capital: RD$ " . number_format($pago['capital'] ?? 0, 2) . "\n";
        $mensaje .= "• Interés: RD$ " . number_format($pago['interes'] ?? 0, 2) . "\n";
        
        if (($pago['mora'] ?? 0) > 0) {
            $mensaje .= "• Mora: RD$ " . number_format($pago['mora'], 2) . "\n";
        }
        
        $mensaje .= "\nGracias por su pago puntual.\n";
        $mensaje .= "Para consultas, contáctenos.\n\n";
        $mensaje .= "_Este es un mensaje automático, por favor no responder._";
        
        return $mensaje;
    }
    
    /**
     * Enviar recordatorio de pago
     */
    public function enviarRecordatorioPago($prestamoId, $diasVencido = 0) {
        $stmt = $this->db->query(
            "SELECT p.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, 
                    c.telefono as cliente_telefono, c.email as cliente_email
             FROM prestamos p
             INNER JOIN clientes c ON p.cliente_id = c.id
             WHERE p.id = ?",
            [$prestamoId]
        );
        $prestamo = $stmt->fetch();
        
        if (!$prestamo) {
            throw new Exception('Préstamo no encontrado');
        }
        
        $mensaje = $this->generarMensajeRecordatorio($prestamo, $diasVencido);
        
        // Enviar por WhatsApp
        if (!empty($prestamo['cliente_telefono'])) {
            try {
                $this->enviarMensaje($prestamo['cliente_telefono'], $mensaje);
            } catch (Exception $e) {
                error_log('Error enviando WhatsApp: ' . $e->getMessage());
            }
        }
        
        // Enviar por Email
        if (!empty($prestamo['cliente_email'])) {
            try {
                $emailService = new EmailService();
                $emailService->enviarEmail(
                    $prestamo['cliente_email'],
                    'Recordatorio de Pago - Préstamo ' . $prestamo['numero_prestamo'],
                    $mensaje
                );
            } catch (Exception $e) {
                error_log('Error enviando Email: ' . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * Generar mensaje de recordatorio
     */
    private function generarMensajeRecordatorio($prestamo, $diasVencido) {
        $cuota = number_format($prestamo['cuota_mensual'] ?? 0, 2);
        
        if ($diasVencido > 0) {
            $mensaje = "⚠️ *RECORDATORIO DE PAGO VENCIDO*\n\n";
            $mensaje .= "Estimado/a {$prestamo['cliente_nombre']} {$prestamo['cliente_apellido']}\n\n";
            $mensaje .= "Su préstamo #{$prestamo['numero_prestamo']} tiene una cuota vencida.\n\n";
            $mensaje .= "📋 *Detalles:*\n";
            $mensaje .= "• Días vencido: {$diasVencido}\n";
            $mensaje .= "• Cuota pendiente: RD$ {$cuota}\n\n";
            $mensaje .= "Por favor, realice su pago lo antes posible para evitar cargos adicionales.\n\n";
        } else {
            $mensaje = "📅 *RECORDATORIO DE PAGO*\n\n";
            $mensaje .= "Estimado/a {$prestamo['cliente_nombre']} {$prestamo['cliente_apellido']}\n\n";
            $mensaje .= "Le recordamos que tiene una cuota pendiente del préstamo #{$prestamo['numero_prestamo']}.\n\n";
            $mensaje .= "📋 *Detalles:*\n";
            $mensaje .= "• Cuota: RD$ {$cuota}\n\n";
            $mensaje .= "Por favor, realice su pago a tiempo.\n\n";
        }
        
        $mensaje .= "Gracias por su atención.\n\n";
        $mensaje .= "_Este es un mensaje automático, por favor no responder._";
        
        return $mensaje;
    }
    
    /**
     * Guardar mensaje en historial
     */
    private function guardarHistorial($numero, $mensaje, $estado, $respuesta = null) {
        $this->db->query(
            "INSERT INTO whatsapp_historial (numero, mensaje, estado, respuesta, fecha_envio)
             VALUES (?, ?, ?, ?, NOW())",
            [$numero, $mensaje, $estado, json_encode($respuesta)]
        );
    }
    
    /**
     * Obtener historial de conversaciones
     */
    public function obtenerHistorial($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        if (isset($filters['numero'])) {
            $where[] = 'numero = ?';
            $params[] = $filters['numero'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = 'DATE(fecha_envio) >= ?';
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = 'DATE(fecha_envio) <= ?';
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT * FROM whatsapp_historial 
             WHERE $whereClause 
             ORDER BY fecha_envio DESC 
             LIMIT 100",
            $params
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener conversaciones agrupadas por número
     */
    public function obtenerConversaciones($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        if (isset($filters['busqueda'])) {
            $where[] = '(numero LIKE ? OR mensaje LIKE ?)';
            $busqueda = '%' . $filters['busqueda'] . '%';
            $params[] = $busqueda;
            $params[] = $busqueda;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT numero, 
                    COUNT(*) as total_mensajes,
                    MAX(fecha_envio) as ultima_conversacion,
                    GROUP_CONCAT(mensaje ORDER BY fecha_envio DESC SEPARATOR ' | ') as mensajes
             FROM whatsapp_historial
             WHERE $whereClause
             GROUP BY numero
             ORDER BY ultima_conversacion DESC
             LIMIT 50",
            $params
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Formatear número de teléfono
     */
    private function formatearNumero($numero) {
        // Remover caracteres no numéricos
        $numero = preg_replace('/[^0-9]/', '', $numero);
        
        // Si no tiene código de país, agregar 1 (República Dominicana)
        if (strlen($numero) == 10) {
            $numero = '1' . $numero;
        }
        
        return $numero;
    }
    
    /**
     * Webhook para recibir mensajes de WhatsApp
     */
    public function procesarWebhook($data) {
        // Verificar que es un mensaje válido
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return false;
        }
        
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from'];
        $text = $message['text']['body'] ?? '';
        $timestamp = $message['timestamp'] ?? time();
        
        // Guardar mensaje recibido
        $this->db->query(
            "INSERT INTO whatsapp_historial (numero, mensaje, estado, fecha_envio, tipo)
             VALUES (?, ?, 'recibido', FROM_UNIXTIME(?), 'recibido')",
            [$from, $text, $timestamp]
        );
        
        // Procesar respuesta automática si es necesario
        $this->procesarRespuestaAutomatica($from, $text);
        
        return true;
    }
    
    /**
     * Procesar respuesta automática
     */
    private function procesarRespuestaAutomatica($numero, $mensaje) {
        $mensaje = strtolower(trim($mensaje));
        
        // Respuestas automáticas básicas
        if (strpos($mensaje, 'saldo') !== false || strpos($mensaje, 'balance') !== false) {
            $this->responderConsultaSaldo($numero);
        } elseif (strpos($mensaje, 'pago') !== false || strpos($mensaje, 'cuota') !== false) {
            $this->responderConsultaPago($numero);
        } elseif (strpos($mensaje, 'hola') !== false || strpos($mensaje, 'buenos') !== false) {
            $respuesta = "¡Hola! 👋\n\nSoy el asistente virtual de ImaxPrestamos.\n\n";
            $respuesta .= "Puedo ayudarte con:\n";
            $respuesta .= "• Consultar tu saldo\n";
            $respuesta .= "• Información sobre pagos\n";
            $respuesta .= "• Estado de tu préstamo\n\n";
            $respuesta .= "Escribe 'saldo', 'pago' o 'estado' para más información.";
            $this->enviarMensaje($numero, $respuesta);
        }
    }
    
    /**
     * Responder consulta de saldo
     */
    private function responderConsultaSaldo($numero) {
        // Buscar cliente por teléfono
        $stmt = $this->db->query(
            "SELECT c.*, p.numero_prestamo, p.monto_aprobado, 
                    (SELECT SUM(monto) FROM pagos WHERE prestamo_id = p.id) as monto_pagado
             FROM clientes c
             INNER JOIN prestamos p ON c.id = p.cliente_id
             WHERE c.telefono LIKE ? AND p.estado = 'activo'
             ORDER BY p.fecha_creacion DESC
             LIMIT 1",
            ['%' . substr($numero, -10) . '%']
        );
        $cliente = $stmt->fetch();
        
        if ($cliente) {
            $saldo = ($cliente['monto_aprobado'] ?? 0) - ($cliente['monto_pagado'] ?? 0);
            $mensaje = "📊 *CONSULTA DE SALDO*\n\n";
            $mensaje .= "Préstamo: {$cliente['numero_prestamo']}\n";
            $mensaje .= "Saldo pendiente: RD$ " . number_format($saldo, 2) . "\n";
            $mensaje .= "Monto aprobado: RD$ " . number_format($cliente['monto_aprobado'] ?? 0, 2) . "\n";
            $mensaje .= "Monto pagado: RD$ " . number_format($cliente['monto_pagado'] ?? 0, 2);
        } else {
            $mensaje = "No se encontró información de préstamos activos asociados a este número.";
        }
        
        $this->enviarMensaje($numero, $mensaje);
    }
    
    /**
     * Responder consulta de pago
     */
    private function responderConsultaPago($numero) {
        $mensaje = "💳 *INFORMACIÓN DE PAGOS*\n\n";
        $mensaje .= "Para realizar un pago, puedes:\n\n";
        $mensaje .= "1. Acudir a nuestras oficinas\n";
        $mensaje .= "2. Realizar transferencia bancaria\n";
        $mensaje .= "3. Contactar a tu cobrador asignado\n\n";
        $mensaje .= "Para más información, contáctanos.";
        
        $this->enviarMensaje($numero, $mensaje);
    }
}

