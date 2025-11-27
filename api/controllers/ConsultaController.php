<?php
/**
 * Controlador de Consultas
 */

require_once __DIR__ . '/../services/ConsultaService.php';

class ConsultaController {
    private $db;
    private $consultaService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->consultaService = new ConsultaService();
    }
    
    public function consultarCedula($cedula, $user) {
        if (!validateCedula($cedula)) {
            sendError('Cédula inválida', 400);
        }
        
        // Registrar consulta
        $consultaId = $this->registrarConsulta($cedula, $user['id'], 'jce');
        
        try {
            // Consultar JCE
            $resultado = $this->consultaService->consultarJCE($cedula);
            
            // Actualizar consulta como exitosa
            $this->db->query(
                "UPDATE consultas_cedulas 
                 SET estado = 'exitoso', resultado = ? 
                 WHERE id = ?",
                [json_encode($resultado), $consultaId]
            );
            
            sendResponse($resultado);
            
        } catch (Exception $e) {
            // Actualizar consulta como fallida
            $this->db->query(
                "UPDATE consultas_cedulas 
                 SET estado = 'fallido', resultado = ? 
                 WHERE id = ?",
                [json_encode(['error' => $e->getMessage()]), $consultaId]
            );
            
            sendError('Error al consultar cédula: ' . $e->getMessage(), 500);
        }
    }
    
    public function consultarDataCreditos($cedula, $user) {
        if (!validateCedula($cedula)) {
            sendError('Cédula inválida', 400);
        }
        
        // Verificar si hay data créditos en cache
        $stmt = $this->db->query(
            "SELECT * FROM data_creditos 
             WHERE cedula = ? 
             AND DATE(fecha_actualizacion) = CURDATE()",
            [$cedula]
        );
        
        $dataCreditos = $stmt->fetch();
        
        if ($dataCreditos) {
            sendResponse($dataCreditos);
            return;
        }
        
        // Registrar consulta
        $consultaId = $this->registrarConsulta($cedula, $user['id'], 'data_creditos');
        
        try {
            // Consultar Data Créditos
            $resultado = $this->consultaService->consultarDataCreditos($cedula);
            
            // Guardar o actualizar en cache
            $this->guardarDataCreditos($cedula, $resultado);
            
            // Actualizar consulta como exitosa
            $this->db->query(
                "UPDATE consultas_cedulas 
                 SET estado = 'exitoso', resultado = ? 
                 WHERE id = ?",
                [json_encode($resultado), $consultaId]
            );
            
            sendResponse($resultado);
            
        } catch (Exception $e) {
            // Actualizar consulta como fallida
            $this->db->query(
                "UPDATE consultas_cedulas 
                 SET estado = 'fallido', resultado = ? 
                 WHERE id = ?",
                [json_encode(['error' => $e->getMessage()]), $consultaId]
            );
            
            sendError('Error al consultar data créditos: ' . $e->getMessage(), 500);
        }
    }
    
    public function getHistorial($user, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // Filtros por rol
        $where = ["usuario_id = ?"];
        $params = [$user['id']];
        
        if (isset($_GET['cedula'])) {
            $where[] = "cedula = ?";
            $params[] = $_GET['cedula'];
        }
        
        if (isset($_GET['tipo_consulta'])) {
            $where[] = "tipo_consulta = ?";
            $params[] = $_GET['tipo_consulta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM consultas_cedulas WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener consultas
        $stmt = $this->db->query(
            "SELECT * FROM consultas_cedulas 
             WHERE $whereClause
             ORDER BY fecha_consulta DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $consultas = $stmt->fetchAll();
        
        // Decodificar resultados JSON
        foreach ($consultas as &$consulta) {
            if ($consulta['resultado']) {
                $consulta['resultado'] = json_decode($consulta['resultado'], true);
            }
        }
        
        sendPaginatedResponse($consultas, $total, $page, $perPage);
    }
    
    private function registrarConsulta($cedula, $userId, $tipo) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $this->db->query(
            "INSERT INTO consultas_cedulas (cedula, usuario_id, tipo_consulta, ip_address) 
             VALUES (?, ?, ?, ?)",
            [$cedula, $userId, $tipo, $ipAddress]
        );
        
        return $this->db->lastInsertId();
    }
    
    public function getStatus() {
        $dataCreditosConfigurado = !empty(DATA_CREDITOS_API_KEY) && DATA_CREDITOS_API_KEY !== '';
        $jceConfigurado = !empty(JCE_API_KEY) && JCE_API_KEY !== '';
        $dgiiConfigurado = !empty(DGII_API_KEY) && DGII_API_KEY !== '';
        
        sendResponse([
            'data_creditos' => [
                'disponible' => true,
                'configurado' => $dataCreditosConfigurado,
                'modo' => $dataCreditosConfigurado ? 'produccion' : 'prueba',
                'url' => DATA_CREDITOS_API_URL
            ],
            'jce' => [
                'disponible' => true,
                'configurado' => $jceConfigurado,
                'modo' => $jceConfigurado ? 'produccion' : 'prueba',
                'url' => JCE_API_URL
            ],
            'dgii' => [
                'disponible' => true,
                'configurado' => $dgiiConfigurado,
                'modo' => $dgiiConfigurado ? 'produccion' : 'prueba',
                'url' => DGII_API_URL
            ]
        ]);
    }
    
    private function guardarDataCreditos($cedula, $data) {
        // Buscar cliente por cédula
        $stmt = $this->db->query("SELECT id FROM clientes WHERE cedula = ?", [$cedula]);
        $cliente = $stmt->fetch();
        $clienteId = $cliente['id'] ?? null;
        
        // Verificar si existe
        $stmt = $this->db->query("SELECT id FROM data_creditos WHERE cedula = ?", [$cedula]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $this->db->query(
                "UPDATE data_creditos 
                 SET score = ?, 
                     deuda_total = ?,
                     cantidad_prestamos_activos = ?,
                     cantidad_prestamos_vencidos = ?,
                     ultimo_prestamo_fecha = ?,
                     historial_credito = ?,
                     fecha_actualizacion = NOW()
                 WHERE cedula = ?",
                [
                    $data['score'] ?? null,
                    $data['deuda_total'] ?? null,
                    $data['cantidad_prestamos_activos'] ?? 0,
                    $data['cantidad_prestamos_vencidos'] ?? 0,
                    $data['ultimo_prestamo_fecha'] ?? null,
                    json_encode($data['historial_credito'] ?? []),
                    $cedula
                ]
            );
        } else {
            $this->db->query(
                "INSERT INTO data_creditos (
                    cedula, cliente_id, score, deuda_total,
                    cantidad_prestamos_activos, cantidad_prestamos_vencidos,
                    ultimo_prestamo_fecha, historial_credito
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $cedula,
                    $clienteId,
                    $data['score'] ?? null,
                    $data['deuda_total'] ?? null,
                    $data['cantidad_prestamos_activos'] ?? 0,
                    $data['cantidad_prestamos_vencidos'] ?? 0,
                    $data['ultimo_prestamo_fecha'] ?? null,
                    json_encode($data['historial_credito'] ?? [])
                ]
            );
        }
    }
}

