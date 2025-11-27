<?php
/**
 * Controlador de Cooperativas
 */

require_once __DIR__ . '/../services/CooperativaService.php';

class CooperativaController {
    private $db;
    private $cooperativaService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cooperativaService = new CooperativaService();
    }
    
    /**
     * Obtener todas las cooperativas
     */
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['activa'])) {
            $where[] = "activa = ?";
            $params[] = $filters['activa'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT * FROM cooperativas 
             WHERE $whereClause
             ORDER BY nombre",
            $params
        );
        
        sendResponse($stmt->fetchAll());
    }
    
    /**
     * Obtener cooperativa por ID
     */
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM cooperativas WHERE id = ?",
            [$id]
        );
        
        $cooperativa = $stmt->fetch();
        
        if (!$cooperativa) {
            sendError('Cooperativa no encontrada', 404);
            return;
        }
        
        // Obtener estadísticas
        $cooperativa['total_socios'] = $this->contarSocios($id);
        $cooperativa['total_apartaciones'] = $this->obtenerTotalApartaciones($id);
        
        sendResponse($cooperativa);
    }
    
    /**
     * Crear cooperativa
     */
    public function create($data) {
        $errors = [];
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
            return;
        }
        
        $this->db->query(
            "INSERT INTO cooperativas 
             (nombre, rnc, direccion, telefono, email, fecha_constitucion, activa)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['nombre'],
                $data['rnc'] ?? null,
                $data['direccion'] ?? null,
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['fecha_constitucion'] ?? null,
                $data['activa'] ?? 1
            ]
        );
        
        $id = $this->db->lastInsertId();
        $this->getById($id);
    }
    
    /**
     * Actualizar cooperativa
     */
    public function update($id, $data) {
        $this->db->query(
            "UPDATE cooperativas SET
             nombre = ?,
             rnc = ?,
             direccion = ?,
             telefono = ?,
             email = ?,
             fecha_constitucion = ?,
             activa = ?
             WHERE id = ?",
            [
                $data['nombre'] ?? null,
                $data['rnc'] ?? null,
                $data['direccion'] ?? null,
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['fecha_constitucion'] ?? null,
                $data['activa'] ?? 1,
                $id
            ]
        );
        
        $this->getById($id);
    }
    
    /**
     * Obtener socios de una cooperativa
     */
    public function getSocios($cooperativaId, $filters = []) {
        $where = ["s.cooperativa_id = ?"];
        $params = [$cooperativaId];
        
        if (isset($filters['activo'])) {
            $where[] = "s.activo = ?";
            $params[] = $filters['activo'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT s.*, 
                    COALESCE(r.total_apartaciones, 0) as total_apartaciones,
                    r.ultima_apartacion
             FROM socios s
             LEFT JOIN socios_apartaciones_resumen r ON s.id = r.socio_id
             WHERE $whereClause
             ORDER BY s.nombre, s.apellido",
            $params
        );
        
        sendResponse($stmt->fetchAll());
    }
    
    /**
     * Agregar socio
     */
    public function agregarSocio($cooperativaId, $data) {
        $errors = [];
        
        if (!isset($data['cedula']) || empty($data['cedula'])) {
            $errors[] = 'Cédula es requerida';
        }
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
            return;
        }
        
        // Verificar que no exista
        $stmt = $this->db->query(
            "SELECT id FROM socios 
             WHERE cooperativa_id = ? AND cedula = ?",
            [$cooperativaId, $data['cedula']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe un socio con esta cédula en esta cooperativa', 400);
            return;
        }
        
        $this->db->query(
            "INSERT INTO socios 
             (cooperativa_id, cliente_id, cedula, nombre, apellido, telefono, email, 
              direccion, fecha_ingreso, activo, porcentaje_utilidad, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $cooperativaId,
                $data['cliente_id'] ?? null,
                $data['cedula'],
                $data['nombre'],
                $data['apellido'] ?? '',
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['direccion'] ?? null,
                $data['fecha_ingreso'] ?? date('Y-m-d'),
                $data['activo'] ?? 1,
                $data['porcentaje_utilidad'] ?? null,
                $data['observaciones'] ?? null
            ]
        );
        
        $socioId = $this->db->lastInsertId();
        $this->getSocioById($socioId);
    }
    
    /**
     * Obtener socio por ID
     */
    public function getSocioById($socioId) {
        $stmt = $this->db->query(
            "SELECT s.*, 
                    COALESCE(r.total_apartaciones, 0) as total_apartaciones,
                    r.ultima_apartacion
             FROM socios s
             LEFT JOIN socios_apartaciones_resumen r ON s.id = r.socio_id
             WHERE s.id = ?",
            [$socioId]
        );
        
        $socio = $stmt->fetch();
        
        if (!$socio) {
            sendError('Socio no encontrado', 404);
            return;
        }
        
        sendResponse($socio);
    }
    
    /**
     * Registrar apartación
     */
    public function registrarApartacion($cooperativaId, $data) {
        $errors = [];
        
        if (!isset($data['socio_id']) || !$data['socio_id']) {
            $errors[] = 'Socio es requerido';
        }
        
        if (!isset($data['monto']) || $data['monto'] <= 0) {
            $errors[] = 'Monto inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
            return;
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Insertar apartación
            $this->db->query(
                "INSERT INTO apartaciones 
                 (socio_id, cooperativa_id, fecha_apartacion, monto, tipo_apartacion, 
                  metodo_pago, numero_comprobante, observaciones, registrado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['socio_id'],
                    $cooperativaId,
                    $data['fecha_apartacion'] ?? date('Y-m-d'),
                    $data['monto'],
                    $data['tipo_apartacion'] ?? 'adicional',
                    $data['metodo_pago'] ?? null,
                    $data['numero_comprobante'] ?? null,
                    $data['observaciones'] ?? null,
                    $data['registrado_por'] ?? null
                ]
            );
            
            // Actualizar resumen
            $this->cooperativaService->actualizarResumenApartaciones($data['socio_id']);
            
            $this->db->getConnection()->commit();
            
            sendResponse([
                'success' => true,
                'message' => 'Apartación registrada correctamente'
            ]);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al registrar apartación: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener apartaciones de un socio
     */
    public function getApartaciones($socioId, $filters = []) {
        $where = ["a.socio_id = ?"];
        $params = [$socioId];
        
        if (isset($filters['periodo'])) {
            $where[] = "DATE_FORMAT(a.fecha_apartacion, '%Y-%m') = ?";
            $params[] = $filters['periodo'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT a.*, s.nombre as socio_nombre, s.apellido as socio_apellido
             FROM apartaciones a
             LEFT JOIN socios s ON a.socio_id = s.id
             WHERE $whereClause
             ORDER BY a.fecha_apartacion DESC",
            $params
        );
        
        sendResponse($stmt->fetchAll());
    }
    
    /**
     * Calcular y crear distribución de utilidades
     */
    public function calcularDistribucionUtilidades($cooperativaId, $data) {
        $errors = [];
        
        if (!isset($data['periodo']) || empty($data['periodo'])) {
            $errors[] = 'Período es requerido';
        }
        
        if (!isset($data['monto_total_utilidad']) || $data['monto_total_utilidad'] <= 0) {
            $errors[] = 'Monto total de utilidad inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
            return;
        }
        
        $metodo = $data['metodo_distribucion'] ?? 'por_apartaciones';
        $periodo = $data['periodo'];
        $montoTotal = $data['monto_total_utilidad'];
        
        // Calcular distribución
        $distribuciones = $this->cooperativaService->calcularDistribucionUtilidades(
            $cooperativaId,
            $periodo,
            $montoTotal,
            $metodo
        );
        
        // Crear registro de distribución
        $this->db->getConnection()->beginTransaction();
        
        try {
            $anio = substr($periodo, 0, 4);
            $mes = substr($periodo, 5, 2);
            
            $this->db->query(
                "INSERT INTO distribucion_utilidades 
                 (cooperativa_id, periodo, anio, mes, monto_total_utilidad, metodo_distribucion, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 'calculado')
                 ON DUPLICATE KEY UPDATE
                 monto_total_utilidad = VALUES(monto_total_utilidad),
                 metodo_distribucion = VALUES(metodo_distribucion),
                 estado = 'calculado'",
                [$cooperativaId, $periodo, $anio, $mes, $montoTotal, $metodo]
            );
            
            $distribucionId = $this->db->lastInsertId();
            
            if (!$distribucionId) {
                // Si ya existe, obtener el ID
                $stmt = $this->db->query(
                    "SELECT id FROM distribucion_utilidades 
                     WHERE cooperativa_id = ? AND periodo = ?",
                    [$cooperativaId, $periodo]
                );
                $dist = $stmt->fetch();
                $distribucionId = $dist['id'];
            }
            
            // Eliminar detalles anteriores
            $this->db->query(
                "DELETE FROM distribucion_utilidades_detalle WHERE distribucion_id = ?",
                [$distribucionId]
            );
            
            // Insertar detalles
            foreach ($distribuciones as $dist) {
                $this->db->query(
                    "INSERT INTO distribucion_utilidades_detalle
                     (distribucion_id, socio_id, monto_utilidad, porcentaje_asignado, 
                      monto_apartaciones_periodo, metodo_calculo)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $distribucionId,
                        $dist['socio_id'],
                        $dist['monto_utilidad'],
                        $dist['porcentaje_asignado'],
                        $dist['monto_apartaciones_periodo'],
                        $dist['metodo_calculo']
                    ]
                );
            }
            
            $this->db->getConnection()->commit();
            
            $this->getDistribucionById($distribucionId);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            sendError('Error al calcular distribución: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener distribución por ID
     */
    public function getDistribucionById($distribucionId) {
        $stmt = $this->db->query(
            "SELECT * FROM distribucion_utilidades WHERE id = ?",
            [$distribucionId]
        );
        
        $distribucion = $stmt->fetch();
        
        if (!$distribucion) {
            sendError('Distribución no encontrada', 404);
            return;
        }
        
        // Obtener detalles
        $stmt = $this->db->query(
            "SELECT d.*, s.nombre as socio_nombre, s.apellido as socio_apellido, s.cedula
             FROM distribucion_utilidades_detalle d
             LEFT JOIN socios s ON d.socio_id = s.id
             WHERE d.distribucion_id = ?
             ORDER BY d.monto_utilidad DESC",
            [$distribucionId]
        );
        
        $distribucion['detalles'] = $stmt->fetchAll();
        
        sendResponse($distribucion);
    }
    
    /**
     * Obtener distribuciones de una cooperativa
     */
    public function getDistribuciones($cooperativaId, $filters = []) {
        $where = ["cooperativa_id = ?"];
        $params = [$cooperativaId];
        
        if (isset($filters['periodo'])) {
            $where[] = "periodo = ?";
            $params[] = $filters['periodo'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "estado = ?";
            $params[] = $filters['estado'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT * FROM distribucion_utilidades 
             WHERE $whereClause
             ORDER BY periodo DESC",
            $params
        );
        
        sendResponse($stmt->fetchAll());
    }
    
    /**
     * Aprobar distribución
     */
    public function aprobarDistribucion($distribucionId, $userId) {
        $this->db->query(
            "UPDATE distribucion_utilidades SET
             estado = 'aprobado',
             fecha_aprobacion = NOW(),
             aprobado_por = ?
             WHERE id = ? AND estado = 'calculado'",
            [$userId, $distribucionId]
        );
        
        $this->getDistribucionById($distribucionId);
    }
    
    /**
     * Marcar pago de utilidad a socio
     */
    public function marcarPagoUtilidad($detalleId, $data) {
        $this->db->query(
            "UPDATE distribucion_utilidades_detalle SET
             pagado = 1,
             fecha_pago = ?,
             comprobante_pago = ?,
             observaciones = ?
             WHERE id = ?",
            [
                $data['fecha_pago'] ?? date('Y-m-d'),
                $data['comprobante_pago'] ?? null,
                $data['observaciones'] ?? null,
                $detalleId
            ]
        );
        
        // Verificar si todas las utilidades están pagadas
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total, SUM(CASE WHEN pagado = 1 THEN 1 ELSE 0 END) as pagados
             FROM distribucion_utilidades_detalle
             WHERE distribucion_id = (SELECT distribucion_id FROM distribucion_utilidades_detalle WHERE id = ?)",
            [$detalleId]
        );
        
        $result = $stmt->fetch();
        
        if ($result['total'] == $result['pagados']) {
            // Actualizar estado de distribución a distribuido
            $this->db->query(
                "UPDATE distribucion_utilidades SET estado = 'distribuido'
                 WHERE id = (SELECT distribucion_id FROM distribucion_utilidades_detalle WHERE id = ?)",
                [$detalleId]
            );
        }
        
        sendResponse(['success' => true, 'message' => 'Pago marcado correctamente']);
    }
    
    // Métodos auxiliares
    private function contarSocios($cooperativaId) {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM socios WHERE cooperativa_id = ? AND activo = 1",
            [$cooperativaId]
        );
        $result = $stmt->fetch();
        return intval($result['total'] ?? 0);
    }
    
    private function obtenerTotalApartaciones($cooperativaId) {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(monto), 0) as total
             FROM apartaciones
             WHERE cooperativa_id = ?",
            [$cooperativaId]
        );
        $result = $stmt->fetch();
        return floatval($result['total'] ?? 0);
    }
}

