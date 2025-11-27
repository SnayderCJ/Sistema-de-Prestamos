# Tests - Sistema de Reportes

Este directorio contiene tests unitarios y de integración para el sistema de reportes de la Semana 3.

## Requisitos

- PHPUnit 9.0 o superior
- PHP 7.4 o superior
- Base de datos de prueba configurada

## Instalación

```bash
cd api
composer install --dev
```

## Estructura de Tests

### Tests Unitarios

- `ReporteServiceTest.php`: Tests para el servicio de reportes
  - Generación de reportes sin filtros
  - Generación de reportes con filtros
  - Validación de cálculos
  - Manejo de errores

### Tests de Integración

- `DashboardAvanzadoControllerTest.php`: Tests para el controlador de dashboard avanzado
  - Validación de filtros
  - Estructura de respuestas
  - Validaciones de fecha

## Ejecutar Tests

### Todos los tests

```bash
vendor/bin/phpunit tests/
```

### Test específico

```bash
vendor/bin/phpunit tests/ReporteServiceTest.php
vendor/bin/phpunit tests/DashboardAvanzadoControllerTest.php
```

### Con cobertura

```bash
vendor/bin/phpunit --coverage-html coverage/ tests/
```

## Configuración

Crear archivo `phpunit.xml` en el directorio `api/`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Reportes">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">services</directory>
            <directory suffix=".php">controllers</directory>
            <directory suffix=".php">utils</directory>
        </whitelist>
    </filter>
</phpunit>
```

## Notas

- Los tests que requieren base de datos se saltarán automáticamente si la BD no está disponible
- Usar base de datos de prueba separada para tests
- Limpiar datos de prueba después de cada ejecución

