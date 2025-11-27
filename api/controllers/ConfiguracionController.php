<?php
/**
 * Controlador de Configuración del Sistema
 */

class ConfiguracionController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las configuraciones
     */
    public function getAll($user) {
        // Solo admin puede ver configuración
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden ver la configuración', 403);
            return;
        }
        
        $stmt = $this->db->query(
            "SELECT * FROM configuracion_sistema ORDER BY clave"
        );
        
        $configs = $stmt->fetchAll();
        sendResponse($configs);
    }
    
    /**
     * Obtener configuración específica
     */
    public function getByClave($clave, $user) {
        // Solo admin puede ver configuración
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden ver la configuración', 403);
            return;
        }
        
        $stmt = $this->db->query(
            "SELECT * FROM configuracion_sistema WHERE clave = ?",
            [$clave]
        );
        
        $config = $stmt->fetch();
        
        if (!$config) {
            sendError('Configuración no encontrada', 404);
            return;
        }
        
        sendResponse($config);
    }
    
    /**
     * Actualizar configuraciones
     */
    public function update($data, $user) {
        // Solo admin puede actualizar configuración
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden actualizar la configuración', 403);
            return;
        }
        
        if (!isset($data['configuraciones']) || !is_array($data['configuraciones'])) {
            sendError('Configuraciones requeridas', 400);
            return;
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            foreach ($data['configuraciones'] as $config) {
                if (!isset($config['clave']) || !isset($config['valor'])) {
                    continue;
                }
                
                $this->db->query(
                    "UPDATE configuracion_sistema 
                     SET valor = ?, fecha_actualizacion = NOW()
                     WHERE clave = ?",
                    [$config['valor'], $config['clave']]
                );
            }
            
            $this->db->getConnection()->commit();
            
            sendResponse(['message' => 'Configuración actualizada correctamente']);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error actualizando configuración: " . $e->getMessage());
            sendError('Error al actualizar configuración', 500);
        }
    }
    
    /**
     * Crear nueva configuración
     */
    public function create($data, $user) {
        // Solo admin puede crear configuración
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden crear configuraciones', 403);
            return;
        }
        
        if (!isset($data['clave']) || !isset($data['valor'])) {
            sendError('Clave y valor son requeridos', 400);
            return;
        }
        
        $this->db->query(
            "INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = ?, fecha_actualizacion = NOW()",
            [
                $data['clave'],
                $data['valor'],
                $data['tipo'] ?? 'string',
                $data['descripcion'] ?? '',
                $data['valor']
            ]
        );
        
        $this->getByClave($data['clave'], $user);
    }
}

