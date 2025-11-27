# Frontend - ImaxPrestamos

Frontend web desarrollado con SASS y una capa ligera de build para el Sistema de Préstamos.

## 🚀 Instalación

```bash
# Instalar dependencias
npm install

# Ambiente de desarrollo (SASS + JS + assets en watch + servidor)
npm run dev

# Compilar todo para producción (CSS comprimido + copia de JS y assets)
npm run build
```

### Scripts disponibles

| Script | Descripción |
| --- | --- |
| `npm run sass:watch` | Compila SASS desde `src/scss` hacia `public/css` en modo watch |
| `npm run sass:build` | Genera CSS comprimido listo para producción |
| `npm run js:watch` | Copia automáticamente los JS de `src/js` hacia `public/js` |
| `npm run assets:watch` | Sincroniza imágenes/fuentes desde `src/assets` hacia `public/img` |
| `npm run pages:watch` | Copia las vistas (`public/views/pages`) a la raíz de `public/` y se mantiene escuchando |
| `npm run serve` | Levanta `live-server` apuntando a `public/` |
| `npm run dev` | Corre todo lo anterior en paralelo (incluye `pages:watch`) |
| `npm run pages:copy` | Copia una vez las vistas para exponerlas como `http://localhost:3000/pagina.html` |
| `npm run build` | Ejecuta `js:copy`, `assets:copy`, `pages:copy` y `sass:build` |

## 📁 Estructura

```
frontend/
├── package.json
├── README.md
├── public/                   # Código servido por Node o cualquier servidor estático
│   ├── css/                  # CSS compilado desde SASS
│   ├── js/                   # JS copiado desde src/js
│   ├── img/                  # Activos optimizados
│   ├── *.html                # Copia automática de cada vista para exponer `/login.html`, `/clientes.html`, etc.
│   └── views/
│       ├── index.html        # Landing/redirecciones
│       ├── pages/            # Vistas fuente (se editan aquí y se copian a la raíz)
│       └── layouts/
│           ├── components/   # Header, sidebar, layouts compartidos
│           └── templates/    # Plantillas base
└── src/                      # Código fuente
    ├── scss/                 # SASS modularizado
    ├── js/                   # Lógica JavaScript moderna (ES6+)
    └── assets/               # Imágenes, íconos y fuentes originales
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

Editar `src/js/app.js` y cambiar `API_BASE_URL`:

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

