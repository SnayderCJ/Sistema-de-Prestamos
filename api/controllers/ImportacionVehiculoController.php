<?php
/**
 * Controlador de Importaciones de Vehículos
 */

class ImportacionVehiculoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['marca']) || empty($data['marca'])) {
            $errors[] = 'Marca es requerida';
        }
        
        if (!isset($data['numero_chasis']) || empty($data['numero_chasis'])) {
            $errors[] = 'Número de chasis es requerido';
        }
        
        if (!isset($data['valor_fob']) || $data['valor_fob'] <= 0) {
            $errors[] = 'Valor FOB inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Calcular CIF
        $valorFOB = $data['valor_fob'];
        $flete = $data['flete'] ?? 0;
        $seguro = $data['seguro'] ?? 0;
        $cif = $valorFOB + $flete + $seguro;
        
        // Calcular impuestos
        $impuestos = $this->calcularImpuestos($cif, $data);
        $totalImpuestos = array_sum(array_column($impuestos, 'monto'));
        
        $valorTotal = $cif + $totalImpuestos;
        
        // Generar número de importación
        $numeroImportacion = $this->generarNumeroImportacion();
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Insertar importación
            $this->db->query(
                "INSERT INTO importaciones_vehiculos (
                    numero_importacion, vehiculo_id, financiamiento_id,
                    marca, modelo, ano, numero_chasis, numero_motor,
                    pais_origen, puerto_entrada, fecha_importacion,
                    valor_factura, moneda_factura_id, tasa_cambio,
                    valor_fob, flete, seguro, cif, arancel, itbis,
                    selectivo, otros_impuestos, total_impuestos, valor_total,
                    numero_dai, fecha_dai, agente_aduanero, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $numeroImportacion,
                    $data['vehiculo_id'] ?? null,
                    $data['financiamiento_id'] ?? null,
                    sanitizeInput($data['marca']),
                    sanitizeInput($data['modelo']),
                    $data['ano'],
                    $data['numero_chasis'],
                    $data['numero_motor'] ?? null,
                    $data['pais_origen'] ?? 'Estados Unidos',
                    $data['puerto_entrada'] ?? null,
                    $data['fecha_importacion'] ?? date('Y-m-d'),
                    $data['valor_factura'] ?? $valorFOB,
                    $data['moneda_factura_id'] ?? 2, // USD por defecto
                    $data['tasa_cambio'] ?? 1.0,
                    $valorFOB,
                    $flete,
                    $seguro,
                    $cif,
                    $impuestos['arancel']['monto'] ?? 0,
                    $impuestos['itbis']['monto'] ?? 0,
                    $impuestos['selectivo']['monto'] ?? 0,
                    $impuestos['otros']['monto'] ?? 0,
                    $totalImpuestos,
                    $valorTotal,
                    $data['numero_dai'] ?? null,
                    $data['fecha_dai'] ?? null,
                    $data['agente_aduanero'] ?? null,
                    $data['observaciones'] ?? null
                ]
            );
            
            $importacionId = $this->db->lastInsertId();
            
            // Insertar impuestos de importación
            foreach ($impuestos as $impuesto) {
                if ($impuesto['monto'] > 0) {
                    $this->db->query(
                        "INSERT INTO impuestos_importacion (
                            importacion_id, impuesto_id, base_imponible,
                            porcentaje, monto_fijo, monto_impuesto
                        ) VALUES (?, ?, ?, ?, ?, ?)",
                        [
                            $importacionId,
                            $impuesto['impuesto_id'],
                            $impuesto['base'],
                            $impuesto['porcentaje'] ?? null,
                            $impuesto['fijo'] ?? null,
                            $impuesto['monto']
                        ]
                    );
                }
            }
            
            $this->db->getConnection()->commit();
            
            $this->getById($importacionId);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error creando importación: " . $e->getMessage());
            sendError('Error al crear la importación', 500);
        }
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['vehiculo_id'])) {
            $where[] = "i.vehiculo_id = ?";
            $params[] = $filters['vehiculo_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT i.*, 
                    m.codigo as moneda_codigo,
                    m.simbolo as moneda_simbolo
             FROM importaciones_vehiculos i
             LEFT JOIN monedas m ON i.moneda_factura_id = m.id
             WHERE $whereClause
             ORDER BY i.fecha_importacion DESC",
            $params
        );
        
        $importaciones = $stmt->fetchAll();
        sendResponse($importaciones);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT i.*, 
                    m.codigo as moneda_codigo,
                    m.simbolo as moneda_simbolo
             FROM importaciones_vehiculos i
             LEFT JOIN monedas m ON i.moneda_factura_id = m.id
             WHERE i.id = ?",
            [$id]
        );
        
        $importacion = $stmt->fetch();
        
        if (!$importacion) {
            sendError('Importación no encontrada', 404);
        }
        
        // Obtener impuestos
        $impuestosStmt = $this->db->query(
            "SELECT ii.*, imp.nombre as impuesto_nombre
             FROM impuestos_importacion ii
             LEFT JOIN impuestos imp ON ii.impuesto_id = imp.id
             WHERE ii.importacion_id = ?",
            [$id]
        );
        
        $importacion['impuestos'] = $impuestosStmt->fetchAll();
        
        sendResponse($importacion);
    }
    
    private function calcularImpuestos($cif, $data) {
        $impuestos = [];
        
        // Obtener impuestos configurados
        $stmt = $this->db->query(
            "SELECT * FROM impuestos WHERE aplica_a IN ('importacion', 'general') AND activo = 1"
        );
        
        $impuestosConfig = $stmt->fetchAll();
        
        foreach ($impuestosConfig as $imp) {
            $monto = 0;
            $base = $cif;
            
            if ($imp['tipo'] === 'porcentaje') {
                $porcentaje = $data['porcentaje_' . strtolower($imp['codigo'])] ?? $imp['valor'];
                $monto = $base * ($porcentaje / 100);
            } else {
                $monto = $imp['valor'];
            }
            
            $impuestos[strtolower($imp['codigo'])] = [
                'impuesto_id' => $imp['id'],
                'base' => $base,
                'porcentaje' => $imp['tipo'] === 'porcentaje' ? ($data['porcentaje_' . strtolower($imp['codigo'])] ?? $imp['valor']) : null,
                'fijo' => $imp['tipo'] === 'fijo' ? $imp['valor'] : null,
                'monto' => $monto
            ];
        }
        
        return $impuestos;
    }
    
    private function generarNumeroImportacion() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM importaciones_vehiculos WHERE YEAR(fecha_creacion) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "IMP-$year-$numero";
    }
}


