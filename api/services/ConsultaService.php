<?php
/**
 * Servicio de Consultas Externas
 */

class ConsultaService {
    
    /**
     * Consultar JCE (Junta Central Electoral)
     */
    public function consultarJCE($cedula) {
        // Implementar integración real con API de JCE
        // Por ahora retorna estructura de ejemplo
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => JCE_API_URL . '/api/cedula/' . $cedula,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . JCE_API_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Error en la consulta: HTTP $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception($data['error'] ?? 'Error desconocido');
        }
        
        return [
            'cedula' => $data['cedula'] ?? $cedula,
            'nombre' => $data['nombre'] ?? null,
            'apellido' => $data['apellido'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'lugar_nacimiento' => $data['lugar_nacimiento'] ?? null,
            'nacionalidad' => $data['nacionalidad'] ?? 'Dominicana',
            'estado_civil' => $data['estado_civil'] ?? null,
            'valida' => $data['valida'] ?? true
        ];
    }
    
    /**
     * Consultar Data Créditos
     * Si no hay API key configurada, retorna datos de prueba
     */
    public function consultarDataCreditos($cedula) {
        // Verificar si hay API key configurada
        if (empty(DATA_CREDITOS_API_KEY) || DATA_CREDITOS_API_KEY === '') {
            // Modo de prueba - retornar datos simulados
            return $this->consultarDataCreditosPrueba($cedula);
        }
        
        // Consulta real a la API de Data Créditos
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => DATA_CREDITOS_API_URL . '/api/consulta/' . $cedula,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . DATA_CREDITOS_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Error de conexión Data Créditos: $error");
            // Si falla la conexión, intentar con datos de prueba
            return $this->consultarDataCreditosPrueba($cedula);
        }
        
        if ($httpCode !== 200) {
            error_log("Error HTTP Data Créditos: $httpCode");
            // Si falla la API, intentar con datos de prueba
            return $this->consultarDataCreditosPrueba($cedula);
        }
        
        $data = json_decode($response, true);
        
        if (!$data || isset($data['error'])) {
            error_log("Error en respuesta Data Créditos: " . ($data['error'] ?? 'Error desconocido'));
            return $this->consultarDataCreditosPrueba($cedula);
        }
        
        return [
            'cedula' => $cedula,
            'score' => $data['score'] ?? $data['score_credito'] ?? null,
            'deuda_total' => $data['deuda_total'] ?? $data['deudaTotal'] ?? 0,
            'cantidad_prestamos_activos' => $data['prestamos_activos'] ?? $data['prestamosActivos'] ?? 0,
            'cantidad_prestamos_vencidos' => $data['prestamos_vencidos'] ?? $data['prestamosVencidos'] ?? 0,
            'ultimo_prestamo_fecha' => $data['ultimo_prestamo_fecha'] ?? $data['ultimoPrestamoFecha'] ?? null,
            'historial_credito' => $data['historial'] ?? $data['historialCredito'] ?? [],
            'recomendacion' => $this->calcularRecomendacion($data),
            'fuente' => 'api_real'
        ];
    }
    
    /**
     * Consultar Data Créditos en modo prueba (sin API key)
     * Genera datos simulados basados en la cédula
     */
    private function consultarDataCreditosPrueba($cedula) {
        // Generar score basado en el último dígito de la cédula (para pruebas)
        $ultimoDigito = (int)substr($cedula, -1);
        $score = 500 + ($ultimoDigito * 30); // Score entre 500-770
        
        // Generar datos simulados
        $prestamosActivos = $ultimoDigito % 3;
        $prestamosVencidos = ($ultimoDigito % 5 === 0) ? 1 : 0;
        $deudaTotal = $prestamosActivos * 50000;
        
        return [
            'cedula' => $cedula,
            'score' => $score,
            'deuda_total' => $deudaTotal,
            'cantidad_prestamos_activos' => $prestamosActivos,
            'cantidad_prestamos_vencidos' => $prestamosVencidos,
            'ultimo_prestamo_fecha' => date('Y-m-d', strtotime('-' . ($ultimoDigito % 12) . ' months')),
            'historial_credito' => [
                [
                    'institucion' => 'Banco Ejemplo',
                    'monto' => 50000,
                    'fecha' => date('Y-m-d', strtotime('-6 months')),
                    'estado' => 'vigente'
                ]
            ],
            'recomendacion' => $this->calcularRecomendacion([
                'score' => $score,
                'prestamos_vencidos' => $prestamosVencidos
            ]),
            'fuente' => 'modo_prueba',
            'nota' => 'Datos de prueba. Configure DATA_CREDITOS_API_KEY para consultas reales.'
        ];
    }
    
    /**
     * Calcular recomendación basada en score
     */
    private function calcularRecomendacion($data) {
        $score = $data['score'] ?? 0;
        $deudaTotal = $data['deuda_total'] ?? 0;
        $prestamosVencidos = $data['prestamos_vencidos'] ?? 0;
        
        if ($score >= 700 && $prestamosVencidos == 0) {
            return 'aprobado';
        } elseif ($score >= 600 && $prestamosVencidos <= 1) {
            return 'condicionado';
        } else {
            return 'rechazado';
        }
    }
    
    /**
     * Consultar DGII (Dirección General de Impuestos Internos)
     */
    public function consultarDGII($rnc) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => DGII_API_URL . '/api/rnc/' . $rnc,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . DGII_API_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Error en la consulta: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
}

