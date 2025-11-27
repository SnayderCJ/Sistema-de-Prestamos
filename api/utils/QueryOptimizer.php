<?php
/**
 * Utilidades para optimización de consultas SQL
 */

class QueryOptimizer {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Analizar y sugerir índices faltantes
     */
    public function analizarIndices() {
        $sugerencias = [];
        
        // Verificar índices en tablas principales
        $tablas = ['prestamos', 'pagos', 'clientes', 'cuotas_prestamos', 'auditoria'];
        
        foreach ($tablas as $tabla) {
            $indices = $this->obtenerIndices($tabla);
            $sugerencias[$tabla] = $this->sugerirIndices($tabla, $indices);
        }
        
        return $sugerencias;
    }
    
    /**
     * Obtener índices existentes de una tabla
     */
    private function obtenerIndices($tabla) {
        $stmt = $this->db->query(
            "SHOW INDEX FROM $tabla",
            []
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Sugerir índices basado en consultas comunes
     */
    private function sugerirIndices($tabla, $indicesExistentes) {
        $sugerencias = [];
        $indicesNombres = array_column($indicesExistentes, 'Key_name');
        
        $indicesRecomendados = [
            'prestamos' => [
                ['columnas' => ['cliente_id'], 'nombre' => 'idx_prestamos_cliente'],
                ['columnas' => ['estado'], 'nombre' => 'idx_prestamos_estado'],
                ['columnas' => ['fecha_creacion'], 'nombre' => 'idx_prestamos_fecha'],
                ['columnas' => ['estado', 'fecha_creacion'], 'nombre' => 'idx_prestamos_estado_fecha']
            ],
            'pagos' => [
                ['columnas' => ['prestamo_id'], 'nombre' => 'idx_pagos_prestamo'],
                ['columnas' => ['fecha_pago'], 'nombre' => 'idx_pagos_fecha'],
                ['columnas' => ['prestamo_id', 'fecha_pago'], 'nombre' => 'idx_pagos_prestamo_fecha']
            ],
            'clientes' => [
                ['columnas' => ['cedula'], 'nombre' => 'idx_clientes_cedula'],
                ['columnas' => ['estado_credito'], 'nombre' => 'idx_clientes_estado']
            ],
            'cuotas_prestamos' => [
                ['columnas' => ['prestamo_id'], 'nombre' => 'idx_cuotas_prestamo'],
                ['columnas' => ['estado'], 'nombre' => 'idx_cuotas_estado'],
                ['columnas' => ['fecha_vencimiento'], 'nombre' => 'idx_cuotas_fecha_vencimiento'],
                ['columnas' => ['prestamo_id', 'estado'], 'nombre' => 'idx_cuotas_prestamo_estado']
            ],
            'auditoria' => [
                ['columnas' => ['usuario_id'], 'nombre' => 'idx_auditoria_usuario'],
                ['columnas' => ['fecha'], 'nombre' => 'idx_auditoria_fecha'],
                ['columnas' => ['tabla'], 'nombre' => 'idx_auditoria_tabla'],
                ['columnas' => ['usuario_id', 'fecha'], 'nombre' => 'idx_auditoria_usuario_fecha']
            ]
        ];
        
        if (isset($indicesRecomendados[$tabla])) {
            foreach ($indicesRecomendados[$tabla] as $indice) {
                if (!in_array($indice['nombre'], $indicesNombres)) {
                    $sugerencias[] = [
                        'nombre' => $indice['nombre'],
                        'columnas' => $indice['columnas'],
                        'sql' => "CREATE INDEX {$indice['nombre']} ON $tabla (" . implode(', ', $indice['columnas']) . ")"
                    ];
                }
            }
        }
        
        return $sugerencias;
    }
    
    /**
     * Optimizar consulta usando EXPLAIN
     */
    public function analizarConsulta($sql, $params = []) {
        $explainSql = "EXPLAIN " . $sql;
        
        try {
            $stmt = $this->db->query($explainSql, $params);
            $result = $stmt->fetchAll();
            
            $analisis = [
                'query' => $sql,
                'explain' => $result,
                'sugerencias' => $this->generarSugerencias($result)
            ];
            
            return $analisis;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Generar sugerencias basadas en EXPLAIN
     */
    private function generarSugerencias($explain) {
        $sugerencias = [];
        
        foreach ($explain as $row) {
            // Si type es 'ALL', sugiere índice
            if ($row['type'] === 'ALL') {
                $sugerencias[] = "Considerar agregar índice en la tabla '{$row['table']}' para mejorar el rendimiento";
            }
            
            // Si Extra contiene 'Using filesort' o 'Using temporary'
            if (strpos($row['Extra'] ?? '', 'Using filesort') !== false) {
                $sugerencias[] = "Considerar agregar índice para evitar ordenamiento en disco";
            }
            
            if (strpos($row['Extra'] ?? '', 'Using temporary') !== false) {
                $sugerencias[] = "La consulta está creando tablas temporales, considerar optimización";
            }
        }
        
        return array_unique($sugerencias);
    }
    
    /**
     * Obtener estadísticas de rendimiento de consultas
     */
    public function obtenerEstadisticasRendimiento() {
        // Consultas lentas (si está habilitado slow query log)
        $slowQueries = $this->obtenerSlowQueries();
        
        // Estadísticas de tablas
        $estadisticasTablas = $this->obtenerEstadisticasTablas();
        
        return [
            'slow_queries' => $slowQueries,
            'estadisticas_tablas' => $estadisticasTablas
        ];
    }
    
    private function obtenerSlowQueries() {
        // En producción, leer del slow query log
        // Por ahora retornamos estructura vacía
        return [];
    }
    
    private function obtenerEstadisticasTablas() {
        $tablas = ['prestamos', 'pagos', 'clientes', 'cuotas_prestamos'];
        $estadisticas = [];
        
        foreach ($tablas as $tabla) {
            $stmt = $this->db->query(
                "SELECT 
                    table_rows as filas,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as tamanio_mb
                 FROM information_schema.TABLES
                 WHERE table_schema = DATABASE()
                 AND table_name = ?",
                [$tabla]
            );
            
            $estadisticas[$tabla] = $stmt->fetch();
        }
        
        return $estadisticas;
    }
}

