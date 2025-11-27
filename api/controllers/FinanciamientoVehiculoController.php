<?php
/**
 * Controlador de Financiamientos de Vehículos
 */

class FinanciamientoVehiculoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['cliente_id']) || !$data['cliente_id']) {
            $errors[] = 'Cliente es requerido';
        }
        
        if (!isset($data['marca']) || empty($data['marca'])) {
            $errors[] = 'Marca es requerida';
        }
        
        if (!isset($data['modelo']) || empty($data['modelo'])) {
            $errors[] = 'Modelo es requerido';
        }
        
        if (!isset($data['numero_chasis']) || empty($data['numero_chasis'])) {
            $errors[] = 'Número de chasis es requerido';
        }
        
        if (!isset($data['valor_financiado']) || $data['valor_financiado'] <= 0) {
            $errors[] = 'Valor financiado inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el chasis no esté duplicado
        $stmt = $this->db->query(
            "SELECT id FROM financiamientos_vehiculos WHERE numero_chasis = ?",
            [$data['numero_chasis']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe un financiamiento con este número de chasis', 400);
        }
        
        // Calcular cuota mensual
        $valorFinanciado = $data['valor_financiado'];
        $tasaInteres = $data['tasa_interes'] ?? 2.5;
        $plazoMeses = $data['plazo_meses'] ?? 36;
        $cuotaMensual = $this->calcularCuota($valorFinanciado, $tasaInteres, $plazoMeses);
        
        // Generar número de financiamiento
        $numeroFinanciamiento = $this->generarNumeroFinanciamiento();
        
        $this->db->query(
            "INSERT INTO financiamientos_vehiculos (
                numero_financiamiento, prestamo_id, cliente_id, tipo_financiamiento,
                vehiculo_id, marca, modelo, ano, color, numero_chasis, numero_motor,
                numero_placa, kilometraje, valor_comercial, valor_financiado,
                monto_inicial, plazo_meses, tasa_interes, cuota_mensual,
                seguro_afiliado, numero_poliza_seguro, fecha_vencimiento_seguro,
                tasador, fecha_tasacion, valor_tasacion, banco_id, moneda_id,
                fecha_inicio, fecha_vencimiento, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $numeroFinanciamiento,
                $data['prestamo_id'] ?? null,
                $data['cliente_id'],
                $data['tipo_financiamiento'] ?? 'propio',
                $data['vehiculo_id'] ?? null,
                sanitizeInput($data['marca']),
                sanitizeInput($data['modelo']),
                $data['ano'],
                $data['color'] ?? null,
                $data['numero_chasis'],
                $data['numero_motor'] ?? null,
                $data['numero_placa'] ?? null,
                $data['kilometraje'] ?? null,
                $data['valor_comercial'] ?? $data['valor_financiado'],
                $data['valor_financiado'],
                $data['monto_inicial'] ?? 0,
                $plazoMeses,
                $tasaInteres,
                $cuotaMensual,
                $data['seguro_afiliado'] ?? null,
                $data['numero_poliza_seguro'] ?? null,
                $data['fecha_vencimiento_seguro'] ?? null,
                $data['tasador'] ?? null,
                $data['fecha_tasacion'] ?? null,
                $data['valor_tasacion'] ?? null,
                $data['banco_id'] ?? null,
                $data['moneda_id'] ?? 1,
                $data['fecha_inicio'] ?? date('Y-m-d'),
                $this->calcularFechaVencimiento($data['fecha_inicio'] ?? date('Y-m-d'), $plazoMeses),
                $data['observaciones'] ?? null
            ]
        );
        
        $financiamientoId = $this->db->lastInsertId();
        $this->getById($financiamientoId);
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['cliente_id'])) {
            $where[] = "f.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        
        if (isset($filters['tipo_financiamiento'])) {
            $where[] = "f.tipo_financiamiento = ?";
            $params[] = $filters['tipo_financiamiento'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "f.estado = ?";
            $params[] = $filters['estado'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT f.*, 
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    b.nombre as banco_nombre,
                    m.codigo as moneda_codigo,
                    m.simbolo as moneda_simbolo
             FROM financiamientos_vehiculos f
             LEFT JOIN clientes c ON f.cliente_id = c.id
             LEFT JOIN bancos b ON f.banco_id = b.id
             LEFT JOIN monedas m ON f.moneda_id = m.id
             WHERE $whereClause
             ORDER BY f.fecha_creacion DESC",
            $params
        );
        
        $financiamientos = $stmt->fetchAll();
        sendResponse($financiamientos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT f.*, 
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    b.nombre as banco_nombre,
                    m.codigo as moneda_codigo,
                    m.simbolo as moneda_simbolo
             FROM financiamientos_vehiculos f
             LEFT JOIN clientes c ON f.cliente_id = c.id
             LEFT JOIN bancos b ON f.banco_id = b.id
             LEFT JOIN monedas m ON f.moneda_id = m.id
             WHERE f.id = ?",
            [$id]
        );
        
        $financiamiento = $stmt->fetch();
        
        if (!$financiamiento) {
            sendError('Financiamiento no encontrado', 404);
        }
        
        sendResponse($financiamiento);
    }
    
    private function calcularCuota($monto, $tasaMensual, $plazo) {
        if ($tasaMensual == 0) {
            return $monto / $plazo;
        }
        
        $tasaDecimal = $tasaMensual / 100;
        $factor = pow(1 + $tasaDecimal, $plazo);
        $cuota = $monto * ($tasaDecimal * $factor) / ($factor - 1);
        
        return round($cuota, 2);
    }
    
    private function calcularFechaVencimiento($fechaInicio, $plazoMeses) {
        $fecha = new DateTime($fechaInicio);
        $fecha->modify("+$plazoMeses months");
        return $fecha->format('Y-m-d');
    }
    
    private function generarNumeroFinanciamiento() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM financiamientos_vehiculos WHERE YEAR(fecha_creacion) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "FIN-$year-$numero";
    }
}


