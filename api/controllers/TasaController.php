<?php
/**
 * Controlador de Tasas de Interés
 */

class TasaController {
    private $db;
    private $tasaService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->tasaService = new TasaService();
    }
    
    public function getAll() {
        $tasas = $this->tasaService->obtenerTasasActivas();
        sendResponse($tasas);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM tasas_interes WHERE id = ?",
            [$id]
        );
        
        $tasa = $stmt->fetch();
        
        if (!$tasa) {
            sendError('Tasa no encontrada', 404);
        }
        
        sendResponse($tasa);
    }
    
    public function getTasaAplicable($monto, $plazo) {
        $tasa = $this->tasaService->obtenerTasaAplicable($monto, $plazo);
        
        if (!$tasa) {
            sendError('No hay tasa aplicable para los parámetros especificados', 404);
        }
        
        sendResponse($tasa);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['codigo']) || empty($data['codigo'])) {
            $errors[] = 'Código es requerido';
        }
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!isset($data['tasa_mensual']) || !is_numeric($data['tasa_mensual'])) {
            $errors[] = 'Tasa mensual es requerida';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Calcular tasa anual
        $tasaAnual = $data['tasa_mensual'] * 12;
        
        $this->db->query(
            "INSERT INTO tasas_interes (
                codigo, nombre, tipo_tasa, tasa_mensual, tasa_anual,
                monto_minimo, monto_maximo, plazo_minimo, plazo_maximo,
                activa, fecha_inicio, fecha_fin, descripcion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['codigo'],
                $data['nombre'],
                $data['tipo_tasa'] ?? 'fija',
                $data['tasa_mensual'],
                $tasaAnual,
                $data['monto_minimo'] ?? null,
                $data['monto_maximo'] ?? null,
                $data['plazo_minimo'] ?? null,
                $data['plazo_maximo'] ?? null,
                $data['activa'] ?? 1,
                $data['fecha_inicio'] ?? date('Y-m-d'),
                $data['fecha_fin'] ?? null,
                $data['descripcion'] ?? null
            ]
        );
        
        $tasaId = $this->db->lastInsertId();
        $this->getById($tasaId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'nombre', 'tipo_tasa', 'tasa_mensual', 'monto_minimo', 
            'monto_maximo', 'plazo_minimo', 'plazo_maximo', 
            'activa', 'fecha_inicio', 'fecha_fin', 'descripcion'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'tasa_mensual') {
                    $updates[] = "tasa_mensual = ?";
                    $updates[] = "tasa_anual = ?";
                    $params[] = $data[$field];
                    $params[] = $data[$field] * 12;
                } else {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            sendError('No hay campos para actualizar', 400);
        }
        
        $params[] = $id;
        $this->db->query(
            "UPDATE tasas_interes SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
    
    public function delete($id) {
        // No eliminar, solo desactivar
        $this->db->query(
            "UPDATE tasas_interes SET activa = 0 WHERE id = ?",
            [$id]
        );
        
        sendResponse(['message' => 'Tasa desactivada correctamente']);
    }
}


