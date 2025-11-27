<?php
/**
 * Servicio de Generación de Contratos
 * Según leyes de República Dominicana
 */

class ContratoService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generar contrato según tipo y leyes de RD
     */
    public function generarContrato($prestamoId, $tipo = 'personal') {
        // Obtener datos del préstamo
        $stmt = $this->db->query(
            "SELECT p.*, 
                    c.*,
                    s.nombre as sucursal_nombre,
                    s.direccion as sucursal_direccion,
                    t.nombre as tasa_nombre,
                    t.tasa_mensual,
                    t.tasa_anual
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN sucursales s ON p.sucursal_id = s.id
             LEFT JOIN tasas_interes t ON p.tasa_interes_id = t.id
             WHERE p.id = ?",
            [$prestamoId]
        );
        
        $prestamo = $stmt->fetch();
        
        if (!$prestamo) {
            throw new Exception('Préstamo no encontrado');
        }
        
        // Obtener garantes si existen
        $garantesStmt = $this->db->query(
            "SELECT * FROM garantes WHERE prestamo_id = ?",
            [$prestamoId]
        );
        $garantes = $garantesStmt->fetchAll();
        
        // Generar número de contrato
        $numeroContrato = $this->generarNumeroContrato($prestamoId);
        
        // Generar contenido según tipo
        $contenido = $this->generarContenido($prestamo, $garantes, $tipo);
        
        // Guardar contrato
        $this->db->query(
            "INSERT INTO contratos (
                prestamo_id, numero_contrato, tipo_contrato, contenido
            ) VALUES (?, ?, ?, ?)",
            [$prestamoId, $numeroContrato, $tipo, $contenido]
        );
        
        $contratoId = $this->db->lastInsertId();
        
        return [
            'id' => $contratoId,
            'numero_contrato' => $numeroContrato,
            'contenido' => $contenido
        ];
    }
    
    /**
     * Generar contenido del contrato según leyes de RD
     */
    private function generarContenido($prestamo, $garantes, $tipo) {
        $fecha = date('d/m/Y');
        $fechaVencimiento = date('d/m/Y', strtotime($prestamo['fecha_vencimiento']));
        
        $contenido = "CONTRATO DE PRÉSTAMO " . strtoupper($tipo) . "\n\n";
        $contenido .= "Número de Contrato: {$prestamo['numero_prestamo']}\n";
        $contenido .= "Fecha: $fecha\n\n";
        
        $contenido .= "PARTES:\n\n";
        $contenido .= "PRESTAMISTA: {$prestamo['sucursal_nombre']}\n";
        $contenido .= "Dirección: {$prestamo['sucursal_direccion']}\n\n";
        
        $contenido .= "PRESTATARIO:\n";
        $contenido .= "Nombre: {$prestamo['nombre']} {$prestamo['apellido']}\n";
        $contenido .= "Cédula: {$prestamo['cedula']}\n";
        $contenido .= "Dirección: {$prestamo['direccion']}\n";
        $contenido .= "Teléfono: {$prestamo['telefono']}\n\n";
        
        if (!empty($garantes)) {
            $contenido .= "GARANTES:\n";
            foreach ($garantes as $index => $garante) {
                $contenido .= ($index + 1) . ". {$garante['nombre']} {$garante['apellido']}\n";
                $contenido .= "   Cédula: {$garante['cedula']}\n";
                $contenido .= "   Dirección: {$garante['direccion']}\n\n";
            }
        }
        
        $contenido .= "CLÁUSULAS:\n\n";
        
        $contenido .= "PRIMERA: El PRESTAMISTA otorga al PRESTATARIO un préstamo por la cantidad de ";
        $contenido .= number_format($prestamo['monto_aprobado'], 2) . " pesos dominicanos (RD$ " . number_format($prestamo['monto_aprobado'], 2) . ").\n\n";
        
        $contenido .= "SEGUNDA: El préstamo será pagado en {$prestamo['plazo_meses']} cuotas mensuales de ";
        $contenido .= number_format($prestamo['cuota_mensual'], 2) . " pesos dominicanos (RD$ " . number_format($prestamo['cuota_mensual'], 2) . ").\n\n";
        
        $contenido .= "TERCERA: La tasa de interés aplicable es del {$prestamo['tasa_interes_mensual']}% mensual ";
        $contenido .= "({$prestamo['tasa_anual']}% anual), conforme a las leyes de la República Dominicana.\n\n";
        
        $contenido .= "CUARTA: El plazo de pago es de {$prestamo['plazo_meses']} meses, ";
        $contenido .= "venciendo la primera cuota el día " . date('d', strtotime($prestamo['fecha_vencimiento'])) . " de cada mes.\n\n";
        
        $contenido .= "QUINTA: En caso de mora, se aplicará una tasa de mora del 0.1% diario sobre el monto adeudado, ";
        $contenido .= "después de {$prestamo['dias_gracia']} días de gracia, conforme al Código Civil Dominicano.\n\n";
        
        if ($tipo === 'fiador' && !empty($garantes)) {
            $contenido .= "SEXTA: Los GARANTES se obligan solidariamente con el PRESTATARIO al pago de todas las obligaciones ";
            $contenido .= "derivadas de este contrato, conforme al artículo 2281 del Código Civil Dominicano.\n\n";
        }
        
        $contenido .= "SÉPTIMA: El PRESTATARIO se compromete a pagar puntualmente todas las cuotas en las fechas establecidas. ";
        $contenido .= "El incumplimiento dará lugar a la ejecución inmediata de las garantías.\n\n";
        
        $contenido .= "OCTAVA: Este contrato se rige por las leyes de la República Dominicana y cualquier controversia ";
        $contenido .= "será resuelta en los tribunales competentes de esta jurisdicción.\n\n";
        
        $contenido .= "NOVENA: El PRESTATARIO declara que la información proporcionada es veraz y autoriza la consulta ";
        $contenido .= "de su historial crediticio en las centrales de riesgo autorizadas.\n\n";
        
        $contenido .= "DÉCIMA: Este contrato entra en vigor desde la fecha de su firma y permanecerá vigente hasta ";
        $contenido .= "el pago completo de todas las obligaciones.\n\n";
        
        $contenido .= "Firmado en Santo Domingo, República Dominicana, el día $fecha.\n\n";
        $contenido .= "___________________________        ___________________________\n";
        $contenido .= "PRESTAMISTA                        PRESTATARIO\n\n";
        
        if (!empty($garantes)) {
            foreach ($garantes as $index => $garante) {
                $contenido .= "___________________________\n";
                $contenido .= "GARANTE " . ($index + 1) . ": {$garante['nombre']} {$garante['apellido']}\n\n";
            }
        }
        
        $contenido .= "\n\n";
        $contenido .= "NOTA: Este contrato cumple con las disposiciones de la Ley 253-12 sobre Préstamos de Consumo ";
        $contenido .= "y el Código Civil Dominicano.\n";
        
        return $contenido;
    }
    
    private function generarNumeroContrato($prestamoId) {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM contratos WHERE YEAR(fecha_creacion) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "CONT-$year-$numero";
    }
    
    /**
     * Generar PDF del contrato (estructura lista para librería)
     */
    public function generarPDF($contratoId) {
        $stmt = $this->db->query(
            "SELECT * FROM contratos WHERE id = ?",
            [$contratoId]
        );
        
        $contrato = $stmt->fetch();
        
        if (!$contrato) {
            throw new Exception('Contrato no encontrado');
        }
        
        // En producción, usar TCPDF o FPDF para generar PDF real
        // Por ahora retornamos el contenido como texto
        return $contrato['contenido'];
    }
}


