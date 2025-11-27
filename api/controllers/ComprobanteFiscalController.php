<?php
/**
 * Controlador de Comprobantes Fiscales (NCF)
 */

class ComprobanteFiscalController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function generar($data) {
        $errors = [];
        
        if (!isset($data['tipo_comprobante_id']) || !$data['tipo_comprobante_id']) {
            $errors[] = 'Tipo de comprobante es requerido';
        }
        
        if (!isset($data['cliente_id']) || !$data['cliente_id']) {
            $errors[] = 'Cliente es requerido';
        }
        
        if (!isset($data['monto_subtotal']) || $data['monto_subtotal'] <= 0) {
            $errors[] = 'Monto subtotal inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Calcular impuestos
        $impuestos = $this->calcularImpuestos($data['monto_subtotal'], $data['impuestos'] ?? []);
        $totalImpuestos = array_sum(array_column($impuestos, 'monto'));
        $montoTotal = $data['monto_subtotal'] + $totalImpuestos;
        
        // Generar NCF
        $numeroNCF = $this->generarNCF($data['tipo_comprobante_id']);
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Insertar comprobante
            $this->db->query(
                "INSERT INTO comprobantes_fiscales (
                    numero_ncf, tipo_comprobante_id, prestamo_id, pago_id,
                    cliente_id, fecha_emision, monto_subtotal, monto_impuestos,
                    monto_total, rnc_cliente, razon_social, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $numeroNCF,
                    $data['tipo_comprobante_id'],
                    $data['prestamo_id'] ?? null,
                    $data['pago_id'] ?? null,
                    $data['cliente_id'],
                    $data['fecha_emision'] ?? date('Y-m-d'),
                    $data['monto_subtotal'],
                    $totalImpuestos,
                    $montoTotal,
                    $data['rnc_cliente'] ?? null,
                    $data['razon_social'] ?? null,
                    $data['observaciones'] ?? null
                ]
            );
            
            $comprobanteId = $this->db->lastInsertId();
            
