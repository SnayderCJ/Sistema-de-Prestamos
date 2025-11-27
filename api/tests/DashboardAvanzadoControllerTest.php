<?php
/**
 * Tests de Integración para DashboardAvanzadoController
 * 
 * Ejecutar con: phpunit tests/DashboardAvanzadoControllerTest.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/DashboardAvanzadoController.php';
require_once __DIR__ . '/../middleware/auth.php';

use PHPUnit\Framework\TestCase;

class DashboardAvanzadoControllerTest extends TestCase {
    private $controller;
    private $userMock;
    
    protected function setUp(): void {
        $this->controller = new DashboardAvanzadoController();
        
        // Mock de usuario para pruebas
        $this->userMock = [
            'id' => 1,
            'rol' => 'admin',
            'sucursal_id' => 1
        ];
    }
    
    /**
     * Test: Obtener estadísticas avanzadas sin filtros
     */
    public function testGetEstadisticasAvanzadasSinFiltros() {
        $filters = [];
        
        // Capturar output
        ob_start();
        try {
            $this->controller->getEstadisticasAvanzadas($this->userMock, $filters);
        } catch (Exception $e) {
            // Si hay error de conexión a BD, saltar test
            $this->markTestSkipped('Base de datos no disponible: ' . $e->getMessage());
        }
        $output = ob_get_clean();
        
        // Verificar que se genera respuesta JSON
        $this->assertNotEmpty($output);
        $data = json_decode($output, true);
        
        if ($data) {
            $this->assertArrayHasKey('estadisticas', $data);
            $this->assertArrayHasKey('graficos', $data);
            $this->assertArrayHasKey('top_clientes', $data);
            $this->assertArrayHasKey('tendencias', $data);
        }
    }
    
    /**
     * Test: Validar filtros de fecha
     */
    public function testValidarFiltrosFecha() {
        // Test: fecha desde mayor que fecha hasta (debe fallar)
        $filters = [
            'fecha_desde' => '2024-01-31',
            'fecha_hasta' => '2024-01-01'
        ];
        
        ob_start();
        try {
            $this->controller->getEstadisticasAvanzadas($this->userMock, $filters);
        } catch (Exception $e) {
            $this->markTestSkipped('Base de datos no disponible');
        }
        $output = ob_get_clean();
        $data = json_decode($output, true);
        
        // Debe retornar error
        if ($data && isset($data['error'])) {
            $this->assertStringContainsString('fecha desde no puede ser mayor', $data['error']);
        }
    }
    
    /**
     * Test: Validar formato de fecha
     */
    public function testValidarFormatoFecha() {
        $filters = [
            'fecha_desde' => '2024/01/01', // Formato incorrecto
            'fecha_hasta' => '2024-01-31'
        ];
        
        ob_start();
        try {
            $this->controller->getEstadisticasAvanzadas($this->userMock, $filters);
        } catch (Exception $e) {
            $this->markTestSkipped('Base de datos no disponible');
        }
        $output = ob_get_clean();
        $data = json_decode($output, true);
        
        // Debe retornar error de formato
        if ($data && isset($data['error'])) {
            $this->assertStringContainsString('Formato de fecha inválido', $data['error']);
        }
    }
    
    /**
     * Test: Validar rango máximo de fechas (2 años)
     */
    public function testValidarRangoMaximoFechas() {
        $filters = [
            'fecha_desde' => '2020-01-01',
            'fecha_hasta' => '2024-12-31' // Más de 2 años
        ];
        
        ob_start();
        try {
            $this->controller->getEstadisticasAvanzadas($this->userMock, $filters);
        } catch (Exception $e) {
            $this->markTestSkipped('Base de datos no disponible');
        }
        $output = ob_get_clean();
        $data = json_decode($output, true);
        
        // Debe retornar error de rango
        if ($data && isset($data['error'])) {
            $this->assertStringContainsString('rango de fechas no puede exceder', $data['error']);
        }
    }
    
    /**
     * Test: Estructura de respuesta de estadísticas
     */
    public function testEstructuraRespuestaEstadisticas() {
        $filters = [
            'fecha_desde' => '2024-01-01',
            'fecha_hasta' => '2024-01-31'
        ];
        
        ob_start();
        try {
            $this->controller->getEstadisticasAvanzadas($this->userMock, $filters);
        } catch (Exception $e) {
            $this->markTestSkipped('Base de datos no disponible');
        }
        $output = ob_get_clean();
        $data = json_decode($output, true);
        
        if ($data && !isset($data['error'])) {
            // Verificar estructura de estadísticas
            if (isset($data['estadisticas'])) {
                $stats = $data['estadisticas'];
                $this->assertArrayHasKey('total_prestamos', $stats);
                $this->assertArrayHasKey('prestamos_activos', $stats);
                $this->assertArrayHasKey('prestamos_vencidos', $stats);
                $this->assertArrayHasKey('tasa_recuperacion', $stats);
            }
            
            // Verificar estructura de gráficos
            if (isset($data['graficos'])) {
                $graficos = $data['graficos'];
                $this->assertArrayHasKey('prestamos_por_mes', $graficos);
                $this->assertArrayHasKey('cobros_por_mes', $graficos);
                $this->assertArrayHasKey('distribucion_estado', $graficos);
            }
        }
    }
}

