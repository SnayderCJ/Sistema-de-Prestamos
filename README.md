# ERP Multicajas RD

Sistema completo de gestión empresarial con backend PHP, API REST, frontend web, y aplicaciones móviles para Android e iOS.

## 📋 Características Principales

### Sistema de Préstamos
- Gestión completa de préstamos
- Cálculo automático de cuotas e intereses
- Gestión de pagos y mora
- Reenganche (refinanciamiento)
- Estados de cuenta
- Contratos automáticos

### Gestión de Clientes
- CRUD completo de clientes
- Codeudores y garantes
- Consultas avanzadas (JCE, Data Créditos, DGII)
- Historial de préstamos

### Sistema Fiscal
- Comprobantes Fiscales (NCF)
- **Facturación Electrónica (eCF)** con firma digital
- Reportes DGII (606, 607, 608, 609) en TXT, Excel y PDF
- Integración con DGII

### Ventas y Compras
- Gestión de ventas
- Gestión de compras
- Artículos con utilidad
- Precios variables (contado/crédito)
- Proveedores y categorías

### Vehículos
- Inventario de vehículos
- Financiamientos
- Importaciones
- Hipotecas

### Recursos Humanos
- Nómina (cálculo según leyes RD)
- Empleados y departamentos
- Bonos de cobradores

### Cooperativas
- Gestión de cooperativas
- Apartaciones de socios
- Distribución de utilidades (4 métodos)
- Porcentaje de utilidad opcional por socio

### Integraciones
- WhatsApp Business API (CRM)
- Notificaciones push (FCM/APNs)
- Email automático
- Webhooks
- Backups automáticos

## 🏗️ Estructura del Proyecto

```
erp_multicajas_rd/
├── api/                          # Backend PHP
│   ├── config/                   # Configuración
│   ├── controllers/              # 54 Controladores
│   ├── routes/                   # 54 Rutas API
│   ├── services/                 # Servicios de negocio
│   ├── middleware/               # Autenticación, permisos, rate limiting
│   ├── utils/                    # Utilidades (PDF, Excel, DGII)
│   └── scripts/                  # Scripts utilitarios
├── database/                     # Base de datos
│   ├── schema_prestamos.sql     # Esquema completo
│   └── migrations/               # Migraciones
├── frontend/                     # Frontend Web
│   ├── scss/                     # Archivos SASS
│   ├── js/                       # 46 Archivos JavaScript
│   └── *.html                    # 44 Páginas HTML
└── mobile/                       # Apps Móviles
    ├── android/                  # App Android (100% completo)
    └── ios/                      # App iOS (100% completo)
```

## 🚀 Instalación

Ver [INSTALACION.md](INSTALACION.md) para instrucciones detalladas.

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- Node.js 14+ (para frontend)
- Composer (para backend)

### Configuración Rápida

1. **Base de Datos**
```bash
mysql -u root -p < database/schema_prestamos.sql
```

2. **Backend**
```bash
cd api
cp config/config.example.php config/config.php
# Editar config/config.php con tus credenciales
```

3. **Frontend**
```bash
cd frontend
npm install
npm run sass
```

4. **Apps Móviles**
- Android: Abrir en Android Studio
- iOS: `pod install` y abrir en Xcode

## 📚 Documentación

- [INSTALACION.md](INSTALACION.md) - Guía de instalación completa
- [FACTURACION_ELECTRONICA_IMPLEMENTADA.md](FACTURACION_ELECTRONICA_IMPLEMENTADA.md) - Guía de facturación electrónica
- [WHATSAPP_CRM_GUIDE.md](WHATSAPP_CRM_GUIDE.md) - Guía de WhatsApp CRM
- [COOPERATIVAS_IMPLEMENTADO.md](COOPERATIVAS_IMPLEMENTADO.md) - Guía de cooperativas
- [api/docs/](api/docs/) - Documentación de API
- [mobile/android/README.md](mobile/android/README.md) - Guía Android
- [mobile/ios/README.md](mobile/ios/README.md) - Guía iOS

## 🔐 Seguridad

- Autenticación JWT
- Refresh tokens
- Control de permisos granular
- Validación de datos
- Prepared statements
- Rate limiting
- Auditoría completa

## 📊 Estado del Proyecto

**✅ 100% COMPLETO**

- ✅ Backend API: 100% (55 controladores, 55+ rutas)
- ✅ Frontend Web: 100% (46 páginas)
- ✅ Base de Datos: 100% (60+ tablas, 12 migraciones)
- ✅ Mobile Android: 100%
- ✅ Mobile iOS: 100%
- ✅ Integraciones: 100%
- ✅ Facturación Electrónica: 100%
- ✅ Cooperativas: 100%

## 📝 Licencia

Este proyecto es privado y propietario.

---

**ERP Multicajas RD - Sistema de Gestión Empresarial Completo**
