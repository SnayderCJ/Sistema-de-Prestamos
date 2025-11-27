# Frontend - ImaxPrestamos

Frontend web desarrollado con SASS para el Sistema de Préstamos.

## 🚀 Instalación

```bash
# Instalar dependencias
npm install

# Compilar SASS en modo desarrollo (watch)
npm run sass

# Compilar SASS para producción
npm run sass:build

# Iniciar servidor de desarrollo
npm run dev
```

## 📁 Estructura

```
frontend/
├── scss/              # Archivos SASS fuente
│   ├── main.scss      # Archivo principal
│   ├── _variables.scss # Variables
│   ├── _mixins.scss   # Mixins
│   ├── base/          # Estilos base
│   ├── components/    # Componentes
│   ├── layout/        # Layout
│   ├── pages/         # Páginas específicas
│   └── responsive/    # Media queries
├── css/               # CSS compilado (generado)
├── js/                # JavaScript
├── index.html         # Página principal
└── package.json       # Dependencias
```

## 🎨 SASS

El proyecto usa SASS con la siguiente estructura:

- **Variables**: Colores, tipografía, espaciado, breakpoints
- **Mixins**: Funciones reutilizables (flexbox, botones, etc.)
- **Componentes**: Botones, formularios, cards, tablas, modales
- **Layout**: Header, sidebar, footer, grid
- **Pages**: Estilos específicos por página
- **Responsive**: Media queries para mobile y tablet

## 🔧 Configuración

Editar `js/app.js` y cambiar `API_BASE_URL`:

```javascript
const API_BASE_URL = 'http://tu-servidor.com/api';
```

## 📱 Responsive

El diseño es completamente responsive con breakpoints:
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

## 🎯 Características

- ✅ Diseño moderno y limpio
- ✅ Completamente responsive
- ✅ Sistema de componentes reutilizables
- ✅ Integración con API REST
- ✅ Manejo de autenticación
- ✅ Loading states y alertas
- ✅ Formateo de moneda y fechas

## 🚀 Build para Producción

```bash
npm run build
```

Esto compilará SASS en modo comprimido para producción.

