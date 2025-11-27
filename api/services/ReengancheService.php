<?php
/**
 * Servicio de Reenganche (Refinanciamiento)
 */

require_once __DIR__ . '/PrestamoService.php';

class ReengancheService {
    private $db;
    private $prestamoService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->prestamoService = new PrestamoService();
    }
    
    /**
     * Procesar reenganche de un préstamo
     */
    public function procesarReenganche($prestamoOriginalId, $data, $user) {
        // Obtener préstamo original
        $stmt = $this->db->query(
            "SELECT * FROM prestamos WHERE id = ?",
            [$prestamoOriginalId]
        );
        
        $prestamoOriginal = $stmt->fetch();
        
        if (!$prestamoOriginal) {
            throw new Exception('Préstamo original no encontrado');
        }
        
        if ($prestamoOriginal['estado'] === 'pagado') {
            throw new Exception('No se puede reenganchar un préstamo ya pagado');
        }
        
        // Calcular saldo pendiente
        $saldoPendiente = $this->prestamoService->calcularSaldoPendiente($prestamoOriginalId);
        $moraTotal = $this->prestamoService->calcularMoraTotal($prestamoOriginalId);
        
        // Nuevo monto = saldo pendiente + mora + monto adicional (si hay)
        $montoAdicional = $data['monto_adicional'] ?? 0;
        $nuevoMonto = $saldoPendiente + $moraTotal + $montoAdicional;
        
        // Validar nuevo monto
        if ($nuevoMonto < MONTO_MINIMO_PRESTAMO || $nuevoMonto > MONTO_MAXIMO_PRESTAMO) {
            throw new Exception('El monto del reenganche está fuera de los límites permitidos');
        }
        
        // Obtener nueva tasa (puede ser diferente)
        $nuevaTasaId = $data['tasa_interes_id'] ?? $prestamoOriginal['tasa_interes_id'];
        $nuevoPlazo = $data['plazo_meses'] ?? $prestamoOriginal['plazo_meses'];
        
        // Validar plazo
        if ($nuevoPlazo < PLAZO_MINIMO_MESES || $nuevoPlazo > PLAZO_MAXIMO_MESES) {
            throw new Exception('El plazo está fuera de los límites permitidos');
        }
        
        // Obtener tasa
        $tasaStmt = $this->db->query(
            "SELECT * FROM tasas_interes WHERE id = ? AND activa = 1",
            [$nuevaTasaId]
        );
        $tasa = $tasaStmt->fetch();
        
        if (!$tasa) {
            throw new Exception('Tasa de interés no válida');
        }
        
        // Calcular nuevo préstamo
        $calculos = $this->prestamoService->calcularPrestamo($nuevoMonto, $tasa['tasa_mensual'], $nuevoPlazo);
        
        // Generar número de préstamo
        $numeroPrestamo = $this->generarNumeroPrestamo();
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Crear nuevo préstamo (reenganche)
            $this->db->query(
                "INSERT INTO prestamos (
                    numero_prestamo, cliente_id, sucursal_id, usuario_creador_id,
                    tasa_interes_id, monto_solicitado, monto_aprobado,
                    tasa_interes_mensual, plazo_meses, cuota_mensual,
                    monto_total_pagar, interes_total, estado,
                    reenganche_de, es_reenganche
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aprobado', ?, 1)",
                [
                    $numeroPrestamo,
                    $prestamoOriginal['cliente_id'],
                    $prestamoOriginal['sucursal_id'],
                    $user['id'],
                    $nuevaTasaId,
                    $nuevoMonto,
                    $nuevoMonto,
                    $tasa['tasa_mensual'],
                    $nuevoPlazo,
                    $calculos['cuota_mensual'],
                    $calculos['monto_total'],
                    $calculos['interes_total'],
                    $prestamoOriginalId
                ]
            );
            
            $nuevoPrestamoId = $this->db->lastInsertId();
            
            // Crear cuotas del nuevo préstamo
            $this->prestamoService->crearCuotas($nuevoPrestamoId, $calculos, $nuevoPlazo);
            
            // Cancelar préstamo original
            $this->db->query(
                "UPDATE prestamos SET estado = 'cancelado' WHERE id = ?",
                [$prestamoOriginalId]
            );
            
            // Marcar todas las cuotas del préstamo original como canceladas
            $this->db->query(
                "UPDATE cuotas_prestamos SET estado = 'cancelado' WHERE prestamo_id = ? AND estado != 'pagada'",
                [$prestamoOriginalId]
            );
            
            // Copiar garantes del préstamo original
            $garantesStmt = $this->db->query(
                "SELECT * FROM garantes WHERE prestamo_id = ?",
                [$prestamoOriginalId]
            );
            $garantes = $garantesStmt->fetchAll();
            
            foreach ($garantes as $garante) {
                $this->db->query(
                    "INSERT INTO garantes (
                        prestamo_id, cedula, nombre, apellido, fecha_nacimiento,
                        email, telefono, direccion, ciudad, provincia, ocupacion,
                        ingresos_mensuales, relacion_cliente
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $nuevoPrestamoId,
                        $garante['cedula'],
                        $garante['nombre'],
                        $garante['apellido'],
                        $garante['fecha_nacimiento'],
                        $garante['email'],
                        $garante['telefono'],
                        $garante['direccion'],
                        $garante['ciudad'],
                        $garante['provincia'],
                        $garante['ocupacion'],
                        $garante['ingresos_mensuales'],
                        $garante['relacion_cliente']
                    ]
                );
            }
            
            $this->db->getConnection()->commit();
            
            return [
                'prestamo_original_id' => $prestamoOriginalId,
                'nuevo_prestamo_id' => $nuevoPrestamoId,
                'numero_prestamo' => $numeroPrestamo,
                'saldo_anterior' => $saldoPendiente,
                'mora_anterior' => $moraTotal,
                'monto_adicional' => $montoAdicional,
                'nuevo_monto' => $nuevoMonto,
                'nueva_cuota' => $calculos['cuota_mensual']
            ];
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }
    }
    
    private function generarNumeroPrestamo() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM prestamos WHERE YEAR(fecha_creacion) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "PREST-$year-$numero";
    }
}


