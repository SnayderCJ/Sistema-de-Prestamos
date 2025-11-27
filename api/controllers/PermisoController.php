<?php
/**
 * Controlador de Permisos
 */

require_once __DIR__ . '/../middleware/permissions.php';

class PermisoController {
    private $db;
    private $permissionsMiddleware;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->permissionsMiddleware = new PermissionsMiddleware();
    }
    
    /**
     * Obtener permisos del usuario actual
     */
    public function obtenerPermisos($user) {
        $permisos = $this->permissionsMiddleware->obtenerPermisos($user['id'], $user['rol']);
        $modulos = $this->permissionsMiddleware->obtenerModulosDisponibles();
        
        sendResponse([
            'permisos' => $permisos,
            'modulos' => $modulos,
            'rol' => $user['rol']
        ]);
    }
    
    /**
     * Obtener permisos de un usuario específico (solo admin)
     */
    public function obtenerPermisosUsuario($usuarioId, $user) {
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden ver permisos de otros usuarios', 403);
            return;
        }
        
        $stmt = $this->db->query(
            "SELECT rol FROM usuarios WHERE id = ?",
            [$usuarioId]
        );
        
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            sendError('Usuario no encontrado', 404);
            return;
        }
        
        $permisos = $this->permissionsMiddleware->obtenerPermisos($usuarioId, $usuario['rol']);
        
        sendResponse([
            'usuario_id' => $usuarioId,
            'rol' => $usuario['rol'],
            'permisos' => $permisos
        ]);
    }
    
    /**
     * Actualizar permisos de un usuario (solo admin)
     */
    public function actualizarPermisos($usuarioId, $data, $user) {
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden actualizar permisos', 403);
            return;
        }
        
        if (!isset($data['permisos']) || !is_array($data['permisos'])) {
            sendError('Permisos requeridos', 400);
            return;
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Eliminar permisos existentes del usuario
            $this->db->query(
                "DELETE FROM permisos_usuarios WHERE usuario_id = ?",
                [$usuarioId]
            );
            
            // Insertar nuevos permisos
            foreach ($data['permisos'] as $modulo => $acciones) {
                if (is_array($acciones)) {
                    foreach ($acciones as $accion => $permitido) {
                        $this->db->query(
                            "INSERT INTO permisos_usuarios (usuario_id, modulo, accion, permitido)
                             VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE permitido = ?",
                            [$usuarioId, $modulo, $accion, $permitido ? 1 : 0, $permitido ? 1 : 0]
                        );
                    }
                } else {
                    // Si es un booleano, aplicar a todas las acciones del módulo
                    $modulos = $this->permissionsMiddleware->obtenerModulosDisponibles();
                    if (isset($modulos[$modulo])) {
                        foreach ($modulos[$modulo] as $accion) {
                            $this->db->query(
                                "INSERT INTO permisos_usuarios (usuario_id, modulo, accion, permitido)
                                 VALUES (?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE permitido = ?",
                                [$usuarioId, $modulo, $accion, $acciones ? 1 : 0, $acciones ? 1 : 0]
                            );
                        }
                    }
                }
            }
            
            $this->db->getConnection()->commit();
            
            sendResponse(['message' => 'Permisos actualizados correctamente']);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error actualizando permisos: " . $e->getMessage());
            sendError('Error al actualizar permisos', 500);
        }
    }
    
    /**
     * Obtener permisos por defecto de un rol
     */
    public function obtenerPermisosRol($rol) {
        $stmt = $this->db->query(
            "SELECT modulo, accion, permitido 
             FROM permisos_roles 
             WHERE rol = ?",
            [$rol]
        );
        
        $permisos = $stmt->fetchAll();
        
        $resultado = [];
        foreach ($permisos as $permiso) {
            $clave = $permiso['modulo'] . '.' . $permiso['accion'];
            $resultado[$clave] = (bool)$permiso['permitido'];
        }
        
        sendResponse([
            'rol' => $rol,
            'permisos' => $resultado
        ]);
    }
    
    /**
     * Actualizar permisos por defecto de un rol (solo admin)
     */
    public function actualizarPermisosRol($rol, $data, $user) {
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden actualizar permisos de roles', 403);
            return;
        }
        
        if (!isset($data['permisos']) || !is_array($data['permisos'])) {
            sendError('Permisos requeridos', 400);
            return;
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Eliminar permisos existentes del rol
            $this->db->query(
                "DELETE FROM permisos_roles WHERE rol = ?",
                [$rol]
            );
            
            // Insertar nuevos permisos
            foreach ($data['permisos'] as $modulo => $acciones) {
                if (is_array($acciones)) {
                    foreach ($acciones as $accion => $permitido) {
                        $this->db->query(
                            "INSERT INTO permisos_roles (rol, modulo, accion, permitido)
                             VALUES (?, ?, ?, ?)",
                            [$rol, $modulo, $accion, $permitido ? 1 : 0]
                        );
                    }
                }
            }
            
            $this->db->getConnection()->commit();
            
            sendResponse(['message' => 'Permisos del rol actualizados correctamente']);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error actualizando permisos de rol: " . $e->getMessage());
            sendError('Error al actualizar permisos del rol', 500);
        }
    }
}

