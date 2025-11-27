<?php
/**
 * Controlador de Backups
 */

require_once __DIR__ . '/../utils/BackupService.php';

class BackupController {
    private $backupService;
    
    public function __construct() {
        $this->backupService = new BackupService();
    }
    
    /**
     * Crear backup
     */
    public function create($user) {
        // Solo admin puede crear backups
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden crear backups', 403);
            return;
        }
        
        try {
            $resultado = $this->backupService->crearBackup();
            sendResponse([
                'message' => 'Backup creado correctamente',
                'backup' => $resultado
            ]);
        } catch (Exception $e) {
            error_log("Error creando backup: " . $e->getMessage());
            sendError('Error al crear backup: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Listar backups
     */
    public function getAll($user) {
        // Solo admin puede ver backups
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden ver backups', 403);
            return;
        }
        
        $backups = $this->backupService->listarBackups();
        sendResponse($backups);
    }
    
    /**
     * Restaurar backup
     */
    public function restaurar($id, $user) {
        // Solo admin puede restaurar backups
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden restaurar backups', 403);
            return;
        }
        
        try {
            $stmt = Database::getInstance()->query(
                "SELECT nombre_archivo FROM backups WHERE id = ?",
                [$id]
            );
            
            $backup = $stmt->fetch();
            
            if (!$backup) {
                sendError('Backup no encontrado', 404);
                return;
            }
            
            $this->backupService->restaurarBackup($backup['nombre_archivo']);
            
            sendResponse(['message' => 'Backup restaurado correctamente']);
        } catch (Exception $e) {
            error_log("Error restaurando backup: " . $e->getMessage());
            sendError('Error al restaurar backup: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Eliminar backup
     */
    public function delete($id, $user) {
        // Solo admin puede eliminar backups
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden eliminar backups', 403);
            return;
        }
        
        try {
            $this->backupService->eliminarBackup($id);
            sendResponse(['message' => 'Backup eliminado correctamente']);
        } catch (Exception $e) {
            error_log("Error eliminando backup: " . $e->getMessage());
            sendError('Error al eliminar backup: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Limpiar backups antiguos
     */
    public function limpiar($user) {
        // Solo admin puede limpiar backups
        if ($user['rol'] !== 'admin') {
            sendError('Solo administradores pueden limpiar backups', 403);
            return;
        }
        
        try {
            $dias = $_GET['dias'] ?? 30;
            $eliminados = $this->backupService->limpiarBackupsAntiguos($dias);
            
            sendResponse([
                'message' => "Se eliminaron $eliminados backups antiguos",
                'eliminados' => $eliminados
            ]);
        } catch (Exception $e) {
            error_log("Error limpiando backups: " . $e->getMessage());
            sendError('Error al limpiar backups: ' . $e->getMessage(), 500);
        }
    }
}

