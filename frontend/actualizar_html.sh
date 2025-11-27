#!/bin/bash
# Script para actualizar todos los archivos HTML para cargar config.js antes de app.js

echo "Actualizando archivos HTML..."

# Buscar todos los archivos HTML en el directorio frontend
find . -name "*.html" -type f | while read file; do
    # Verificar si el archivo ya tiene config.js cargado
    if grep -q "js/config.js" "$file"; then
        echo "✓ $file ya está actualizado"
        continue
    fi
    
    # Verificar si el archivo tiene app.js
    if grep -q "js/app.js" "$file"; then
        # Crear archivo temporal con la actualización
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS
            sed -i '' 's|<script src="js/app\.js"></script>|<script src="js/config.js"></script>\n    <script src="js/app.js"></script>\n    <script src="js/utils.js"></script>|g' "$file"
        else
            # Linux
            sed -i 's|<script src="js/app\.js"></script>|<script src="js/config.js"></script>\n    <script src="js/app.js"></script>\n    <script src="js/utils.js"></script>|g' "$file"
        fi
        echo "✓ Actualizado: $file"
    else
        echo "- Saltado (no tiene app.js): $file"
    fi
done

echo ""
echo "¡Actualización completada!"
echo ""
echo "Nota: Si algunos archivos no se actualizaron correctamente,"
echo "puedes actualizarlos manualmente siguiendo el patrón:"
echo ""
echo "  <script src=\"js/config.js\"></script>"
echo "  <script src=\"js/app.js\"></script>"
echo "  <script src=\"js/utils.js\"></script>"
echo "  <script src=\"js/[nombre].js\"></script>"

