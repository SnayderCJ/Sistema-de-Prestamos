<?php
/**
 * Servicio de Facturación Electrónica (eCF)
 * Genera facturas electrónicas según estándares DGII
 */

require_once __DIR__ . '/FirmaDigitalService.php';

class FacturacionElectronicaService {
    private $db;
    private $firmaDigitalService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->firmaDigitalService = new FirmaDigitalService();
    }
    
    /**
     * Generar factura electrónica completa (XML + Firma)
     */
    public function generarFacturaElectronica($comprobanteId) {
        // Obtener datos del comprobante
        $comprobante = $this->obtenerComprobante($comprobanteId);
        
        if (!$comprobante) {
            throw new Exception("Comprobante no encontrado");
        }
        
        // Generar XML según estándar DGII
        $xml = $this->generarXMLFactura($comprobante);
        
        // Firmar XML
        $xmlFirmado = $this->firmaDigitalService->firmarXML($xml, $comprobante);
        
        // Guardar XML en base de datos
        $this->guardarXML($comprobanteId, $xml, $xmlFirmado);
        
        // Generar QR Code
        $qrCode = $this->generarQRCode($comprobante, $xmlFirmado);
        
        return [
            'xml' => $xml,
            'xml_firmado' => $xmlFirmado,
            'qr_code' => $qrCode,
            'comprobante_id' => $comprobanteId
        ];
    }
    
    /**
     * Generar XML de factura según estándar DGII
     */
    private function generarXMLFactura($comprobante) {
        // Obtener configuración de la empresa
        $empresa = $this->obtenerConfiguracionEmpresa();
        
        // Obtener cliente
        $cliente = $this->obtenerCliente($comprobante['cliente_id']);
        
        // Obtener items del comprobante
        $items = $this->obtenerItemsComprobante($comprobante['id']);
        
        // Obtener impuestos
        $impuestos = $this->obtenerImpuestosComprobante($comprobante['id']);
        
        // Crear XML según estándar DGII eCF
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root element: eCF
        $ecf = $xml->createElement('eCF');
        $ecf->setAttribute('version', '1.0');
        $ecf->setAttribute('xmlns', 'http://www.dgii.gov.do/ecf');
        $xml->appendChild($ecf);
        
        // Encabezado
        $encabezado = $xml->createElement('Encabezado');
        $ecf->appendChild($encabezado);
        
        // Información del Emisor
        $emisor = $xml->createElement('Emisor');
        $encabezado->appendChild($emisor);
        
        $emisor->appendChild($xml->createElement('RNCEmisor', $empresa['rnc']));
        $emisor->appendChild($xml->createElement('RazonSocialEmisor', htmlspecialchars($empresa['razon_social'])));
        $emisor->appendChild($xml->createElement('NombreComercial', htmlspecialchars($empresa['nombre_comercial'] ?? $empresa['razon_social'])));
        $emisor->appendChild($xml->createElement('DireccionEmisor', htmlspecialchars($empresa['direccion'])));
        $emisor->appendChild($xml->createElement('TelefonoEmisor', $empresa['telefono'] ?? ''));
        $emisor->appendChild($xml->createElement('EmailEmisor', $empresa['email'] ?? ''));
        
        // Información del Receptor
        $receptor = $xml->createElement('Receptor');
        $encabezado->appendChild($receptor);
        
        $receptor->appendChild($xml->createElement('RNCReceptor', $cliente['rnc'] ?? ''));
        $receptor->appendChild($xml->createElement('RazonSocialReceptor', htmlspecialchars($cliente['razon_social'] ?? $cliente['nombre'] . ' ' . $cliente['apellido'])));
        $receptor->appendChild($xml->createElement('DireccionReceptor', htmlspecialchars($cliente['direccion'] ?? '')));
        $receptor->appendChild($xml->createElement('TelefonoReceptor', $cliente['telefono'] ?? ''));
        $receptor->appendChild($xml->createElement('EmailReceptor', $cliente['email'] ?? ''));
        
        // Información del Comprobante
        $infoComprobante = $xml->createElement('InformacionComprobante');
        $encabezado->appendChild($infoComprobante);
        
        $infoComprobante->appendChild($xml->createElement('TipoComprobante', $comprobante['tipo_comprobante_codigo']));
        $infoComprobante->appendChild($xml->createElement('NCF', $comprobante['numero_ncf']));
        $infoComprobante->appendChild($xml->createElement('FechaEmision', $comprobante['fecha_emision']));
        $infoComprobante->appendChild($xml->createElement('FechaVencimiento', $comprobante['fecha_vencimiento'] ?? $comprobante['fecha_emision']));
        $infoComprobante->appendChild($xml->createElement('MontoGravadoTotal', number_format($comprobante['monto_subtotal'], 2, '.', '')));
        $infoComprobante->appendChild($xml->createElement('MontoImpuesto', number_format($comprobante['monto_impuestos'], 2, '.', '')));
        $infoComprobante->appendChild($xml->createElement('MontoTotal', number_format($comprobante['monto_total'], 2, '.', '')));
        
        // Detalle de Items
        $detalle = $xml->createElement('Detalle');
        $ecf->appendChild($detalle);
        
        foreach ($items as $item) {
            $itemElement = $xml->createElement('Item');
            $detalle->appendChild($itemElement);
            
            $itemElement->appendChild($xml->createElement('Descripcion', htmlspecialchars($item['descripcion'])));
            $itemElement->appendChild($xml->createElement('Cantidad', number_format($item['cantidad'], 2, '.', '')));
            $itemElement->appendChild($xml->createElement('PrecioUnitario', number_format($item['precio_unitario'], 2, '.', '')));
            $itemElement->appendChild($xml->createElement('MontoItem', number_format($item['monto'], 2, '.', '')));
            $itemElement->appendChild($xml->createElement('IndicadorFacturacion', $item['indicador_facturacion'] ?? '1'));
        }
        
        // Impuestos
        if (!empty($impuestos)) {
            $impuestosElement = $xml->createElement('Impuestos');
            $ecf->appendChild($impuestosElement);
            
            foreach ($impuestos as $imp) {
                $impuestoElement = $xml->createElement('Impuesto');
                $impuestosElement->appendChild($impuestoElement);
                
                $impuestoElement->appendChild($xml->createElement('TipoImpuesto', $imp['codigo']));
                $impuestoElement->appendChild($xml->createElement('BaseImponible', number_format($imp['base_imponible'], 2, '.', '')));
                $impuestoElement->appendChild($xml->createElement('MontoImpuesto', number_format($imp['monto_impuesto'], 2, '.', '')));
                $impuestoElement->appendChild($xml->createElement('TasaImpuesto', number_format($imp['tasa'], 2, '.', '')));
            }
        }
        
        // Información Adicional
        $infoAdicional = $xml->createElement('InformacionAdicional');
        $ecf->appendChild($infoAdicional);
        
        if (!empty($comprobante['observaciones'])) {
            $infoAdicional->appendChild($xml->createElement('Observaciones', htmlspecialchars($comprobante['observaciones'])));
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Generar QR Code para la factura
     */
    private function generarQRCode($comprobante, $xmlFirmado) {
        // Obtener configuración de la empresa
        $empresa = $this->obtenerConfiguracionEmpresa();
        
        // Datos para el QR según estándar DGII
        $qrData = [
            'RNC' => $empresa['rnc'],
            'NCF' => $comprobante['numero_ncf'],
            'Fecha' => $comprobante['fecha_emision'],
            'Monto' => number_format($comprobante['monto_total'], 2, '.', ''),
            'ITBIS' => number_format($comprobante['monto_impuestos'], 2, '.', '')
        ];
        
        // Generar URL del QR (formato DGII)
        $qrUrl = "https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx?RNC=" . 
                 $empresa['rnc'] . "&NCF=" . $comprobante['numero_ncf'];
        
        // Usar librería de QR Code (requiere instalar phpqrcode)
        // Por ahora retornamos la URL, en producción generar imagen QR
        return [
            'url' => $qrUrl,
            'data' => $qrData
        ];
    }
    
    /**
     * Guardar XML en base de datos
     */
    private function guardarXML($comprobanteId, $xml, $xmlFirmado) {
        // Guardar XML original
        $this->db->query(
            "UPDATE comprobantes_fiscales 
             SET xml_original = ?, 
                 xml_firmado = ?,
                 fecha_generacion_xml = NOW(),
                 estado_electronico = 'generado'
             WHERE id = ?",
            [$xml, $xmlFirmado, $comprobanteId]
        );
    }
    
    /**
     * Obtener comprobante con toda su información
     */
    private function obtenerComprobante($comprobanteId) {
        $stmt = $this->db->query(
            "SELECT c.*, t.codigo as tipo_comprobante_codigo, t.nombre as tipo_comprobante_nombre
             FROM comprobantes_fiscales c
             LEFT JOIN tipos_comprobantes t ON c.tipo_comprobante_id = t.id
             WHERE c.id = ?",
            [$comprobanteId]
        );
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener configuración de la empresa
     */
    private function obtenerConfiguracionEmpresa() {
        $stmt = $this->db->query(
            "SELECT clave, valor FROM configuracion_sistema 
             WHERE clave IN ('rnc_empresa', 'razon_social', 'nombre_comercial', 'direccion_empresa', 'telefono_empresa', 'email_empresa')"
        );
        
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        return [
            'rnc' => $config['rnc_empresa'] ?? '',
            'razon_social' => $config['razon_social'] ?? '',
            'nombre_comercial' => $config['nombre_comercial'] ?? $config['razon_social'] ?? '',
            'direccion' => $config['direccion_empresa'] ?? '',
            'telefono' => $config['telefono_empresa'] ?? '',
            'email' => $config['email_empresa'] ?? ''
        ];
    }
    
    /**
     * Obtener cliente
     */
    private function obtenerCliente($clienteId) {
        $stmt = $this->db->query(
            "SELECT * FROM clientes WHERE id = ?",
            [$clienteId]
        );
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener items del comprobante
     */
    private function obtenerItemsComprobante($comprobanteId) {
        // Si el comprobante está relacionado con una venta, obtener items de la venta
        $stmt = $this->db->query(
            "SELECT v.id as venta_id FROM comprobantes_fiscales c
             LEFT JOIN ventas v ON c.id = v.comprobante_id
             WHERE c.id = ?",
            [$comprobanteId]
        );
        
        $venta = $stmt->fetch();
        
        if ($venta && $venta['venta_id']) {
            // Obtener items de la venta
            $stmt = $this->db->query(
                "SELECT vi.*, a.nombre as descripcion, a.codigo
                 FROM ventas_items vi
                 LEFT JOIN articulos a ON vi.articulo_id = a.id
                 WHERE vi.venta_id = ?",
                [$venta['venta_id']]
            );
            
            $items = [];
            while ($row = $stmt->fetch()) {
                $items[] = [
                    'descripcion' => $row['descripcion'] ?? 'Item sin descripción',
                    'cantidad' => $row['cantidad'],
                    'precio_unitario' => $row['precio_unitario'],
                    'monto' => $row['subtotal'],
                    'indicador_facturacion' => '1'
                ];
            }
            
            return $items;
        }
        
        // Si no hay venta, crear un item genérico
        $comprobante = $this->obtenerComprobante($comprobanteId);
        return [
            [
                'descripcion' => $comprobante['observaciones'] ?? 'Servicio',
                'cantidad' => 1,
                'precio_unitario' => $comprobante['monto_subtotal'],
                'monto' => $comprobante['monto_subtotal'],
                'indicador_facturacion' => '1'
            ]
        ];
    }
    
    /**
     * Obtener impuestos del comprobante
     */
    private function obtenerImpuestosComprobante($comprobanteId) {
        $stmt = $this->db->query(
            "SELECT ic.*, imp.codigo, imp.nombre, imp.valor as tasa
             FROM impuestos_comprobantes ic
             LEFT JOIN impuestos imp ON ic.impuesto_id = imp.id
             WHERE ic.comprobante_id = ?",
            [$comprobanteId]
        );
        
        $impuestos = [];
        while ($row = $stmt->fetch()) {
            $impuestos[] = [
                'codigo' => $row['codigo'] ?? 'ITBIS',
                'nombre' => $row['nombre'],
                'base_imponible' => $row['base_imponible'],
                'monto_impuesto' => $row['monto_impuesto'],
                'tasa' => $row['tasa']
            ];
        }
        
        return $impuestos;
    }
    
    /**
     * Enviar factura electrónica a DGII
     */
    public function enviarFacturaDGII($comprobanteId) {
        $comprobante = $this->obtenerComprobante($comprobanteId);
        
        if (!$comprobante || !$comprobante['xml_firmado']) {
            throw new Exception("Factura electrónica no generada");
        }
        
        require_once __DIR__ . '/DGIIService.php';
        $dgiiService = new DGIIService();
        
        // Enviar XML firmado a DGII
        $resultado = $dgiiService->enviarFacturaElectronica([
            'comprobante_id' => $comprobanteId,
            'xml' => $comprobante['xml_firmado'],
            'numero_ncf' => $comprobante['numero_ncf']
        ]);
        
        // Actualizar estado
        $this->db->query(
            "UPDATE comprobantes_fiscales 
             SET estado_electronico = 'enviado',
                 dgii_enviado = 1,
                 dgii_fecha_envio = NOW(),
                 dgii_respuesta = ?,
                 dgii_trackid = ?
             WHERE id = ?",
            [
                json_encode($resultado),
                $resultado['trackid'] ?? null,
                $comprobanteId
            ]
        );
        
        return $resultado;
    }
}

