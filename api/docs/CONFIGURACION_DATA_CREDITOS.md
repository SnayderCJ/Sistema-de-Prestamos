# Configuración de Consultas Data Créditos

## 📋 Estado Actual

Las consultas de Data Créditos están **disponibles y funcionando** en dos modos:

### ✅ Modo Prueba (Activo por defecto)
- Funciona sin configuración adicional
- Genera datos simulados para desarrollo
- Útil para testing y desarrollo

### 🔧 Modo Producción (Requiere configuración)
- Requiere API key de Data Créditos
- Consultas reales a la API
- Datos precisos del cliente

## 🚀 Configuración

### Paso 1: Obtener API Key

Contactar con el proveedor de Data Créditos (ej: TransUnion, Equifax, etc.) para obtener:
- URL de la API
- API Key / Token de autenticación
- Documentación de endpoints

### Paso 2: Configurar en el Sistema

Editar `api/config/config.php`:

```php
// Configuración de Data Créditos
define('DATA_CREDITOS_API_URL', 'https://api.tu-proveedor-datacreditos.com');
define('DATA_CREDITOS_API_KEY', 'tu_api_key_aqui');
```

O usar variables de entorno:

```bash
export DATA_CREDITOS_API_URL="https://api.tu-proveedor-datacreditos.com"
export DATA_CREDITOS_API_KEY="tu_api_key_aqui"
```

### Paso 3: Verificar Funcionamiento

```bash
# Probar consulta
curl -X GET "http://localhost/api/consultas/data-creditos?cedula=00123456789" \
  -H "Authorization: Bearer TU_TOKEN"
```

## 📡 Endpoints Disponibles

### Consultar Data Créditos
```
GET /api/consultas/data-creditos?cedula=00123456789
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "cedula": "00123456789",
    "score": 750,
    "deuda_total": 150000,
    "cantidad_prestamos_activos": 2,
    "cantidad_prestamos_vencidos": 0,
    "ultimo_prestamo_fecha": "2024-01-15",
    "historial_credito": [...],
    "recomendacion": "aprobado",
    "fuente": "api_real"
  }
}
```

### Historial de Consultas
```
GET /api/consultas/historial?page=1&per_page=20
```

## 🔄 Funcionamiento

1. **Cache Inteligente**: Las consultas se cachean por día
2. **Fallback Automático**: Si falla la API real, usa modo prueba
3. **Registro de Consultas**: Todas las consultas se registran en BD
4. **Validación**: Valida formato de cédula antes de consultar

## 📊 Estructura de Datos

### Tabla: `data_creditos`
Almacena cache de consultas:
- `cedula`: Cédula del cliente
- `score`: Score de crédito
- `deuda_total`: Deuda total
- `cantidad_prestamos_activos`: Préstamos activos
- `cantidad_prestamos_vencidos`: Préstamos vencidos
- `historial_credito`: JSON con historial completo

### Tabla: `consultas_cedulas`
Registra todas las consultas:
- `cedula`: Cédula consultada
- `tipo_consulta`: 'data_creditos', 'jce', 'dgii'
- `estado`: 'exitoso', 'fallido', 'pendiente'
- `resultado`: JSON con resultado
- `fecha_consulta`: Fecha y hora

## ⚠️ Notas Importantes

1. **Límites de API**: Verificar límites de consultas con el proveedor
2. **Costo**: Algunas APIs cobran por consulta
3. **Privacidad**: Los datos son sensibles, asegurar HTTPS
4. **Cache**: El cache evita consultas duplicadas el mismo día

## 🧪 Modo Prueba

Si no hay API key configurada, el sistema genera datos de prueba:
- Score: 500-770 (basado en último dígito de cédula)
- Préstamos: Simulados
- Útil para desarrollo sin costo

## 🔐 Seguridad

- Las consultas requieren autenticación JWT
- Se registra IP del usuario
- Los resultados se almacenan de forma segura
- Validación de cédula antes de consultar

## 📞 Soporte

Para problemas con la integración:
1. Verificar logs: `api/logs/error.log`
2. Verificar configuración de API key
3. Probar endpoint directamente con curl
4. Contactar al proveedor de Data Créditos


