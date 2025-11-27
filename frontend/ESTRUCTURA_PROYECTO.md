# Estructura del Proyecto - ImaxPrestamos

Este documento describe la estructura completa del proyecto y cómo usar el sistema de componentes.

## 📁 Estructura de Directorios

```
frontend/
├── components/          # Componentes HTML reutilizables
│   ├── header.html     # Header de navegación
│   └── sidebar.html    # Menú lateral
├── templates/          # Plantillas base
│   └── base.html       # Plantilla base para páginas
├── js/
│   ├── config.js       # Configuración de la aplicación
│   ├── app.js          # Aplicación principal
│   ├── utils.js        # Utilidades globales
│   ├── components.js   # Sistema de carga de componentes
│   ├── auth.js         # Módulo de autenticación
│   └── [página].js     # Scripts específicos por página
├── scss/
│   ├── components/     # Estilos de componentes
│   ├── layout/         # Estilos de layout
│   ├── pages/          # Estilos específicos de páginas
│   └── main.scss       # Archivo principal de estilos
├── css/                # CSS compilado (generado)
├── login.html          # Página de inicio de sesión
├── register.html       # Página de registro
└── [página].html       # Páginas de la aplicación
```

## 🧩 Sistema de Componentes

### ¿Qué es?

El sistema de componentes permite reutilizar código HTML común (como header y sidebar) sin duplicarlo en cada página. Esto mejora:
- **Mantenibilidad**: Cambios en un solo lugar
- **Consistencia**: Mismo diseño en todas las páginas
- **Rendimiento**: Código más limpio y rápido
- **Accesibilidad**: Mejor estructura semántica

### Cómo Usar

#### 1. En una página HTML nueva:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Página - ImaxPrestamos</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Header Component -->
    <div data-component="header"></div>

    <div class="main-layout">
        <!-- Sidebar Component -->
        <div data-component="sidebar"></div>

        <!-- Main Content -->
        <main class="main-content" role="main">
            <h1>Mi Contenido</h1>
            <!-- Tu contenido aquí -->
        </main>
    </div>

    <!-- Scripts (IMPORTANTE: en este orden) -->
    <script src="js/config.js"></script>
    <script src="js/app.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/components.js"></script>
    <!-- Tu script específico aquí -->
</body>
</html>
```

#### 2. Los componentes se cargan automáticamente

El archivo `components.js` detecta los elementos con `data-component` y carga el HTML correspondiente desde `components/[nombre].html`.

#### 3. Componentes disponibles

- **header**: Header de navegación principal
- **sidebar**: Menú lateral completo

### Crear un Nuevo Componente

1. Crea el archivo HTML en `components/`:
```html
<!-- components/mi-componente.html -->
<div class="mi-componente">
    <h2>Mi Componente</h2>
    <p>Contenido del componente</p>
</div>
```

2. Úsalo en cualquier página:
```html
<div data-component="mi-componente"></div>
```

3. El componente se cargará automáticamente cuando se cargue la página.

## 🔐 Páginas de Autenticación

### Login (`login.html`)

- Formulario de inicio de sesión
- Validación en tiempo real
- Manejo de errores
- Opción "Recordar sesión"

### Registro (`register.html`)

- Formulario de registro completo
- Validación de cédula dominicana
- Validación de contraseñas
- Aceptación de términos

### Características

- **Validación avanzada**: Usa `validateFormAdvanced()` de `utils.js`
- **Mensajes de error accesibles**: Con roles ARIA y mensajes claros
- **Auto-redirección**: Después de login/registro exitoso

## 🎨 Sistema de Estilos

### Organización

Los estilos están organizados en:

1. **Variables** (`_variables.scss`): Colores, espaciados, tipografías
2. **Mixins** (`_mixins.scss`): Funciones reutilizables
3. **Base**: Reset, tipografía, utilidades
4. **Layout**: Header, sidebar, footer, grid
5. **Components**: Botones, formularios, tablas, modales, etc.
6. **Pages**: Estilos específicos por página
7. **Responsive**: Media queries para móvil y tablet

### Prevenir Choques de Estilos

El archivo `scss/components/_components.scss` incluye:

- **Isolation**: Los componentes no interfieren entre sí
- **Z-index organizado**: Header (1000), Sidebar (999), Modals (2000), Loading (3000)
- **Reset de estilos**: Previene herencia no deseada
- **Mejoras de accesibilidad**: Focus visible, roles ARIA

### Mejores Prácticas

1. **Usa las variables**: No hardcodees colores o espaciados
2. **Sigue la estructura**: Mantén los estilos en sus archivos correspondientes
3. **Evita !important**: Usa especificidad CSS correcta
4. **Responsive first**: Diseña primero para móvil

## 📝 Refactorizar Páginas Existentes

Para refactorizar una página existente:

1. **Reemplaza el header**:
```html
<!-- ANTES -->
<header class="header">...</header>

<!-- DESPUÉS -->
<div data-component="header"></div>
```

2. **Reemplaza el sidebar**:
```html
<!-- ANTES -->
<aside class="sidebar">...</aside>

<!-- DESPUÉS -->
<div data-component="sidebar"></div>
```

3. **Agrega los scripts en orden**:
```html
<script src="js/config.js"></script>
<script src="js/app.js"></script>
<script src="js/utils.js"></script>
<script src="js/components.js"></script>
```

4. **Mantén el contenido principal**:
```html
<main class="main-content" role="main">
    <!-- Tu contenido aquí -->
</main>
```

## 🚀 Funcionalidades del Sistema de Componentes

### Actualización Automática de Navegación

El sistema actualiza automáticamente:
- Enlaces activos según la página actual
- Información del usuario en el header
- Badges de notificaciones

### Accesibilidad

- Roles ARIA apropiados
- Navegación por teclado
- Indicadores de foco visibles
- Mensajes de error accesibles

### Rendimiento

- Componentes se cargan una vez y se cachean
- Animaciones optimizadas con `will-change`
- Reducción de repaint con `transform: translateZ(0)`

## 📋 Checklist para Nuevas Páginas

- [ ] Usar `data-component="header"` y `data-component="sidebar"`
- [ ] Incluir scripts en el orden correcto
- [ ] Agregar `role="main"` al contenido principal
- [ ] Usar estructura semántica HTML5
- [ ] Probar accesibilidad con navegación por teclado
- [ ] Verificar que los estilos no choquen
- [ ] Probar en diferentes tamaños de pantalla

## 🔧 Solución de Problemas

### Los componentes no se cargan

1. Verifica que `components.js` esté cargado después de `app.js`
2. Verifica que el archivo existe en `components/[nombre].html`
3. Revisa la consola del navegador para errores

### Los estilos se ven mal

1. Verifica que `css/main.css` esté compilado correctamente
2. Revisa que `_components.scss` esté importado en `main.scss`
3. Usa las herramientas de desarrollador para inspeccionar

### La navegación no se actualiza

1. Verifica que los enlaces tengan `data-page` en el sidebar
2. Verifica que el nombre de la página coincida con `data-page`
3. Revisa la consola para errores de JavaScript

## 📚 Recursos Adicionales

- `MEJORAS_IMPLEMENTADAS.md`: Mejoras de seguridad y funcionalidad
- `RESUMEN_CORRECCIONES.md`: Resumen de todas las correcciones
- `ACTUALIZAR_HTML.md`: Guía para actualizar archivos HTML

---

**Última actualización**: 2025-01-27

