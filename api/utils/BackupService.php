<?php
/**
 * Servicio de Backup de Base de Datos
 */

class BackupService {
    private $db;
    private $backupDir;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->backupDir = __DIR__ . '/../../backups/';
        
        // Crear directorio si no existe
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Crear backup completo de la base de datos
     */
    public function crearBackup($nombreArchivo = null) {
        if (!$nombreArchivo) {
            $nombreArchivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $rutaArchivo = $this->backupDir . $nombreArchivo;
        
        // Obtener configuración de BD
        $config = require __DIR__ . '/../config/database.php';
        $host = $config['host'];
        $dbname = $config['dbname'];
        $username = $config['username'];
        $password = $config['password'];
        
        // Comando mysqldump
        $comando = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($dbname),
            escapeshellarg($rutaArchivo)
        );
        
        exec($comando, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Error creando backup: " . implode("\n", $output));
        }
        
        // Comprimir backup
        $rutaComprimido = $rutaArchivo . '.gz';
        $this->comprimirArchivo($rutaArchivo, $rutaComprimido);
        
        // Eliminar archivo sin comprimir
        unlink($rutaArchivo);
        
        // Registrar en base de datos
        $this->db->query(
            "INSERT INTO backups (nombre_archivo, ruta, tamano, fecha_creacion)
             VALUES (?, ?, ?, NOW())",
            [$nombreArchivo . '.gz', $rutaComprimido, filesize($rutaComprimido)]
        );
        
        return [
            'archivo' => $nombreArchivo . '.gz',
            'ruta' => $rutaComprimido,
            'tamano' => filesize($rutaComprimido),
            'fecha' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Restaurar backup
     */
    public function restaurarBackup($nombreArchivo) {
        $rutaArchivo = $this->backupDir . $nombreArchivo;
        
        if (!file_exists($rutaArchivo)) {
            throw new Exception("Archivo de backup no encontrado");
        }
        
        // Descomprimir si es necesario
        if (pathinfo($rutaArchivo, PATHINFO_EXTENSION) === 'gz') {
            $rutaDescomprimido = str_replace('.gz', '', $rutaArchivo);
            $this->descomprimirArchivo($rutaArchivo, $rutaDescomprimido);
            $rutaArchivo = $rutaDescomprimido;
        }
        
        // Obtener configuración de BD
        $config = require __DIR__ . '/../config/database.php';
        $host = $config['host'];
        $dbname = $config['dbname'];
        $username = $config['username'];
        $password = $config['password'];
        
        // Comando mysql para restaurar
        $comando = sprintf(
            'mysql -h %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($dbname),
            escapeshellarg($rutaArchivo)
        );
        
        exec($comando, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Error restaurando backup: " . implode("\n", $output));
        }
        
        // Limpiar archivo descomprimido si se creó
        if (isset($rutaDescomprimido) && file_exists($rutaDescomprimido)) {
            unlink($rutaDescomprimido);
        }
        
        return true;
    }
    
    /**
     * Listar backups disponibles
     */
    public function listarBackups() {
        $stmt = $this->db->query(
            "SELECT * FROM backups ORDER BY fecha_creacion DESC"
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Eliminar backup
     */
    public function eliminarBackup($id) {
        $stmt = $this->db->query(
            "SELECT ruta FROM backups WHERE id = ?",
            [$id]
        );
        
        $backup = $stmt->fetch();
        
        if (!$backup) {
            throw new Exception("Backup no encontrado");
        }
        
        // Eliminar archivo
        if (file_exists($backup['ruta'])) {
            unlink($backup['ruta']);
        }
        
        // Eliminar registro
        $this->db->query("DELETE FROM backups WHERE id = ?", [$id]);
        
        return true;
    }
    
    /**
     * Comprimir archivo
     */
    private function comprimirArchivo($archivo, $archivoComprimido) {
        $fp_in = fopen($archivo, 'rb');
        $fp_out = gzopen($archivoComprimido, 'wb9');
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 8192));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
    }
    
    /**
     * Descomprimir archivo
     */
    private function descomprimirArchivo($archivoComprimido, $archivo) {
        $fp_in = gzopen($archivoComprimido, 'rb');
        $fp_out = fopen($archivo, 'wb');
        
        while (!gzeof($fp_in)) {
            fwrite($fp_out, gzread($fp_in, 8192));
        }
        
        gzclose($fp_in);
        fclose($fp_out);
    }
    
    /**
     * Limpiar backups antiguos (más de X días)
     */
    public function limpiarBackupsAntiguos($dias = 30) {
        $stmt = $this->db->query(
            "SELECT id, ruta FROM backups 
             WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$dias]
        );
        
        $backups = $stmt->fetchAll();
        
        foreach ($backups as $backup) {
            if (file_exists($backup['ruta'])) {
                unlink($backup['ruta']);
            }
            $this->db->query("DELETE FROM backups WHERE id = ?", [$backup['id']]);
        }
        
        return count($backups);
    }
}

