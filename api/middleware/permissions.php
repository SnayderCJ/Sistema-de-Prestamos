<?php
/**
 * Middleware de Permisos Granulares
 */

class PermissionsMiddleware {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Verificar si el usuario tiene permiso para una acción específica
     */
    public function checkPermission($user, $modulo, $accion) {
        // Admin tiene todos los permisos
        if ($user['rol'] === 'admin') {
            return true;
        }
        
        // Obtener permisos del usuario
        $permisos = $this->obtenerPermisos($user['id'], $user['rol']);
        
        // Verificar permiso específico
        $clavePermiso = "$modulo.$accion";
        
        if (isset($permisos[$clavePermiso])) {
            return $permisos[$clavePermiso] === true;
        }
        
        // Verificar permiso del módulo completo
        if (isset($permisos[$modulo]) && $permisos[$modulo] === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener todos los permisos de un usuario
     */
    private function obtenerPermisos($usuarioId, $rol) {
        // Primero obtener permisos personalizados del usuario
        $stmt = $this->db->query(
            "SELECT modulo, accion, permitido 
             FROM permisos_usuarios 
             WHERE usuario_id = ?",
            [$usuarioId]
        );
        
        $permisosPersonalizados = $stmt->fetchAll();
        
        // Luego obtener permisos por defecto del rol
        $stmt = $this->db->query(
            "SELECT modulo, accion, permitido 
             FROM permisos_roles 
             WHERE rol = ?",
            [$rol]
        );
        
        $permisosRol = $stmt->fetchAll();
        
        // Combinar permisos (los personalizados tienen prioridad)
        $permisos = [];
        
        // Primero aplicar permisos del rol
        foreach ($permisosRol as $permiso) {
            $clave = $permiso['modulo'] . '.' . $permiso['accion'];
            $permisos[$clave] = (bool)$permiso['permitido'];
        }
        
        // Luego sobrescribir con permisos personalizados
        foreach ($permisosPersonalizados as $permiso) {
            $clave = $permiso['modulo'] . '.' . $permiso['accion'];
            $permisos[$clave] = (bool)$permiso['permitido'];
        }
        
        return $permisos;
    }
    
    /**
     * Requerir permiso específico (lanzar error si no tiene)
     */
    public function requirePermission($user, $modulo, $accion) {
        if (!$this->checkPermission($user, $modulo, $accion)) {
            sendError("No tiene permiso para realizar esta acción: $modulo.$accion", 403);
            exit;
        }
    }
    
    /**
     * Obtener módulos y acciones disponibles
     */
    public function obtenerModulosDisponibles() {
        return [
            'prestamos' => ['crear', 'editar', 'eliminar', 'aprobar', 'ver'],
            'clientes' => ['crear', 'editar', 'eliminar', 'ver'],
            'pagos' => ['crear', 'editar', 'eliminar', 'ver'],
            'rutas' => ['crear', 'editar', 'eliminar', 'ver', 'asignar'],
            'caja' => ['abrir', 'cerrar', 'ver'],
            'desembolsos' => ['crear', 'editar', 'eliminar', 'ver'],
            'reportes' => ['ver', 'exportar'],
            'usuarios' => ['crear', 'editar', 'eliminar', 'ver'],
            'configuracion' => ['editar', 'ver']
        ];
    }
}

