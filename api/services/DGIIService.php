<?php
/**
 * Servicio de Integración con DGII
 */

class DGIIService {
    private $db;
    private $apiUrl;
    private $apiKey;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Obtener configuración de DGII
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'api_dgii_key'"
        );
        $config = $stmt->fetch();
        $this->apiKey = $config ? $config['valor'] : DGII_API_KEY;
        $this->apiUrl = DGII_API_URL;
    }
    
    /**
     * Validar NCF (Número de Comprobante Fiscal)
     */
    public function validarNCF($ncf, $rnc) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/api/ncf/validar',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'ncf' => $ncf,
                'rnc' => $rnc
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión con DGII: $error");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception("Error validando NCF: " . ($errorData['message'] ?? "HTTP $httpCode"));
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Enviar factura electrónica a DGII (método antiguo - mantener compatibilidad)
     */
    public function enviarFactura($facturaData) {
        return $this->enviarFacturaElectronica($facturaData);
    }
    
    /**
     * Enviar factura electrónica a DGII (XML firmado)
     */
    public function enviarFacturaElectronica($facturaData) {
        $ch = curl_init();
        
        // Si viene XML, enviarlo directamente
        if (isset($facturaData['xml'])) {
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl . '/api/facturas-electronicas/enviar',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $facturaData['xml'],
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/xml'
                ],
                CURLOPT_TIMEOUT => 120
            ]);
        } else {
            // Método JSON (compatibilidad)
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl . '/api/facturas/enviar',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($facturaData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 60
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión con DGII: $error");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception("Error enviando factura: " . ($errorData['message'] ?? "HTTP $httpCode"));
        }
        
        $result = json_decode($response, true);
        
        // Guardar respuesta en base de datos
        $this->db->query(
            "UPDATE comprobantes_fiscales 
             SET dgii_enviado = 1, 
                 dgii_fecha_envio = NOW(),
                 dgii_respuesta = ?,
                 dgii_trackid = ?
             WHERE id = ?",
            [
                json_encode($result),
                $result['trackid'] ?? null,
                $facturaData['comprobante_id']
            ]
        );
        
        return $result;
    }
    
    /**
     * Consultar RNC
     */
    public function consultarRNC($rnc) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/api/rnc/' . $rnc,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión con DGII: $error");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception("Error consultando RNC: " . ($errorData['message'] ?? "HTTP $httpCode"));
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Generar y enviar reporte 606 (Ventas al Contado)
     */
    public function generarReporte606($periodo) {
        // Obtener datos del período
        $stmt = $this->db->query(
            "SELECT * FROM comprobantes_fiscales 
             WHERE DATE_FORMAT(fecha_emision, '%Y-%m') = ?
             AND tipo_comprobante_id IN (SELECT id FROM tipos_comprobantes WHERE codigo IN ('01', '02'))
             AND dgii_enviado = 0",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $xml = $this->generarXML606($comprobantes, $periodo);
        
        // Enviar a DGII
        return $this->enviarReporte('606', $xml, $periodo);
    }
    
    /**
     * Generar y enviar reporte 607 (Retenciones ISR)
     */
    public function generarReporte607($periodo) {
        $stmt = $this->db->query(
            "SELECT * FROM comprobantes_fiscales 
             WHERE DATE_FORMAT(fecha_emision, '%Y-%m') = ?
             AND tiene_retencion = 1
             AND dgii_enviado = 0",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $xml = $this->generarXML607($comprobantes, $periodo);
        
        return $this->enviarReporte('607', $xml, $periodo);
    }
    
    /**
     * Generar y enviar reporte 608 (Ventas y Servicios)
     */
    public function generarReporte608($periodo) {
        $stmt = $this->db->query(
            "SELECT * FROM comprobantes_fiscales 
             WHERE DATE_FORMAT(fecha_emision, '%Y-%m') = ?
             AND tipo_comprobante_id IN (SELECT id FROM tipos_comprobantes WHERE codigo IN ('01', '02', '03'))
             AND dgii_enviado = 0",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $xml = $this->generarXML608($comprobantes, $periodo);
        
        return $this->enviarReporte('608', $xml, $periodo);
    }
    
    /**
     * Generar y enviar reporte 609 (Compras)
     */
    public function generarReporte609($periodo) {
        $stmt = $this->db->query(
            "SELECT * FROM comprobantes_fiscales 
             WHERE DATE_FORMAT(fecha_emision, '%Y-%m') = ?
             AND tipo_comprobante_id IN (SELECT id FROM tipos_comprobantes WHERE codigo IN ('43', '44'))
             AND dgii_enviado = 0",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $xml = $this->generarXML609($comprobantes, $periodo);
        
        return $this->enviarReporte('609', $xml, $periodo);
    }
    
    /**
     * Enviar reporte a DGII
     */
    private function enviarReporte($tipo, $xml, $periodo) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/api/reportes/enviar',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'tipo' => $tipo,
                'periodo' => $periodo,
                'xml' => base64_encode($xml)
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 120
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión con DGII: $error");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            throw new Exception("Error enviando reporte: " . ($errorData['message'] ?? "HTTP $httpCode"));
        }
        
        $result = json_decode($response, true);
        
        // Guardar en base de datos
        $this->db->query(
            "INSERT INTO reportes_dgii (tipo_reporte, periodo, estado, archivo_xml, dgii_respuesta)
             VALUES (?, ?, 'enviado', ?, ?)
             ON DUPLICATE KEY UPDATE 
                 estado = 'enviado',
                 dgii_respuesta = ?,
                 fecha_envio = NOW()",
            [$tipo, $periodo, $xml, json_encode($result), json_encode($result)]
        );
        
        return $result;
    }
    
    /**
     * Generar XML para reporte 606
     */
    private function generarXML606($comprobantes, $periodo) {
        // Implementación básica - en producción usar librería XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Reporte606 periodo="' . $periodo . '">' . "\n";
        
        foreach ($comprobantes as $comp) {
            $xml .= '  <Comprobante>' . "\n";
            $xml .= '    <NCF>' . htmlspecialchars($comp['numero_ncf']) . '</NCF>' . "\n";
            $xml .= '    <Fecha>' . $comp['fecha_emision'] . '</Fecha>' . "\n";
            $xml .= '    <Monto>' . $comp['monto_total'] . '</Monto>' . "\n";
            $xml .= '  </Comprobante>' . "\n";
        }
        
        $xml .= '</Reporte606>';
        
        return $xml;
    }
    
    /**
     * Generar XML para reporte 607
     */
    private function generarXML607($comprobantes, $periodo) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Reporte607 periodo="' . $periodo . '">' . "\n";
        
        foreach ($comprobantes as $comp) {
            $xml .= '  <Retencion>' . "\n";
            $xml .= '    <NCF>' . htmlspecialchars($comp['numero_ncf']) . '</NCF>' . "\n";
            $xml .= '    <MontoRetenido>' . ($comp['monto_retencion'] ?? 0) . '</MontoRetenido>' . "\n";
            $xml .= '  </Retencion>' . "\n";
        }
        
        $xml .= '</Reporte607>';
        
        return $xml;
    }
    
    /**
     * Generar XML para reporte 608
     */
    private function generarXML608($comprobantes, $periodo) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Reporte608 periodo="' . $periodo . '">' . "\n";
        
        foreach ($comprobantes as $comp) {
            $xml .= '  <Venta>' . "\n";
            $xml .= '    <NCF>' . htmlspecialchars($comp['numero_ncf']) . '</NCF>' . "\n";
            $xml .= '    <Fecha>' . $comp['fecha_emision'] . '</Fecha>' . "\n";
            $xml .= '    <Monto>' . $comp['monto_total'] . '</Monto>' . "\n";
            $xml .= '  </Venta>' . "\n";
        }
        
        $xml .= '</Reporte608>';
        
        return $xml;
    }
    
    /**
     * Generar XML para reporte 609
     */
    private function generarXML609($comprobantes, $periodo) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Reporte609 periodo="' . $periodo . '">' . "\n";
        
        foreach ($comprobantes as $comp) {
            $xml .= '  <Compra>' . "\n";
            $xml .= '    <NCF>' . htmlspecialchars($comp['numero_ncf']) . '</NCF>' . "\n";
            $xml .= '    <Fecha>' . $comp['fecha_emision'] . '</Fecha>' . "\n";
            $xml .= '    <Monto>' . $comp['monto_total'] . '</Monto>' . "\n";
            $xml .= '  </Compra>' . "\n";
        }
        
        $xml .= '</Reporte609>';
        
        return $xml;
    }
}

