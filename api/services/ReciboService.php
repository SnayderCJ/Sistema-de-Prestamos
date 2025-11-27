<?php
/**
 * Servicio de Generación de Recibos
 */

class ReciboService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generar recibo de pago
     */
    public function generarRecibo($pagoId) {
        // Obtener datos del pago
        $stmt = $this->db->query(
            "SELECT p.*, 
                    pr.numero_prestamo,
                    pr.monto_aprobado,
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    c.direccion as cliente_direccion,
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido,
                    s.nombre as sucursal_nombre,
                    s.direccion as sucursal_direccion,
                    cp.numero_cuota,
                    cp.fecha_vencimiento
             FROM pagos p
             LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
             LEFT JOIN clientes c ON pr.cliente_id = c.id
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN sucursales s ON p.sucursal_id = s.id
             LEFT JOIN cuotas_prestamos cp ON p.cuota_id = cp.id
             WHERE p.id = ?",
            [$pagoId]
        );
        
        $pago = $stmt->fetch();
        
        if (!$pago) {
            throw new Exception('Pago no encontrado');
        }
        
        // Generar número de recibo
        $numeroRecibo = $pago['numero_recibo'];
        
        // Generar contenido del recibo
        $contenido = $this->generarContenidoRecibo($pago);
        
        // Verificar si ya existe recibo
        $reciboStmt = $this->db->query(
            "SELECT id FROM recibos WHERE pago_id = ?",
            [$pagoId]
        );
        
        $reciboExistente = $reciboStmt->fetch();
        
        if ($reciboExistente) {
            // Actualizar recibo existente
            $this->db->query(
                "UPDATE recibos SET contenido = ? WHERE id = ?",
                [$contenido, $reciboExistente['id']]
            );
            return $reciboExistente['id'];
        } else {
            // Crear nuevo recibo
            $this->db->query(
                "INSERT INTO recibos (pago_id, numero_recibo, contenido) VALUES (?, ?, ?)",
                [$pagoId, $numeroRecibo, $contenido]
            );
            return $this->db->lastInsertId();
        }
    }
    
    /**
     * Generar contenido del recibo
     */
    private function generarContenidoRecibo($pago) {
        $fecha = date('d/m/Y H:i:s', strtotime($pago['fecha_pago']));
        
        $contenido = "═══════════════════════════════════════════════════════\n";
        $contenido .= "                    RECIBO DE PAGO\n";
        $contenido .= "═══════════════════════════════════════════════════════\n\n";
        
        $contenido .= "Número de Recibo: {$pago['numero_recibo']}\n";
        $contenido .= "Fecha: $fecha\n";
        $contenido .= "Sucursal: {$pago['sucursal_nombre']}\n";
        $contenido .= "Dirección: {$pago['sucursal_direccion']}\n\n";
        
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "DATOS DEL CLIENTE:\n";
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "Nombre: {$pago['cliente_nombre']} {$pago['cliente_apellido']}\n";
        $contenido .= "Cédula: {$pago['cliente_cedula']}\n";
        $contenido .= "Dirección: {$pago['cliente_direccion']}\n\n";
        
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "DATOS DEL PRÉSTAMO:\n";
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "Número de Préstamo: {$pago['numero_prestamo']}\n";
        if ($pago['numero_cuota']) {
            $contenido .= "Cuota Número: {$pago['numero_cuota']}\n";
            $contenido .= "Fecha de Vencimiento: " . date('d/m/Y', strtotime($pago['fecha_vencimiento'])) . "\n";
        }
        $contenido .= "\n";
        
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "DETALLE DEL PAGO:\n";
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "Capital:                    RD$ " . number_format($pago['capital'], 2) . "\n";
        $contenido .= "Interés:                    RD$ " . number_format($pago['interes'], 2) . "\n";
        if ($pago['mora'] > 0) {
            $contenido .= "Mora:                       RD$ " . number_format($pago['mora'], 2) . "\n";
        }
        $contenido .= "───────────────────────────────────────────────────────\n";
        $contenido .= "TOTAL PAGADO:                RD$ " . number_format($pago['monto'], 2) . "\n";
        $contenido .= "───────────────────────────────────────────────────────\n\n";
        
        $contenido .= "Método de Pago: {$pago['metodo_pago']}\n";
        if ($pago['numero_comprobante']) {
            $contenido .= "Número de Comprobante: {$pago['numero_comprobante']}\n";
        }
        $contenido .= "\n";
        
        $contenido .= "Recibido por: {$pago['usuario_nombre']} {$pago['usuario_apellido']}\n\n";
        
        $contenido .= "═══════════════════════════════════════════════════════\n";
        $contenido .= "          Gracias por su pago\n";
        $contenido .= "═══════════════════════════════════════════════════════\n";
        
        return $contenido;
    }
    
    /**
     * Generar PDF del recibo
     */
    public function generarPDF($reciboId) {
        $stmt = $this->db->query(
            "SELECT * FROM recibos WHERE id = ?",
            [$reciboId]
        );
        
        $recibo = $stmt->fetch();
        
        if (!$recibo) {
            throw new Exception('Recibo no encontrado');
        }
        
        // Marcar como impreso
        $this->db->query(
            "UPDATE recibos SET impreso = 1, fecha_impresion = NOW() WHERE id = ?",
            [$reciboId]
        );
        
        // En producción, usar TCPDF o FPDF para generar PDF real
        return $recibo['contenido'];
    }
}


