<?php
/**
 * Script de Backup de Base de Datos
 * Uso: php backup_db.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();
$connection = $db->getConnection();

$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';

$tables = [
    'usuarios',
    'sucursales',
    'clientes',
    'consultas_cedulas',
    'data_creditos',
    'tasas_interes',
    'prestamos',
    'cuotas_prestamos',
    'pagos',
    'rutas_supervisores',
    'visitas_ruta',
    'analisis_prestamos',
    'configuracion_sistema'
];

$output = "-- Backup de Base de Datos - ImaxPrestamos\n";
$output .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $output .= "-- Estructura de tabla: $table\n";
    
    // Obtener estructura
    $stmt = $connection->query("SHOW CREATE TABLE `$table`");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $output .= $row['Create Table'] . ";\n\n";
    
    // Obtener datos
    $stmt = $connection->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        $output .= "-- Datos de tabla: $table\n";
        $output .= "INSERT INTO `$table` VALUES\n";
        
        $values = [];
        foreach ($rows as $row) {
            $rowValues = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $rowValues[] = 'NULL';
                } else {
                    $rowValues[] = $connection->quote($value);
                }
            }
            $values[] = '(' . implode(',', $rowValues) . ')';
        }
        
        $output .= implode(",\n", $values) . ";\n\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($backupFile, $output);

echo "✅ Backup creado exitosamente: $backupFile\n";
echo "📊 Tamaño: " . number_format(filesize($backupFile) / 1024, 2) . " KB\n";

