# Guía para Actualizar Archivos HTML

Para que todas las mejoras funcionen correctamente, es necesario actualizar los archivos HTML para que carguen los scripts en el orden correcto.

## Orden de Carga de Scripts

Los scripts deben cargarse en este orden:

1. `js/config.js` - Configuración de la aplicación
2. `js/app.js` - Aplicación principal (depende de config.js)
3. `js/utils.js` - Utilidades globales (opcional pero recomendado)
4. `js/[nombre-pagina].js` - Script específico de la página

## Ejemplo de Actualización

**ANTES:**
```html
<script src="js/app.js"></script>
<script src="js/prestamos.js"></script>
```

**DESPUÉS:**
```html
<script src="js/config.js"></script>
<script src="js/app.js"></script>
<script src="js/utils.js"></script>
<script src="js/prestamos.js"></script>
```

## Script de Actualización Automática

Puedes usar este comando para actualizar todos los archivos HTML automáticamente (requiere `sed`):

```bash
# Desde el directorio frontend/
find . -name "*.html" -type f -exec sed -i '' 's|<script src="js/app\.js"></script>|<script src="js/config.js"></script>\n    <script src="js/app.js"></script>\n    <script src="js/utils.js"></script>|g' {} \;
```

**Nota:** Este comando funciona en macOS/Linux. Para Windows, usa PowerShell o Git Bash.

## Archivos Ya Actualizados

Los siguientes archivos ya han sido actualizados como ejemplos:
- `index.html`
- `prestamos.html`
- `clientes.html`
- `pagos.html`

## Verificación

Para verificar que un archivo HTML está correctamente configurado, busca que tenga esta secuencia antes de `</body>`:

```html
    <script src="js/config.js"></script>
    <script src="js/app.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/[nombre].js"></script>
</body>
```

## Importante

- Si no cargas `config.js`, la aplicación seguirá funcionando pero usará valores por defecto
- Si no cargas `utils.js`, algunas funciones de utilidad no estarán disponibles globalmente
- La aplicación es compatible hacia atrás, pero se recomienda cargar todos los scripts para aprovechar todas las mejoras

