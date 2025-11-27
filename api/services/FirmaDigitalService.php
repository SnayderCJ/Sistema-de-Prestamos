<?php
/**
 * Servicio de Firma Digital
 * Firma documentos XML según estándares XAdES y DGII
 */

class FirmaDigitalService {
    private $db;
    private $certificadoPath;
    private $certificadoPassword;
    private $privateKeyPath;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cargarConfiguracion();
    }
    
    /**
     * Cargar configuración del certificado digital
     */
    private function cargarConfiguracion() {
        // Obtener configuración de la base de datos
        $stmt = $this->db->query(
            "SELECT clave, valor FROM configuracion_sistema 
             WHERE clave IN ('certificado_digital_path', 'certificado_digital_password', 'private_key_path')"
        );
        
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $this->certificadoPath = $config['certificado_digital_path'] ?? __DIR__ . '/../certificates/certificado.p12';
        $this->certificadoPassword = $config['certificado_digital_password'] ?? '';
        $this->privateKeyPath = $config['private_key_path'] ?? __DIR__ . '/../certificates/private_key.pem';
    }
    
    /**
     * Firmar XML según estándar XAdES
     */
    public function firmarXML($xml, $comprobante) {
        try {
            // Cargar certificado
            $certificado = $this->cargarCertificado();
            
            if (!$certificado) {
                throw new Exception("No se pudo cargar el certificado digital");
            }
            
            // Crear documento XML
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($xml);
            
            // Crear firma digital
            $firma = $this->crearFirmaDigital($dom, $certificado, $comprobante);
            
            // Agregar firma al documento
            $dom->documentElement->appendChild($firma);
            
            return $dom->saveXML();
            
        } catch (Exception $e) {
            error_log("Error firmando XML: " . $e->getMessage());
            throw new Exception("Error al firmar el documento: " . $e->getMessage());
        }
    }
    
    /**
     * Cargar certificado digital
     */
    private function cargarCertificado() {
        if (!file_exists($this->certificadoPath)) {
            throw new Exception("Certificado digital no encontrado en: " . $this->certificadoPath);
        }
        
        // Leer certificado P12
        $certData = file_get_contents($this->certificadoPath);
        
        // Abrir certificado
        $cert = null;
        $key = null;
        
        if (!openssl_pkcs12_read($certData, $certInfo, $this->certificadoPassword)) {
            throw new Exception("Error al leer el certificado: " . openssl_error_string());
        }
        
        return [
            'cert' => $certInfo['cert'],
            'key' => $certInfo['pkey'],
            'extracerts' => $certInfo['extracerts'] ?? []
        ];
    }
    
    /**
     * Crear firma digital XAdES
     */
    private function crearFirmaDigital($dom, $certificado, $comprobante) {
        // Crear elemento de firma
        $signature = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $signature->setAttribute('Id', 'Signature-' . $comprobante['id']);
        $dom->documentElement->appendChild($signature);
        
        // SignedInfo
        $signedInfo = $dom->createElement('SignedInfo');
        $signature->appendChild($signedInfo);
        
        // CanonicalizationMethod
        $canonicalizationMethod = $dom->createElement('CanonicalizationMethod');
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfo->appendChild($canonicalizationMethod);
        
        // SignatureMethod
        $signatureMethod = $dom->createElement('SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
        $signedInfo->appendChild($signatureMethod);
        
        // Reference
        $reference = $dom->createElement('Reference');
        $reference->setAttribute('URI', '');
        $signedInfo->appendChild($reference);
        
        // Transforms
        $transforms = $dom->createElement('Transforms');
        $reference->appendChild($transforms);
        
        $transform = $dom->createElement('Transform');
        $transform->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transforms->appendChild($transform);
        
        // DigestMethod
        $digestMethod = $dom->createElement('DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
        $reference->appendChild($digestMethod);
        
        // Calcular digest
        $canonicalXML = $dom->C14N();
        $digestValue = base64_encode(sha1($canonicalXML, true));
        
        $digestValueElement = $dom->createElement('DigestValue', $digestValue);
        $reference->appendChild($digestValueElement);
        
        // Firmar SignedInfo
        $signedInfoXML = $signedInfo->C14N();
        $signatureValue = $this->firmarContenido($signedInfoXML, $certificado['key']);
        
        // SignatureValue
        $signatureValueElement = $dom->createElement('SignatureValue', base64_encode($signatureValue));
        $signature->appendChild($signatureValueElement);
        
        // KeyInfo
        $keyInfo = $dom->createElement('KeyInfo');
        $signature->appendChild($keyInfo);
        
        // X509Data
        $x509Data = $dom->createElement('X509Data');
        $keyInfo->appendChild($x509Data);
        
        // X509Certificate
        $certClean = $this->limpiarCertificado($certificado['cert']);
        $x509Certificate = $dom->createElement('X509Certificate', $certClean);
        $x509Data->appendChild($x509Certificate);
        
        return $signature;
    }
    
    /**
     * Firmar contenido con clave privada
     */
    private function firmarContenido($contenido, $privateKey) {
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        
        if (!$privateKeyResource) {
            throw new Exception("Error al cargar la clave privada: " . openssl_error_string());
        }
        
        $signature = '';
        if (!openssl_sign($contenido, $signature, $privateKeyResource, OPENSSL_ALGO_SHA1)) {
            throw new Exception("Error al firmar: " . openssl_error_string());
        }
        
        openssl_free_key($privateKeyResource);
        
        return $signature;
    }
    
    /**
     * Limpiar certificado (remover headers)
     */
    private function limpiarCertificado($cert) {
        $cert = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $cert = str_replace('-----END CERTIFICATE-----', '', $cert);
        $cert = str_replace("\n", '', $cert);
        $cert = str_replace("\r", '', $cert);
        $cert = trim($cert);
        
        return $cert;
    }
    
    /**
     * Validar firma digital
     */
    public function validarFirma($xmlFirmado) {
        try {
            $dom = new DOMDocument();
            $dom->loadXML($xmlFirmado);
            
            // Obtener firma
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            
            $signatures = $xpath->query('//ds:Signature');
            
            if ($signatures->length === 0) {
                return ['valido' => false, 'mensaje' => 'No se encontró firma digital'];
            }
            
            $signature = $signatures->item(0);
            
            // Obtener certificado
            $certNodes = $xpath->query('.//ds:X509Certificate', $signature);
            
            if ($certNodes->length === 0) {
                return ['valido' => false, 'mensaje' => 'No se encontró certificado en la firma'];
            }
            
            $certData = '-----BEGIN CERTIFICATE-----' . "\n" . 
                       chunk_split($certNodes->item(0)->nodeValue, 64, "\n") . 
                       '-----END CERTIFICATE-----';
            
            $cert = openssl_x509_read($certData);
            
            if (!$cert) {
                return ['valido' => false, 'mensaje' => 'Error al leer el certificado'];
            }
            
            // Verificar certificado
            $valid = openssl_x509_checkpurpose($cert, X509_PURPOSE_ANY);
            
            openssl_x509_free($cert);
            
            return [
                'valido' => $valid !== false,
                'mensaje' => $valid ? 'Firma válida' : 'Firma inválida'
            ];
            
        } catch (Exception $e) {
            return [
                'valido' => false,
                'mensaje' => 'Error validando firma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener información del certificado
     */
    public function obtenerInfoCertificado() {
        try {
            $certificado = $this->cargarCertificado();
            $cert = openssl_x509_read($certificado['cert']);
            
            if (!$cert) {
                throw new Exception("Error al leer el certificado");
            }
            
            $info = openssl_x509_parse($cert);
            openssl_x509_free($cert);
            
            return [
                'subject' => $info['subject'] ?? [],
                'issuer' => $info['issuer'] ?? [],
                'validFrom' => date('Y-m-d H:i:s', $info['validFrom_time_t'] ?? 0),
                'validTo' => date('Y-m-d H:i:s', $info['validTo_time_t'] ?? 0),
                'serialNumber' => $info['serialNumber'] ?? '',
                'valid' => time() >= ($info['validFrom_time_t'] ?? 0) && time() <= ($info['validTo_time_t'] ?? PHP_INT_MAX)
            ];
            
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}

