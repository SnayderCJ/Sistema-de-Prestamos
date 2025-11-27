#!/bin/bash
# Script para refactorizar páginas HTML para usar componentes
# Reemplaza header y sidebar con componentes

echo "Refactorizando páginas HTML para usar componentes..."
echo ""

# Lista de páginas a refactorizar (excluyendo login, register, index que ya están listos)
PAGES=(
    "prestamos.html"
    "clientes.html"
    "pagos.html"
    "rutas.html"
    "analisis.html"
    "usuarios.html"
    "tasas.html"
    "caja.html"
    "desembolsos.html"
    "vehiculos.html"
    "ventas.html"
    "articulos.html"
    "compras.html"
    "proveedores.html"
    "bancos.html"
    "monedas.html"
    "impuestos.html"
    "departamentos.html"
    "empleados.html"
    "nomina.html"
    "contabilidad.html"
    "configuracion.html"
    "notificaciones.html"
    "dashboard-avanzado.html"
    "consultas.html"
    "codeudores.html"
    "garantes.html"
    "contratos.html"
    "recibos.html"
    "comprobantes-fiscales.html"
    "tipos-comprobantes.html"
    "legal.html"
    "reportes-dgii.html"
    "cooperativas.html"
    "cooperativa-socios.html"
    "reenganche.html"
    "estados-cuenta.html"
    "bonos-cobradores.html"
    "cheques-empresariales.html"
    "hipotecas.html"
    "importaciones-vehiculos.html"
    "financiamientos-vehiculos.html"
    "ordenes-incautacion.html"
    "categorias-articulos.html"
    "whatsapp-crm.html"
)

for page in "${PAGES[@]}"; do
    if [ ! -f "$page" ]; then
        echo "⚠️  Saltando: $page (no existe)"
        continue
    fi

    # Verificar si ya está refactorizado
    if grep -q 'data-component="header"' "$page" 2>/dev/null; then
        echo "✓ $page ya está refactorizado"
        continue
    fi

    # Verificar si tiene header/sidebar para refactorizar
    if ! grep -q '<header class="header">' "$page" 2>/dev/null && ! grep -q '<aside class="sidebar">' "$page" 2>/dev/null; then
        echo "- $page no tiene header/sidebar para refactorizar"
        continue
    fi

    echo "🔄 Refactorizando: $page"

    # Crear backup
    cp "$page" "${page}.backup"

    # Reemplazar header (patrón común)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' '/<header class="header">/,/<\/header>/c\
    <!-- Header Component -->\
    <div data-component="header"></div>
' "$page" 2>/dev/null

        # Reemplazar sidebar (patrón común)
        sed -i '' '/<aside class="sidebar">/,/<\/aside>/c\
        <!-- Sidebar Component -->\
        <div data-component="sidebar"></div>
' "$page" 2>/dev/null

        # Agregar components.js antes del script específico de la página
        if ! grep -q 'js/components.js' "$page"; then
            sed -i '' '/<script src="js\/utils.js"><\/script>/a\
    <script src="js/components.js"></script>
' "$page" 2>/dev/null
        fi

        # Agregar role="main" al main-content si no lo tiene
        sed -i '' 's/<main class="main-content">/<main class="main-content" role="main">/g' "$page" 2>/dev/null
    else
        # Linux
        sed -i '/<header class="header">/,/<\/header>/c\    <!-- Header Component -->\n    <div data-component="header"></div>' "$page" 2>/dev/null
        sed -i '/<aside class="sidebar">/,/<\/aside>/c\        <!-- Sidebar Component -->\n        <div data-component="sidebar"></div>' "$page" 2>/dev/null
        sed -i '/<script src="js\/utils.js"><\/script>/a\    <script src="js/components.js"></script>' "$page" 2>/dev/null
        sed -i 's/<main class="main-content">/<main class="main-content" role="main">/g' "$page" 2>/dev/null
    fi

    echo "  ✓ Completado: $page"
done

echo ""
echo "✅ Refactorización completada!"
echo ""
echo "Nota: Se crearon archivos .backup por si necesitas revertir cambios."
echo "Revisa manualmente las páginas que tengan estructuras de header/sidebar diferentes."

