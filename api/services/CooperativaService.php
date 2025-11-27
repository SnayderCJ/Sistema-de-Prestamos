<?php
/**
 * Servicio de Gestión de Cooperativas
 */

class CooperativaService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Calcular distribución de utilidades
     */
    public function calcularDistribucionUtilidades($cooperativaId, $periodo, $montoTotal, $metodo = 'por_apartaciones') {
        // Obtener socios activos
        $socios = $this->obtenerSociosActivos($cooperativaId);
        
        if (empty($socios)) {
            throw new Exception("No hay socios activos en la cooperativa");
        }
        
        $distribuciones = [];
        $totalApartaciones = 0;
        
        // Calcular total de apartaciones del período
        foreach ($socios as $socio) {
            $apartacionesPeriodo = $this->obtenerApartacionesPeriodo($socio['id'], $periodo);
            $totalApartaciones += $apartacionesPeriodo;
        }
        
        // Distribuir según método
        foreach ($socios as $socio) {
            $montoUtilidad = 0;
            $porcentaje = 0;
            
            switch ($metodo) {
                case 'igual':
                    // Distribución igualitaria
                    $montoUtilidad = $montoTotal / count($socios);
                    $porcentaje = 100 / count($socios);
                    break;
                    
                case 'porcentaje':
                    // Distribución por porcentaje asignado
                    $porcentajeSocio = $socio['porcentaje_utilidad'] ?? 0;
                    if ($porcentajeSocio > 0) {
                        $montoUtilidad = $montoTotal * ($porcentajeSocio / 100);
                        $porcentaje = $porcentajeSocio;
                    }
                    break;
                    
                case 'por_apartaciones':
                    // Distribución proporcional a apartaciones
                    $apartacionesSocio = $this->obtenerApartacionesPeriodo($socio['id'], $periodo);
                    if ($totalApartaciones > 0) {
                        $porcentaje = ($apartacionesSocio / $totalApartaciones) * 100;
                        $montoUtilidad = $montoTotal * ($apartacionesSocio / $totalApartaciones);
                    }
                    break;
                    
                case 'mixto':
                    // Mixto: 50% por apartaciones, 50% por porcentaje asignado
                    $apartacionesSocio = $this->obtenerApartacionesPeriodo($socio['id'], $periodo);
                    $porcentajeSocio = $socio['porcentaje_utilidad'] ?? 0;
                    
                    $montoPorApartaciones = 0;
                    $montoPorPorcentaje = 0;
                    
                    if ($totalApartaciones > 0) {
                        $montoPorApartaciones = ($montoTotal * 0.5) * ($apartacionesSocio / $totalApartaciones);
                    }
                    
                    if ($porcentajeSocio > 0) {
                        $montoPorPorcentaje = ($montoTotal * 0.5) * ($porcentajeSocio / 100);
                    }
                    
                    $montoUtilidad = $montoPorApartaciones + $montoPorPorcentaje;
                    $porcentaje = ($apartacionesSocio / $totalApartaciones * 50) + ($porcentajeSocio * 0.5);
                    break;
            }
            
            if ($montoUtilidad > 0) {
                $distribuciones[] = [
                    'socio_id' => $socio['id'],
                    'monto_utilidad' => $montoUtilidad,
                    'porcentaje_asignado' => $porcentaje,
                    'monto_apartaciones_periodo' => $this->obtenerApartacionesPeriodo($socio['id'], $periodo),
                    'metodo_calculo' => $metodo
                ];
            }
        }
        
        return $distribuciones;
    }
    
    /**
     * Obtener socios activos
     */
    private function obtenerSociosActivos($cooperativaId) {
        $stmt = $this->db->query(
            "SELECT * FROM socios 
             WHERE cooperativa_id = ? AND activo = 1 
             ORDER BY nombre, apellido",
            [$cooperativaId]
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener apartaciones de un socio en un período
     */
    private function obtenerApartacionesPeriodo($socioId, $periodo) {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) as total
             FROM apartaciones
             WHERE socio_id = ? 
             AND DATE_FORMAT(fecha_apartacion, '%Y-%m') = ?",
            [$socioId, $periodo]
        );
        
        $result = $stmt->fetch();
        return floatval($result['total'] ?? 0);
    }
    
    /**
     * Obtener total de apartaciones de un socio
     */
    public function obtenerTotalApartaciones($socioId) {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) as total
             FROM apartaciones
             WHERE socio_id = ?",
            [$socioId]
        );
        
        $result = $stmt->fetch();
        return floatval($result['total'] ?? 0);
    }
    
    /**
     * Actualizar resumen de apartaciones
     */
    public function actualizarResumenApartaciones($socioId) {
        $total = $this->obtenerTotalApartaciones($socioId);
        
        // Obtener última apartación
        $stmt = $this->db->query(
            "SELECT fecha_apartacion, cooperativa_id
             FROM apartaciones
             WHERE socio_id = ?
             ORDER BY fecha_apartacion DESC
             LIMIT 1",
            [$socioId]
        );
        
        $ultima = $stmt->fetch();
        
        // Insertar o actualizar resumen
        $this->db->query(
            "INSERT INTO socios_apartaciones_resumen 
             (socio_id, cooperativa_id, total_apartaciones, ultima_apartacion, ultima_actualizacion)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
             total_apartaciones = VALUES(total_apartaciones),
             ultima_apartacion = VALUES(ultima_apartacion),
             ultima_actualizacion = NOW()",
            [
                $socioId,
                $ultima['cooperativa_id'] ?? null,
                $total,
                $ultima['fecha_apartacion'] ?? null
            ]
        );
    }
}