            // Insertar impuestos
            foreach ($impuestos as $impuesto) {
                if ($impuesto['monto'] > 0) {
                    $this->db->query(
                        "INSERT INTO impuestos_comprobantes (
                            comprobante_id, impuesto_id, base_imponible, monto_impuesto
                        ) VALUES (?, ?, ?, ?)",
                        [
                            $comprobanteId,
                            $impuesto['impuesto_id'],
                            $impuesto['base'],
                            $impuesto['monto']
                        ]
                    );
                }
            }
            
            $this->db->getConnection()->commit();
            
            // Validar NCF con DGII si está configurado
            try {
                require_once __DIR__ . '/../services/DGIIService.php';
                $dgiiService = new DGIIService();
                
                // Obtener cliente para RNC
                $clienteStmt = $this->db->query(
                    "SELECT rnc FROM clientes WHERE id = ?",
                    [$data['cliente_id']]
                );
                $cliente = $clienteStmt->fetch();
                
                if ($cliente && $cliente['rnc']) {
                    $validacion = $dgiiService->validarNCF($numeroNCF, $cliente['rnc']);
                    
                    // Actualizar estado según validación
                    if (isset($validacion['valido']) && $validacion['valido']) {
                        $this->db->query(
                            "UPDATE comprobantes_fiscales SET dgii_validado = 1 WHERE id = ?",
                            [$comprobanteId]
                        );
                    }
                }
            } catch (Exception $e) {
                // No fallar si DGII no está disponible, solo loguear
                error_log("Error validando NCF con DGII: " . $e->getMessage());
            }
            
            // Enviar factura a DGII si está configurado
            try {
                require_once __DIR__ . '/../services/DGIIService.php';
                $dgiiService = new DGIIService();
                
                $facturaData = [
                    'comprobante_id' => $comprobanteId,
                    'numero_ncf' => $numeroNCF,
                    'cliente_id' => $data['cliente_id'],
                    'monto_total' => $montoTotal,
                    'fecha_emision' => $data['fecha_emision'] ?? date('Y-m-d')
                ];
                
                $dgiiService->enviarFactura($facturaData);
            } catch (Exception $e) {
                // No fallar si DGII no está disponible, solo loguear
                error_log("Error enviando factura a DGII: " . $e->getMessage());
            }
            
            // Disparar webhook
            try {
                require_once __DIR__ . '/../services/WebhookService.php';
                $webhookService = new WebhookService();
                $webhookService->dispararWebhook('comprobante.generado', [
                    'comprobante_id' => $comprobanteId,
                    'numero_ncf' => $numeroNCF,
                    'monto_total' => $montoTotal
                ]);
            } catch (Exception $e) {
                error_log("Error disparando webhook: " . $e->getMessage());
            }
            
            $this->getById($comprobanteId);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error generando comprobante: " . $e->getMessage());
            sendError('Error al generar el comprobante', 500);
        }
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['cliente_id'])) {
            $where[] = "c.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        
        if (isset($filters['prestamo_id'])) {
            $where[] = "c.prestamo_id = ?";
            $params[] = $filters['prestamo_id'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(c.fecha_emision) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(c.fecha_emision) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT c.*, 
                    t.nombre as tipo_comprobante_nombre,
                    cl.cedula as cliente_cedula,
                    cl.nombre as cliente_nombre,
                    cl.apellido as cliente_apellido
             FROM comprobantes_fiscales c
             LEFT JOIN tipos_comprobantes t ON c.tipo_comprobante_id = t.id
             LEFT JOIN clientes cl ON c.cliente_id = cl.id
             WHERE $whereClause
             ORDER BY c.fecha_emision DESC",
            $params
        );
        
        $comprobantes = $stmt->fetchAll();
        sendResponse($comprobantes);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT c.*, 
                    t.nombre as tipo_comprobante_nombre,
                    cl.cedula as cliente_cedula,
                    cl.nombre as cliente_nombre,
                    cl.apellido as cliente_apellido
             FROM comprobantes_fiscales c
             LEFT JOIN tipos_comprobantes t ON c.tipo_comprobante_id = t.id
             LEFT JOIN clientes cl ON c.cliente_id = cl.id
             WHERE c.id = ?",
            [$id]
        );
        
        $comprobante = $stmt->fetch();
        
        if (!$comprobante) {
            sendError('Comprobante no encontrado', 404);
        }
        
        // Obtener impuestos
        $impuestosStmt = $this->db->query(
            "SELECT ic.*, imp.nombre as impuesto_nombre, imp.codigo as impuesto_codigo
             FROM impuestos_comprobantes ic
             LEFT JOIN impuestos imp ON ic.impuesto_id = imp.id
             WHERE ic.comprobante_id = ?",
            [$id]
        );
        
        $comprobante['impuestos'] = $impuestosStmt->fetchAll();
        
        sendResponse($comprobante);
    }
    
    public function anular($id, $data) {
        $this->db->query(
            "UPDATE comprobantes_fiscales SET 
                estado = 'anulado',
                observaciones = ?
             WHERE id = ? AND estado = 'emitido'",
            [$data['motivo'] ?? 'Anulado por el usuario', $id]
        );
        
        $this->getById($id);
    }
    
    private function calcularImpuestos($subtotal, $impuestosSeleccionados = []) {
        $resultado = [];
        
        // Si no se especifican impuestos, aplicar ITBIS por defecto
        if (empty($impuestosSeleccionados)) {
            $stmt = $this->db->query(
                "SELECT * FROM impuestos WHERE codigo = 'ITBIS' AND activo = 1 LIMIT 1"
            );
            $itbis = $stmt->fetch();
            
            if ($itbis) {
                $monto = $subtotal * ($itbis['valor'] / 100);
                $resultado[] = [
                    'impuesto_id' => $itbis['id'],
                    'base' => $subtotal,
                    'monto' => $monto
                ];
            }
        } else {
            // Aplicar impuestos seleccionados
            foreach ($impuestosSeleccionados as $impId => $config) {
                $stmt = $this->db->query(
                    "SELECT * FROM impuestos WHERE id = ? AND activo = 1",
                    [$impId]
                );
                $imp = $stmt->fetch();
                
                if ($imp) {
                    $base = $config['base'] ?? $subtotal;
                    $monto = 0;
                    
                    if ($imp['tipo'] === 'porcentaje') {
                        $porcentaje = $config['porcentaje'] ?? $imp['valor'];
                        $monto = $base * ($porcentaje / 100);
                    } else {
                        $monto = $config['monto'] ?? $imp['valor'];
                    }
                    
                    $resultado[] = [
                        'impuesto_id' => $imp['id'],
                        'base' => $base,
                        'monto' => $monto
                    ];
                }
            }
        }
        
        return $resultado;
    }
    
    private function generarNCF($tipoComprobanteId) {
        // Obtener tipo de comprobante
        $stmt = $this->db->query(
            "SELECT codigo, serie FROM tipos_comprobantes WHERE id = ?",
            [$tipoComprobanteId]
        );
        
        $tipo = $stmt->fetch();
        
        if (!$tipo) {
            sendError('Tipo de comprobante no encontrado', 404);
        }
        
        // Formato NCF: Serie + Tipo + Secuencial (11 dígitos)
        // Ejemplo: B01000000001
        $serie = $tipo['serie'] ?? 'B';
        $codigoTipo = str_pad($tipo['codigo'], 2, '0', STR_PAD_LEFT);
        
        // Obtener último secuencial
        $stmt = $this->db->query(
            "SELECT numero_ncf FROM comprobantes_fiscales 
             WHERE tipo_comprobante_id = ? 
             ORDER BY id DESC LIMIT 1",
            [$tipoComprobanteId]
        );
        
        $ultimo = $stmt->fetch();
        $secuencial = 1;
        
        if ($ultimo) {
            $ultimoSecuencial = substr($ultimo['numero_ncf'], -11);
            $secuencial = intval($ultimoSecuencial) + 1;
        }
        
        $secuencialStr = str_pad($secuencial, 11, '0', STR_PAD_LEFT);
        
        return $serie . $codigoTipo . $secuencialStr;
    }
}


