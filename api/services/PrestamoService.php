<?php
/**
 * Servicio de Préstamos - Lógica de Negocio
 */

class PrestamoService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Calcular valores de un préstamo
     */
    public function calcularPrestamo($monto, $tasaMensual, $plazoMeses) {
        // Convertir tasa mensual a decimal
        $tasaDecimal = $tasaMensual / 100;
        
        // Calcular cuota mensual usando fórmula de anualidad
        if ($tasaDecimal > 0) {
            $factor = pow(1 + $tasaDecimal, $plazoMeses);
            $cuotaMensual = $monto * ($tasaDecimal * $factor) / ($factor - 1);
        } else {
            $cuotaMensual = $monto / $plazoMeses;
        }
        
        $montoTotal = $cuotaMensual * $plazoMeses;
        $interesTotal = $montoTotal - $monto;
        
        return [
            'cuota_mensual' => round($cuotaMensual, 2),
            'monto_total' => round($montoTotal, 2),
            'interes_total' => round($interesTotal, 2)
        ];
    }
    
    /**
     * Crear cuotas de un préstamo
     */
    public function crearCuotas($prestamoId, $calculos, $plazoMeses) {
        $montoTotal = $calculos['monto_total'];
        $cuotaMensual = $calculos['cuota_mensual'];
        $monto = $montoTotal - $calculos['interes_total'];
        
        // Obtener fecha de desembolso o usar fecha actual
        $stmt = $this->db->query("SELECT fecha_desembolso FROM prestamos WHERE id = ?", [$prestamoId]);
        $prestamo = $stmt->fetch();
        $fechaBase = $prestamo['fecha_desembolso'] ? new DateTime($prestamo['fecha_desembolso']) : new DateTime();
        
        $saldoCapital = $monto;
        $interesPorCuota = $calculos['interes_total'] / $plazoMeses;
        $capitalPorCuota = $monto / $plazoMeses;
        
        for ($i = 1; $i <= $plazoMeses; $i++) {
            $fechaVencimiento = clone $fechaBase;
            $fechaVencimiento->modify("+$i months");
            
            $saldoCapital -= $capitalPorCuota;
            
            $this->db->query(
                "INSERT INTO cuotas_prestamos (
                    prestamo_id, numero_cuota, monto_cuota, capital, interes,
                    saldo_capital, fecha_vencimiento, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')",
                [
                    $prestamoId,
                    $i,
                    round($cuotaMensual, 2),
                    round($capitalPorCuota, 2),
                    round($interesPorCuota, 2),
                    round($saldoCapital, 2),
                    $fechaVencimiento->format('Y-m-d')
                ]
            );
        }
    }
    
    /**
     * Calcular saldo pendiente de un préstamo
     */
    public function calcularSaldoPendiente($prestamoId) {
        $stmt = $this->db->query(
            "SELECT SUM(saldo_capital) as saldo 
             FROM cuotas_prestamos 
             WHERE prestamo_id = ? AND estado != 'pagada'",
            [$prestamoId]
        );
        
        $result = $stmt->fetch();
        return $result['saldo'] ?? 0;
    }
    
    /**
     * Contar cuotas pagadas
     */
    public function contarCuotasPagadas($prestamoId) {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total 
             FROM cuotas_prestamos 
             WHERE prestamo_id = ? AND estado = 'pagada'",
            [$prestamoId]
        );
        
        return $stmt->fetch()['total'];
    }
    
    /**
     * Contar cuotas vencidas
     */
    public function contarCuotasVencidas($prestamoId) {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total 
             FROM cuotas_prestamos 
             WHERE prestamo_id = ? 
             AND estado = 'vencida' 
             AND fecha_vencimiento < CURDATE()",
            [$prestamoId]
        );
        
        return $stmt->fetch()['total'];
    }
    
    /**
     * Calcular mora total
     */
    public function calcularMoraTotal($prestamoId) {
        $stmt = $this->db->query(
            "SELECT SUM(mora) as mora_total 
             FROM cuotas_prestamos 
             WHERE prestamo_id = ?",
            [$prestamoId]
        );
        
        $result = $stmt->fetch();
        return $result['mora_total'] ?? 0;
    }
    
    /**
     * Actualizar estado de cuotas vencidas
     */
    public function actualizarCuotasVencidas($prestamoId = null) {
        $where = $prestamoId ? "AND prestamo_id = $prestamoId" : "";
        
        // Actualizar cuotas vencidas
        $this->db->query(
            "UPDATE cuotas_prestamos 
             SET estado = 'vencida',
                 dias_mora = DATEDIFF(CURDATE(), fecha_vencimiento)
             WHERE estado = 'pendiente' 
             AND fecha_vencimiento < CURDATE() 
             $where"
        );
        
        // Calcular mora para cuotas vencidas
        $stmt = $this->db->query(
            "SELECT id, monto_cuota, dias_mora 
             FROM cuotas_prestamos 
             WHERE estado = 'vencida' 
             AND mora = 0
             $where"
        );
        
        $cuotas = $stmt->fetchAll();
        
        foreach ($cuotas as $cuota) {
            $mora = $this->calcularMora($cuota['monto_cuota'], $cuota['dias_mora']);
            $this->db->query(
                "UPDATE cuotas_prestamos SET mora = ? WHERE id = ?",
                [$mora, $cuota['id']]
            );
        }
    }
    
    /**
     * Calcular mora
     */
    private function calcularMora($montoCuota, $diasMora) {
        if ($diasMora <= DIAS_GRACIA) {
            return 0;
        }
        
        $diasMoraEfectivos = $diasMora - DIAS_GRACIA;
        $tasaMoraDiaria = TASA_MORA_DIARIA / 100;
        
        return round($montoCuota * $tasaMoraDiaria * $diasMoraEfectivos, 2);
    }
}


