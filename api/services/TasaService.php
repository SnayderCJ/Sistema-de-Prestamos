<?php
/**
 * Servicio de Tasas de Interés
 */

class TasaService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener tasa aplicable según monto y plazo
     */
    public function obtenerTasaAplicable($monto, $plazoMeses) {
        $stmt = $this->db->query(
            "SELECT * FROM tasas_interes 
             WHERE activa = 1 
             AND (monto_minimo IS NULL OR monto_minimo <= ?)
             AND (monto_maximo IS NULL OR monto_maximo >= ?)
             AND (plazo_minimo IS NULL OR plazo_minimo <= ?)
             AND (plazo_maximo IS NULL OR plazo_maximo >= ?)
             AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
             ORDER BY tasa_mensual ASC
             LIMIT 1",
            [$monto, $monto, $plazoMeses, $plazoMeses]
        );
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener todas las tasas activas
     */
    public function obtenerTasasActivas() {
        $stmt = $this->db->query(
            "SELECT * FROM tasas_interes 
             WHERE activa = 1 
             AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
             ORDER BY monto_minimo ASC"
        );
        
        return $stmt->fetchAll();
    }
}


