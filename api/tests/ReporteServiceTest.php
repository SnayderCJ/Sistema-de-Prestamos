<?php
/**
 * Tests Unitarios para ReporteService
 * 
 * Ejecutar con: phpunit tests/ReporteServiceTest.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ReporteService.php';

use PHPUnit\Framework\TestCase;

class ReporteServiceTest extends TestCase {
    private $reporteService;
    
    protected function setUp(): void {
        $this->reporteService = new ReporteService();
    }
    
    /**
     * Test: Generar reporte de préstamos sin filtros
     */
    public function testGenerarReportePrestamosSinFiltros() {
        $filters = [];
        $reporte = $this->reporteService->generarReportePrestamos($filters);
        
        $this->assertIsArray($reporte);
        $this->assertArrayHasKey('prestamos', $reporte);
        $this->assertArrayHasKey('resumen', $reporte);
        $this->assertArrayHasKey('fecha_generacion', $reporte);
        
        // Verificar estructura del resumen
        $resumen = $reporte['resumen'];
        $this->assertArrayHasKey('total_prestamos', $resumen);
        $this->assertArrayHasKey('total_monto', $resumen);
        $this->assertArrayHasKey('total_pagado', $resumen);
        $this->assertArrayHasKey('total_pendiente', $resumen);
        $this->assertArrayHasKey('total_mora', $resumen);
    }
    
    /**
     * Test: Generar reporte de préstamos con filtros de fecha
     */
    public function testGenerarReportePrestamosConFiltrosFecha() {
        $filters = [
            'fecha_desde' => '2024-01-01',
            'fecha_hasta' => '2024-01-31'
        ];
        
        $reporte = $this->reporteService->generarReportePrestamos($filters);
        
        $this->assertIsArray($reporte);
        $this->assertArrayHasKey('filtros', $reporte);
        $this->assertEquals('2024-01-01', $reporte['filtros']['fecha_desde']);
        $this->assertEquals('2024-01-31', $reporte['filtros']['fecha_hasta']);
    }
    
    /**
     * Test: Generar reporte de préstamos con filtro de estado
     */
    public function testGenerarReportePrestamosConFiltroEstado() {
        $filters = [
            'estado' => 'vigente'
        ];
        
        $reporte = $this->reporteService->generarReportePrestamos($filters);
        
        $this->assertIsArray($reporte);
        
        // Verificar que todos los préstamos tienen el estado correcto
        foreach ($reporte['prestamos'] as $prestamo) {
            $this->assertEquals('vigente', $prestamo['estado']);
        }
    }
    
    /**
     * Test: Generar reporte de cobros
     */
    public function testGenerarReporteCobros() {
        $filters = [];
        $reporte = $this->reporteService->generarReporteCobros($filters);
        
        $this->assertIsArray($reporte);
        $this->assertArrayHasKey('pagos', $reporte);
        $this->assertArrayHasKey('resumen', $reporte);
        
        $resumen = $reporte['resumen'];
        $this->assertArrayHasKey('total_pagos', $resumen);
        $this->assertArrayHasKey('total_cobros', $resumen);
        $this->assertArrayHasKey('total_capital', $resumen);
        $this->assertArrayHasKey('total_interes', $resumen);
        $this->assertArrayHasKey('total_mora', $resumen);
    }
    
    /**
     * Test: Generar reporte de mora
     */
    public function testGenerarReporteMora() {
        $filters = [];
        $reporte = $this->reporteService->generarReporteMora($filters);
        
        $this->assertIsArray($reporte);
        $this->assertArrayHasKey('cuotas_vencidas', $reporte);
        $this->assertArrayHasKey('resumen', $reporte);
        
        $resumen = $reporte['resumen'];
        $this->assertArrayHasKey('total_cuotas_vencidas', $resumen);
        $this->assertArrayHasKey('total_mora', $resumen);
        $this->assertArrayHasKey('promedio_dias_vencido', $resumen);
    }
    
    /**
     * Test: Generar reporte de clientes
     */
    public function testGenerarReporteClientes() {
        $filters = [];
        $reporte = $this->reporteService->generarReporteClientes($filters);
        
        $this->assertIsArray($reporte);
        $this->assertArrayHasKey('clientes', $reporte);
        $this->assertArrayHasKey('resumen', $reporte);
        
        $resumen = $reporte['resumen'];
        $this->assertArrayHasKey('total_clientes', $resumen);
        $this->assertArrayHasKey('clientes_activos', $resumen);
        $this->assertArrayHasKey('clientes_bloqueados', $resumen);
    }
    
    /**
     * Test: Validar cálculo de totales en reporte de préstamos
     */
    public function testValidarCalculoTotalesPrestamos() {
        $filters = [];
        $reporte = $this->reporteService->generarReportePrestamos($filters);
        
        $resumen = $reporte['resumen'];
        $prestamos = $reporte['prestamos'];
        
        // Verificar que el total de préstamos coincide
        $this->assertEquals(count($prestamos), $resumen['total_prestamos']);
        
        // Verificar que el total monto es la suma de montos aprobados
        $sumaMontos = array_sum(array_column($prestamos, 'monto_aprobado'));
        $this->assertEquals($sumaMontos, $resumen['total_monto']);
        
        // Verificar que total pendiente = total monto - total pagado
        $pendienteCalculado = $resumen['total_monto'] - $resumen['total_pagado'];
        $this->assertEquals($pendienteCalculado, $resumen['total_pendiente']);
    }
    
    /**
     * Test: Validar formato de fecha de generación
     */
    public function testValidarFormatoFechaGeneracion() {
        $filters = [];
        $reporte = $this->reporteService->generarReportePrestamos($filters);
        
        $fechaGeneracion = $reporte['fecha_generacion'];
        
        // Verificar formato YYYY-MM-DD HH:MM:SS
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $fechaGeneracion
        );
    }
    
    /**
     * Test: Manejo de errores en tipo de reporte inválido para PDF
     */
    public function testManejoErrorTipoInvalidoPDF() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tipo de reporte no válido');
        
        $this->reporteService->generarPDF('tipo_invalido', []);
    }
    
    /**
     * Test: Manejo de errores en tipo de reporte inválido para Excel
     */
    public function testManejoErrorTipoInvalidoExcel() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tipo de reporte no válido');
        
        $this->reporteService->generarExcel('tipo_invalido', []);
    }
}

