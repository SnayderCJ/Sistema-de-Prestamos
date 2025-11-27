<?php
/**
 * Controlador de Reportes DGII
 */

require_once __DIR__ . '/../services/DGIIService.php';
require_once __DIR__ . '/../utils/DGIIReportGenerator.php';

class ReporteDGIIController {
    private $db;
    private $dgiiService;
    private $reportGenerator;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->dgiiService = new DGIIService();
        $this->reportGenerator = new DGIIReportGenerator();
    }

    public function getAll($filters = []) {
        try {
            $where = ['1=1'];
            $params = [];
            
            if (isset($filters['tipo_reporte'])) {
                $where[] = 'tipo_reporte = ?';
                $params[] = $filters['tipo_reporte'];
            }
            
            if (isset($filters['periodo'])) {
                $where[] = 'periodo = ?';
                $params[] = $filters['periodo'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $this->db->query(
                "SELECT * FROM reportes_dgii WHERE $whereClause ORDER BY periodo DESC, tipo_reporte ASC",
                $params
            );
            
            sendResponse($stmt->fetchAll());
        } catch (Exception $e) {
            sendError('Error al obtener reportes: ' . $e->getMessage(), 500);
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->query("SELECT * FROM reportes_dgii WHERE id = ?", [$id]);
            $reporte = $stmt->fetch();
            
            if (!$reporte) {
                sendError('Reporte no encontrado', 404);
                return;
            }
            
            sendResponse($reporte);
        } catch (Exception $e) {
            sendError('Error al obtener reporte: ' . $e->getMessage(), 500);
        }
    }

    public function generar606($periodo) {
        try {
            // Generar TXT
            $contenidoTXT = $this->reportGenerator->generar606($periodo);
            $archivoTXT = $this->reportGenerator->guardarArchivo('606', $periodo, $contenidoTXT, 'txt');
            
            // Generar Excel
            $contenidoExcel = null;
            $archivoExcel = null;
            try {
                $contenidoExcel = $this->reportGenerator->generarExcel('606', $periodo);
                $archivoExcel = $this->reportGenerator->guardarArchivo('606', $periodo, $contenidoExcel, 'excel');
            } catch (Exception $e) {
                error_log('Error generando Excel: ' . $e->getMessage());
            }
            
            // Generar PDF
            $contenidoPDF = null;
            $archivoPDF = null;
            try {
                $contenidoPDF = $this->reportGenerator->generarPDF('606', $periodo);
                $archivoPDF = $this->reportGenerator->guardarArchivo('606', $periodo, $contenidoPDF, 'pdf');
            } catch (Exception $e) {
                error_log('Error generando PDF: ' . $e->getMessage());
            }
            
            // Guardar registro en BD
            $this->db->query(
                "INSERT INTO reportes_dgii (tipo_reporte, periodo, estado, archivo_txt, archivo_excel, archivo_pdf, fecha_generacion)
                 VALUES (?, ?, 'generado', ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                     estado = 'generado',
                     archivo_txt = ?,
                     archivo_excel = ?,
                     archivo_pdf = ?,
                     fecha_generacion = NOW()",
                ['606', $periodo, $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null, 
                 $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null]
            );
            
            sendResponse([
                'success' => true,
                'message' => 'Reporte 606 generado correctamente',
                'archivos' => [
                    'txt' => $archivoTXT,
                    'excel' => $archivoExcel,
                    'pdf' => $archivoPDF
                ]
            ]);
        } catch (Exception $e) {
            sendError('Error al generar Reporte 606: ' . $e->getMessage(), 500);
        }
    }

    public function generar607($periodo) {
        try {
            $contenidoTXT = $this->reportGenerator->generar607($periodo);
            $archivoTXT = $this->reportGenerator->guardarArchivo('607', $periodo, $contenidoTXT, 'txt');
            
            $contenidoExcel = null;
            $archivoExcel = null;
            try {
                $contenidoExcel = $this->reportGenerator->generarExcel('607', $periodo);
                $archivoExcel = $this->reportGenerator->guardarArchivo('607', $periodo, $contenidoExcel, 'excel');
            } catch (Exception $e) {
                error_log('Error generando Excel: ' . $e->getMessage());
            }
            
            $contenidoPDF = null;
            $archivoPDF = null;
            try {
                $contenidoPDF = $this->reportGenerator->generarPDF('607', $periodo);
                $archivoPDF = $this->reportGenerator->guardarArchivo('607', $periodo, $contenidoPDF, 'pdf');
            } catch (Exception $e) {
                error_log('Error generando PDF: ' . $e->getMessage());
            }
            
            $this->db->query(
                "INSERT INTO reportes_dgii (tipo_reporte, periodo, estado, archivo_txt, archivo_excel, archivo_pdf, fecha_generacion)
                 VALUES (?, ?, 'generado', ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                     estado = 'generado',
                     archivo_txt = ?,
                     archivo_excel = ?,
                     archivo_pdf = ?,
                     fecha_generacion = NOW()",
                ['607', $periodo, $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null, 
                 $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null]
            );
            
            sendResponse([
                'success' => true,
                'message' => 'Reporte 607 generado correctamente',
                'archivos' => [
                    'txt' => $archivoTXT,
                    'excel' => $archivoExcel,
                    'pdf' => $archivoPDF
                ]
            ]);
        } catch (Exception $e) {
            sendError('Error al generar Reporte 607: ' . $e->getMessage(), 500);
        }
    }

    public function generar608($periodo) {
        try {
            $contenidoTXT = $this->reportGenerator->generar608($periodo);
            $archivoTXT = $this->reportGenerator->guardarArchivo('608', $periodo, $contenidoTXT, 'txt');
            
            $contenidoExcel = null;
            $archivoExcel = null;
            try {
                $contenidoExcel = $this->reportGenerator->generarExcel('608', $periodo);
                $archivoExcel = $this->reportGenerator->guardarArchivo('608', $periodo, $contenidoExcel, 'excel');
            } catch (Exception $e) {
                error_log('Error generando Excel: ' . $e->getMessage());
            }
            
            $contenidoPDF = null;
            $archivoPDF = null;
            try {
                $contenidoPDF = $this->reportGenerator->generarPDF('608', $periodo);
                $archivoPDF = $this->reportGenerator->guardarArchivo('608', $periodo, $contenidoPDF, 'pdf');
            } catch (Exception $e) {
                error_log('Error generando PDF: ' . $e->getMessage());
            }
            
            $this->db->query(
                "INSERT INTO reportes_dgii (tipo_reporte, periodo, estado, archivo_txt, archivo_excel, archivo_pdf, fecha_generacion)
                 VALUES (?, ?, 'generado', ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                     estado = 'generado',
                     archivo_txt = ?,
                     archivo_excel = ?,
                     archivo_pdf = ?,
                     fecha_generacion = NOW()",
                ['608', $periodo, $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null, 
                 $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null]
            );
            
            sendResponse([
                'success' => true,
                'message' => 'Reporte 608 generado correctamente',
                'archivos' => [
                    'txt' => $archivoTXT,
                    'excel' => $archivoExcel,
                    'pdf' => $archivoPDF
                ]
            ]);
        } catch (Exception $e) {
            sendError('Error al generar Reporte 608: ' . $e->getMessage(), 500);
        }
    }

    public function generar609($periodo) {
        try {
            $contenidoTXT = $this->reportGenerator->generar609($periodo);
            $archivoTXT = $this->reportGenerator->guardarArchivo('609', $periodo, $contenidoTXT, 'txt');
            
            $contenidoExcel = null;
            $archivoExcel = null;
            try {
                $contenidoExcel = $this->reportGenerator->generarExcel('609', $periodo);
                $archivoExcel = $this->reportGenerator->guardarArchivo('609', $periodo, $contenidoExcel, 'excel');
            } catch (Exception $e) {
                error_log('Error generando Excel: ' . $e->getMessage());
            }
            
            $contenidoPDF = null;
            $archivoPDF = null;
            try {
                $contenidoPDF = $this->reportGenerator->generarPDF('609', $periodo);
                $archivoPDF = $this->reportGenerator->guardarArchivo('609', $periodo, $contenidoPDF, 'pdf');
            } catch (Exception $e) {
                error_log('Error generando PDF: ' . $e->getMessage());
            }
            
            $this->db->query(
                "INSERT INTO reportes_dgii (tipo_reporte, periodo, estado, archivo_txt, archivo_excel, archivo_pdf, fecha_generacion)
                 VALUES (?, ?, 'generado', ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                     estado = 'generado',
                     archivo_txt = ?,
                     archivo_excel = ?,
                     archivo_pdf = ?,
                     fecha_generacion = NOW()",
                ['609', $periodo, $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null, 
                 $archivoTXT['ruta'], $archivoExcel['ruta'] ?? null, $archivoPDF['ruta'] ?? null]
            );
            
            sendResponse([
                'success' => true,
                'message' => 'Reporte 609 generado correctamente',
                'archivos' => [
                    'txt' => $archivoTXT,
                    'excel' => $archivoExcel,
                    'pdf' => $archivoPDF
                ]
            ]);
        } catch (Exception $e) {
            sendError('Error al generar Reporte 609: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Descargar reporte TXT
     */
    public function descargarTXT($tipo, $periodo) {
        try {
            $stmt = $this->db->query(
                "SELECT archivo_txt FROM reportes_dgii WHERE tipo_reporte = ? AND periodo = ?",
                [$tipo, $periodo]
            );
            $reporte = $stmt->fetch();
            
            if (!$reporte || !file_exists($reporte['archivo_txt'])) {
                sendError('Reporte no encontrado. Debe generarlo primero.', 404);
                return;
            }
            
            $mes = substr($periodo, 5, 2);
            $anio = substr($periodo, 0, 4);
            $nombreArchivo = "R{$tipo}_{$anio}{$mes}.txt";
            
            header('Content-Type: text/plain; charset=ISO-8859-1');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . filesize($reporte['archivo_txt']));
            
            readfile($reporte['archivo_txt']);
            exit;
        } catch (Exception $e) {
            sendError('Error al descargar reporte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Descargar reporte Excel
     */
    public function descargarExcel($tipo, $periodo) {
        try {
            $stmt = $this->db->query(
                "SELECT archivo_excel FROM reportes_dgii WHERE tipo_reporte = ? AND periodo = ?",
                [$tipo, $periodo]
            );
            $reporte = $stmt->fetch();
            
            if (!$reporte || !$reporte['archivo_excel'] || !file_exists($reporte['archivo_excel'])) {
                sendError('Reporte Excel no encontrado. Debe generarlo primero.', 404);
                return;
            }
            
            $mes = substr($periodo, 5, 2);
            $anio = substr($periodo, 0, 4);
            $nombreArchivo = "R{$tipo}_{$anio}{$mes}.xlsx";
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . filesize($reporte['archivo_excel']));
            
            readfile($reporte['archivo_excel']);
            exit;
        } catch (Exception $e) {
            sendError('Error al descargar reporte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Descargar reporte PDF
     */
    public function descargarPDF($tipo, $periodo) {
        try {
            $stmt = $this->db->query(
                "SELECT archivo_pdf FROM reportes_dgii WHERE tipo_reporte = ? AND periodo = ?",
                [$tipo, $periodo]
            );
            $reporte = $stmt->fetch();
            
            if (!$reporte || !$reporte['archivo_pdf'] || !file_exists($reporte['archivo_pdf'])) {
                sendError('Reporte PDF no encontrado. Debe generarlo primero.', 404);
                return;
            }
            
            $mes = substr($periodo, 5, 2);
            $anio = substr($periodo, 0, 4);
            $nombreArchivo = "R{$tipo}_{$anio}{$mes}.pdf";
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . filesize($reporte['archivo_pdf']));
            
            readfile($reporte['archivo_pdf']);
            exit;
        } catch (Exception $e) {
            sendError('Error al descargar reporte: ' . $e->getMessage(), 500);
        }
    }
}
