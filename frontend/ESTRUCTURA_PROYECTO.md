# Estructura del Proyecto - ImaxPrestamos

Este documento describe la estructura completa del proyecto y cómo aprovechar el sistema de componentes y estilos bajo la nueva organización.

## 📁 Estructura de directorios

```
nombre-proyecto/
├── package.json
├── api/                      # Backend (controllers, routes, middlewares)
├── database/                 # Migraciones y scripts SQL
└── frontend/
    ├── package.json          # Dependencias del frontend (sass, live-server, cpx)
    ├── src/                  # Código fuente editable
    │   ├── scss/             # Variables, mixins, layout, componentes y páginas
    │   ├── js/               # App.js, config.js, utils.js y scripts por página
    │   └── assets/           # Imágenes originales, iconos SVG, fuentes
    └── public/               # Artefactos servidos al navegador
        ├── css/              # Resultado de compilar src/scss
        ├── js/               # Copia/minificado desde src/js
        ├── img/              # Activos optimizados desde src/assets
        ├── *.html            # Copia de cada vista para exponer /login.html, /clientes.html, etc.
        └── views/
            ├── index.html    # Landing o redirecciones principales
            ├── pages/        # Todas las vistas HTML del sistema
            └── layouts/
                ├── components/  # header.html, sidebar.html, etc.
                └── templates/   # Plantillas base reutilizables
```

### Flujo de build

1. Editas archivos dentro de `src/`.
2. `npm run dev` ejecuta:
   - `sass:watch`: compila `src/scss` → `public/css`
   - `js:watch`: copia `src/js` → `public/js`
   - `assets:watch`: copia `src/assets` → `public/img`
   - `pages:watch`: copia `public/views/pages/*.html` hacia la raíz de `public/`
   - `serve`: levanta `live-server` apuntando a `public/`
3. `npm run build` genera CSS comprimido, copia JS/activos y ejecuta `pages:copy` para dejar las vistas listas en la raíz de `public/`.

### Exponer vistas sin `/views/pages`

Gracias a `pages:watch`/`pages:copy`, cualquier archivo en `public/views/pages/*.html` se duplica en `public/*.html`. Así puedes abrir `http://localhost:3000/login.html` (o `/clientes.html`, `/prestamos.html`, etc.) sin tener que incluir `/views/pages/` en la URL. Recuerda que la edición sigue haciéndose en `public/views/pages`.

## 🧩 Sistema de componentes

### ¿Qué es?

Permite reutilizar fragmentos HTML (header, sidebar, etc.) sin duplicar código. Mejora mantenibilidad, consistencia, rendimiento y accesibilidad.

### Cómo usar

#### 1. Al crear una nueva vista (`public/views/pages/nueva-pagina.html`)

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Página - ImaxPrestamos</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <!-- Header Component -->
    <div data-component="header"></div>

    <div class="main-layout">
        <!-- Sidebar Component -->
        <div data-component="sidebar"></div>

        <main class="main-content" role="main">
            <h1>Mi Contenido</h1>
            <!-- Tu contenido aquí -->
        </main>
    </div>

    <!-- Scripts (IMPORTANTE: en este orden) -->
    <script src="/js/config.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/utils.js"></script>
    <script src="/js/components.js"></script>
    <!-- Tu script específico -->
    <script src="/js/nueva-pagina.js"></script>
</body>
</html>
```

#### 2. Carga automática

`src/js/components.js` detecta los elementos con `data-component` y busca el HTML en `/views/layouts/components/[nombre].html`.

#### 3. Componentes disponibles

- **header**: navegación superior
- **sidebar**: menú lateral principal

### Crear un nuevo componente

1. Crea `public/views/layouts/components/mi-componente.html`:

```html
<div class="mi-componente">
    <h2>Mi componente</h2>
</div>
```

2. Úsalo en cualquier vista:

```html
<div data-component="mi-componente"></div>
```

## 🔐 Páginas de autenticación

- `public/views/pages/login.html`
- `public/views/pages/register.html`

Características:
- Validaciones avanzadas (`validateFormAdvanced()` desde `/js/utils.js`)
- Mensajes accesibles (roles ARIA, focus management)
- Auto-redirección tras autenticación

## 🎨 Sistema de estilos

La organización en `src/scss` se mantiene modular:

1. `_variables.scss`: Colores, espaciados, tipografías y breakpoints.
2. `_mixins.scss`: Funciones reutilizables.
3. `base/`: Reset, tipografía, utilidades.
4. `layout/`: Header, sidebar, footer, grid.
5. `components/`: Botones, formularios, tablas, modales, etc.
6. `pages/`: Estilos específicos para cada vista.
7. `responsive/`: Media queries de mobile/tablet.

`src/scss/main.scss` importa todo y genera `public/css/main.css`.

### Buenas prácticas

- Utiliza variables y mixins para evitar valores mágicos.
- Evita `!important`, prioriza la especificidad correcta.
- Diseña mobile-first (breakpoints declarados en `_variables.scss`).
- Mantén los estilos de cada página dentro de `pages/`.

## 📝 Refactorizar páginas existentes

Para migrar una página antigua (fuera de `public/views/pages`):

1. Mueve el archivo a `public/views/pages`.
2. Reemplaza `header` y `sidebar` por los componentes (`div data-component`).
3. Asegura que los scripts usen rutas absolutas (`/js/...`).
4. Añade `role="main"` al contenido principal.

El script `refactorizar_paginas.sh` automatiza este flujo buscando archivos dentro de `public/views/pages`.

## 🚀 Funcionalidades clave

- Navegación activa automática según la URL (`components.js`).
- Datos del usuario centralizados (`auth.getCurrentUser()` dentro de `components.js`).
- Componentes cacheados para reducir peticiones.
- Accesibilidad: roles ARIA, focus visible, navegación por teclado.

## 📋 Checklist para nuevas páginas

- [ ] Colocar `<link rel="stylesheet" href="/css/main.css">`.
- [ ] Usar `data-component="header"` y `data-component="sidebar"`.
- [ ] Agregar los scripts globales en el orden indicado.
- [ ] Incluir `role="main"` en el `<main>`.
- [ ] Mantener la semántica HTML5.
- [ ] Validar accesibilidad con teclado.
- [ ] Probar en mobile, tablet y desktop.

## 🔧 Solución de problemas

### Los componentes no se cargan
1. Verifica que `/js/components.js` esté cargado después de `utils`.
2. Revisa que el archivo exista en `/views/layouts/components/`.
3. Revisa la consola del navegador para detectar errores 404 o CORS.

### Los estilos no se actualizan
1. Asegúrate de tener `npm run sass:watch` ejecutándose.
2. Comprueba que `public/css/main.css` se regenere (revisa la fecha).
3. Si el estilo específico no cambia, confirma que esté importado en `src/scss/main.scss`.

### La navegación activa falla
1. Asegúrate de que los enlaces del sidebar tengan `data-page="nombre-archivo"`.
2. Confirma que la URL real coincida con el nombre del archivo (`prestamos.html` → `data-page="prestamos"`).
3. Si trabajas sin servidor (abriendo archivos directamente), utiliza rutas relativas válidas o `live-server`.

## 📚 Recursos adicionales

- `README.md`: Guía rápida de instalación y scripts.
- `MEJORAS_IMPLEMENTADAS.md`, `RESUMEN_CORRECCIONES.md`: Cambios funcionales y correcciones (consultar en la raíz del repositorio si aplica).

---

**Última actualización**: 2025-11-27

