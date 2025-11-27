<?php
/**
 * Controlador de Facturación Electrónica
 */

require_once __DIR__ . '/../services/FacturacionElectronicaService.php';
require_once __DIR__ . '/../services/FirmaDigitalService.php';

class FacturacionElectronicaController {
    private $db;
    private $facturacionService;
    private $firmaService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->facturacionService = new FacturacionElectronicaService();
        $this->firmaService = new FirmaDigitalService();
    }
    
    /**
     * Generar factura electrónica completa
     */
    public function generar($comprobanteId) {
        try {
            $resultado = $this->facturacionService->generarFacturaElectronica($comprobanteId);
            
            // Registrar log
            $this->registrarLog($comprobanteId, 'generar_xml', 'exitoso', 'Factura electrónica generada correctamente', $resultado);
            
            sendResponse([
                'success' => true,
                'message' => 'Factura electrónica generada correctamente',
                'data' => $resultado
            ]);
            
        } catch (Exception $e) {
            $this->registrarLog($comprobanteId, 'generar_xml', 'error', $e->getMessage());
            sendError('Error al generar factura electrónica: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Firmar XML de factura
     */
    public function firmar($comprobanteId) {
        try {
            // Obtener XML original
            $stmt = $this->db->query(
                "SELECT xml_original, id FROM comprobantes_fiscales WHERE id = ?",
                [$comprobanteId]
            );
            
            $comprobante = $stmt->fetch();
            
            if (!$comprobante || !$comprobante['xml_original']) {
                sendError('XML no generado. Debe generar la factura electrónica primero.', 400);
                return;
            }
            
            // Firmar XML
            $xmlFirmado = $this->firmaService->firmarXML($comprobante['xml_original'], $comprobante);
            
            // Guardar XML firmado
            $this->db->query(
                "UPDATE comprobantes_fiscales 
                 SET xml_firmado = ?,
                     estado_electronico = 'firmado',
                     fecha_validacion_firma = NOW()
                 WHERE id = ?",
                [$xmlFirmado, $comprobanteId]
            );
            
            // Validar firma
            $validacion = $this->firmaService->validarFirma($xmlFirmado);
            
            if ($validacion['valido']) {
                $this->db->query(
                    "UPDATE comprobantes_fiscales 
                     SET firma_valida = 1,
                         fecha_validacion_firma = NOW()
                     WHERE id = ?",
                    [$comprobanteId]
                );
            }
            
            // Registrar log
            $this->registrarLog($comprobanteId, 'firmar', $validacion['valido'] ? 'exitoso' : 'error', $validacion['mensaje']);
            
            sendResponse([
                'success' => true,
                'message' => 'Factura firmada correctamente',
                'firma_valida' => $validacion['valido'],
                'validacion' => $validacion
            ]);
            
        } catch (Exception $e) {
            $this->registrarLog($comprobanteId, 'firmar', 'error', $e->getMessage());
            sendError('Error al firmar factura: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Enviar factura electrónica a DGII
     */
    public function enviarDGII($comprobanteId) {
        try {
            $resultado = $this->facturacionService->enviarFacturaDGII($comprobanteId);
            
            // Registrar log
            $this->registrarLog($comprobanteId, 'enviar_dgii', 'exitoso', 'Factura enviada a DGII correctamente', $resultado);
            
            sendResponse([
                'success' => true,
                'message' => 'Factura enviada a DGII correctamente',
                'data' => $resultado
            ]);
            
        } catch (Exception $e) {
            $this->registrarLog($comprobanteId, 'enviar_dgii', 'error', $e->getMessage());
            sendError('Error al enviar factura a DGII: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Validar firma digital
     */
    public function validarFirma($comprobanteId) {
        try {
            $stmt = $this->db->query(
                "SELECT xml_firmado FROM comprobantes_fiscales WHERE id = ?",
                [$comprobanteId]
            );
            
            $comprobante = $stmt->fetch();
            
            if (!$comprobante || !$comprobante['xml_firmado']) {
                sendError('XML firmado no encontrado', 404);
                return;
            }
            
            $validacion = $this->firmaService->validarFirma($comprobante['xml_firmado']);
            
            // Actualizar estado
            $this->db->query(
                "UPDATE comprobantes_fiscales 
                 SET firma_valida = ?,
                     fecha_validacion_firma = NOW()
                 WHERE id = ?",
                [$validacion['valido'] ? 1 : 0, $comprobanteId]
            );
            
            // Registrar log
            $this->registrarLog($comprobanteId, 'validar_firma', $validacion['valido'] ? 'exitoso' : 'error', $validacion['mensaje']);
            
            sendResponse([
                'success' => true,
                'validacion' => $validacion
            ]);
            
        } catch (Exception $e) {
            $this->registrarLog($comprobanteId, 'validar_firma', 'error', $e->getMessage());
            sendError('Error al validar firma: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener información del certificado digital
     */
    public function infoCertificado() {
        try {
            $info = $this->firmaService->obtenerInfoCertificado();
            
            sendResponse([
                'success' => true,
                'data' => $info
            ]);
            
        } catch (Exception $e) {
            sendError('Error al obtener información del certificado: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Descargar XML de factura
     */
    public function descargarXML($comprobanteId, $tipo = 'firmado') {
        try {
            $campo = $tipo === 'original' ? 'xml_original' : 'xml_firmado';
            
            $stmt = $this->db->query(
                "SELECT $campo, numero_ncf FROM comprobantes_fiscales WHERE id = ?",
                [$comprobanteId]
            );
            
            $comprobante = $stmt->fetch();
            
            if (!$comprobante || !$comprobante[$campo]) {
                sendError('XML no encontrado', 404);
                return;
            }
            
            // Enviar como descarga
            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="factura_' . $comprobante['numero_ncf'] . '_' . $tipo . '.xml"');
            echo $comprobante[$campo];
            exit;
            
        } catch (Exception $e) {
            sendError('Error al descargar XML: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener QR Code de factura
     */
    public function obtenerQR($comprobanteId) {
        try {
            $stmt = $this->db->query(
                "SELECT qr_code, numero_ncf FROM comprobantes_fiscales WHERE id = ?",
                [$comprobanteId]
            );
            
            $comprobante = $stmt->fetch();
            
            if (!$comprobante) {
                sendError('Comprobante no encontrado', 404);
                return;
            }
            
            if (!$comprobante['qr_code']) {
                // Generar QR si no existe
                $comprobanteData = $this->db->query(
                    "SELECT * FROM comprobantes_fiscales WHERE id = ?",
                    [$comprobanteId]
                )->fetch();
                
                $qrData = $this->facturacionService->generarQRCode($comprobanteData, null);
                
                $this->db->query(
                    "UPDATE comprobantes_fiscales SET qr_code = ? WHERE id = ?",
                    [json_encode($qrData), $comprobanteId]
                );
                
                sendResponse([
                    'success' => true,
                    'data' => $qrData
                ]);
            } else {
                sendResponse([
                    'success' => true,
                    'data' => json_decode($comprobante['qr_code'], true)
                ]);
            }
            
        } catch (Exception $e) {
            sendError('Error al obtener QR: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Registrar log de facturación electrónica
     */
    private function registrarLog($comprobanteId, $accion, $estado, $mensaje, $datosAdicionales = null) {
        try {
            $this->db->query(
                "INSERT INTO logs_facturacion_electronica 
                 (comprobante_id, accion, estado, mensaje, datos_adicionales)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $comprobanteId,
                    $accion,
                    $estado,
                    $mensaje,
                    $datosAdicionales ? json_encode($datosAdicionales) : null
                ]
            );
        } catch (Exception $e) {
            error_log("Error registrando log de facturación electrónica: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener logs de facturación electrónica
     */
    public function obtenerLogs($comprobanteId) {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM logs_facturacion_electronica 
                 WHERE comprobante_id = ?
                 ORDER BY fecha DESC",
                [$comprobanteId]
            );
            
            sendResponse($stmt->fetchAll());
            
        } catch (Exception $e) {
            sendError('Error al obtener logs: ' . $e->getMessage(), 500);
        }
    }
}

