<?php
/**
 * Controlador de Nómina
 * Según leyes de República Dominicana
 */

class NominaController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function calcular($empleadoId, $periodo) {
        // Obtener datos del empleado
        $stmt = $this->db->query(
            "SELECT e.*, u.nombre, u.apellido 
             FROM empleados e
             LEFT JOIN usuarios u ON e.usuario_id = u.id
             WHERE e.usuario_id = ?",
            [$empleadoId]
        );
        
        $empleado = $stmt->fetch();
        
        if (!$empleado) {
            sendError('Empleado no encontrado', 404);
        }
        
        // Calcular según leyes RD
        $salarioBase = $empleado['salario_base'];
        $horasExtras = $data['horas_extras'] ?? 0;
        $montoHorasExtras = $this->calcularHorasExtras($salarioBase, $horasExtras, $empleado['horas_semanales']);
        $bonos = $data['bonos'] ?? 0;
        $comisiones = $data['comisiones'] ?? 0;
        $otrosIngresos = $data['otros_ingresos'] ?? 0;
        
        $totalIngresos = $salarioBase + $montoHorasExtras + $bonos + $comisiones + $otrosIngresos;
        
        // Descuentos según leyes RD
        $afp = $this->calcularAFP($totalIngresos);
        $ars = $this->calcularARS($totalIngresos);
        $isr = $this->calcularISR($totalIngresos);
        $otrosDescuentos = $data['otros_descuentos'] ?? 0;
        
        $totalDescuentos = $afp + $ars + $isr + $otrosDescuentos;
        $netoPagar = $totalIngresos - $totalDescuentos;
        
        return [
            'salario_base' => $salarioBase,
            'horas_extras' => $horasExtras,
            'monto_horas_extras' => $montoHorasExtras,
            'bonos' => $bonos,
            'comisiones' => $comisiones,
            'otros_ingresos' => $otrosIngresos,
            'total_ingresos' => $totalIngresos,
            'afp' => $afp,
            'ars' => $ars,
            'isr' => $isr,
            'otros_descuentos' => $otrosDescuentos,
            'total_descuentos' => $totalDescuentos,
            'neto_pagar' => $netoPagar
        ];
    }
    
    public function procesar($data) {
        $calculos = $this->calcular($data['empleado_id'], $data['periodo']);
        
        // Verificar si ya existe nómina para este período
        $stmt = $this->db->query(
            "SELECT id FROM nomina WHERE empleado_id = ? AND periodo = ?",
            [$data['empleado_id'], $data['periodo']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe nómina para este período', 400);
        }
        
        $this->db->query(
            "INSERT INTO nomina (
                empleado_id, periodo, fecha_pago, salario_base,
                horas_extras, monto_horas_extras, bonos, comisiones,
                otros_ingresos, total_ingresos, afp, ars, isr,
                otros_descuentos, total_descuentos, neto_pagar
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['empleado_id'],
                $data['periodo'],
                $data['fecha_pago'],
                $calculos['salario_base'],
                $calculos['horas_extras'],
                $calculos['monto_horas_extras'],
                $calculos['bonos'],
                $calculos['comisiones'],
                $calculos['otros_ingresos'],
                $calculos['total_ingresos'],
                $calculos['afp'],
                $calculos['ars'],
                $calculos['isr'],
                $calculos['otros_descuentos'],
                $calculos['total_descuentos'],
                $calculos['neto_pagar']
            ]
        );
        
        $nominaId = $this->db->lastInsertId();
        $this->getById($nominaId);
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['empleado_id'])) {
            $where[] = "n.empleado_id = ?";
            $params[] = $filters['empleado_id'];
        }
        
        if (isset($filters['periodo'])) {
            $where[] = "n.periodo = ?";
            $params[] = $filters['periodo'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT n.*, 
                    u.nombre as empleado_nombre,
                    u.apellido as empleado_apellido,
                    d.nombre as departamento_nombre
             FROM nomina n
             LEFT JOIN empleados e ON n.empleado_id = e.id
             LEFT JOIN usuarios u ON e.usuario_id = u.id
             LEFT JOIN departamentos d ON e.departamento_id = d.id
             WHERE $whereClause
             ORDER BY n.fecha_pago DESC",
            $params
        );
        
        $nominas = $stmt->fetchAll();
        sendResponse($nominas);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT n.*, 
                    u.nombre as empleado_nombre,
                    u.apellido as empleado_apellido
             FROM nomina n
             LEFT JOIN empleados e ON n.empleado_id = e.id
             LEFT JOIN usuarios u ON e.usuario_id = u.id
             WHERE n.id = ?",
            [$id]
        );
        
        $nomina = $stmt->fetch();
        
        if (!$nomina) {
            sendError('Nómina no encontrada', 404);
        }
        
        sendResponse($nomina);
    }
    
    // Cálculos según leyes RD
    private function calcularAFP($salario) {
        // AFP: 2.87% del salario (límite máximo según RD)
        $tasaAFP = 0.0287;
        $limiteMaximo = 50000; // Límite máximo para AFP en RD
        $baseCalculo = min($salario, $limiteMaximo);
        return $baseCalculo * $tasaAFP;
    }
    
    private function calcularARS($salario) {
        // ARS: 3.04% del salario (límite máximo según RD)
        $tasaARS = 0.0304;
        $limiteMaximo = 50000; // Límite máximo para ARS en RD
        $baseCalculo = min($salario, $limiteMaximo);
        return $baseCalculo * $tasaARS;
    }
    
    private function calcularISR($salario) {
        // ISR según tabla de retención RD 2024
        // Escalonado según rangos
        if ($salario <= 416220) {
            return 0; // Exento
        } elseif ($salario <= 624329) {
            return ($salario - 416220) * 0.15;
        } elseif ($salario <= 867123) {
            return 31216.35 + ($salario - 624329) * 0.20;
        } else {
            return 79776.35 + ($salario - 867123) * 0.25;
        }
    }
    
    private function calcularHorasExtras($salarioBase, $horasExtras, $horasSemanales) {
        // Hora normal
        $horasMensuales = $horasSemanales * 4.33;
        $valorHora = $salarioBase / $horasMensuales;
        
        // Hora extra: 1.35x según código laboral RD
        $valorHoraExtra = $valorHora * 1.35;
        
        return $horasExtras * $valorHoraExtra;
    }
}


