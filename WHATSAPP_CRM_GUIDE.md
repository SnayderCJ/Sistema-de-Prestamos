# 📱 Guía de Configuración - CRM WhatsApp

## 🎯 Funcionalidades Implementadas

### 1. **CRM de WhatsApp**
- ✅ Envío de mensajes manuales
- ✅ Historial de conversaciones
- ✅ Estadísticas de mensajes
- ✅ Respuestas automáticas básicas
- ✅ Vista de conversaciones agrupadas

### 2. **Notificaciones Automáticas de Pago**
- ✅ Notificación por WhatsApp al registrar pago
- ✅ Notificación por Email al registrar pago
- ✅ Mensajes personalizados con detalles del pago

### 3. **Recordatorios Automáticos**
- ✅ Recordatorios de cuotas vencidas
- ✅ Recordatorios de cuotas próximas a vencer
- ✅ Envío por WhatsApp y Email
- ✅ Control para evitar duplicados

---

## ⚙️ Configuración Inicial

### Paso 1: Configurar WhatsApp Business API

1. **Crear cuenta en Facebook Developers**
   - Ir a https://developers.facebook.com
   - Crear una cuenta de desarrollador

2. **Crear aplicación**
   - Crear nueva aplicación tipo "Business"
   - Agregar producto "WhatsApp"

3. **Obtener credenciales**
   - Access Token: En "WhatsApp" > "API Setup"
   - Phone Number ID: En "WhatsApp" > "API Setup"
   - Verificar número de teléfono

4. **Configurar en el sistema**
   - Ir a: Configuración > Sistema
   - Configurar:
     - `whatsapp_api_url`: `https://graph.facebook.com/v18.0`
     - `whatsapp_api_token`: [Tu Access Token]
     - `whatsapp_phone_number_id`: [Tu Phone Number ID]
     - `whatsapp_webhook_token`: [Token secreto para webhook]

### Paso 2: Configurar Webhook

1. **URL del Webhook**
   ```
   https://tu-dominio.com/api/whatsapp/webhook
   ```

2. **Token de verificación**
   - Usar el mismo token configurado en `whatsapp_webhook_token`

3. **Configurar en Facebook**
   - Ir a: WhatsApp > Configuration > Webhook
   - URL: `https://tu-dominio.com/api/whatsapp/webhook`
   - Verify Token: [Tu token secreto]
   - Suscribir a eventos: `messages`

### Paso 3: Configurar Email (SMTP)

1. **Configurar SMTP en el sistema**
   - Ir a: Configuración > Sistema
   - Configurar:
     - `smtp_host`: `smtp.gmail.com` (o tu servidor SMTP)
     - `smtp_port`: `587`
     - `smtp_user`: [Tu email]
     - `smtp_pass`: [Tu contraseña o App Password]
     - `email_from`: `noreply@imaxprestamos.com`
     - `email_from_name`: `ImaxPrestamos`

2. **Para Gmail**
   - Habilitar "Acceso de aplicaciones menos seguras" o usar "App Password"
   - Generar App Password en: Google Account > Security > App passwords

### Paso 4: Activar Notificaciones

En Configuración > Sistema, verificar:
- ✅ `whatsapp_notificaciones_activas` = `1`
- ✅ `whatsapp_recordatorios_activos` = `1`
- ✅ `email_notificaciones_activas` = `1`

---

## 🔄 Recordatorios Automáticos

### Configurar Cron Job

Agregar al crontab del servidor:

```bash
# Ejecutar recordatorios diariamente a las 9:00 AM
0 9 * * * /usr/bin/php /ruta/al/proyecto/api/cron/recordatorios.php

# O ejecutar cada 6 horas
0 */6 * * * /usr/bin/php /ruta/al/proyecto/api/cron/recordatorios.php
```

### Ejecutar Manualmente

```bash
php api/cron/recordatorios.php
```

O desde el navegador (con token):
```
https://tu-dominio.com/api/cron/recordatorios.php?token=TOKEN_SECRETO_AQUI
```

---

## 📱 Uso del CRM WhatsApp

### Enviar Mensaje Manual

1. Ir a: **CRM WhatsApp**
2. Clic en: **"Enviar Mensaje"**
3. Seleccionar cliente (opcional) o ingresar número
4. Escribir mensaje
5. Clic en: **"Enviar"**

### Ver Conversaciones

1. Ir a: **CRM WhatsApp**
2. Ver lista de conversaciones
3. Clic en: **"Ver"** para ver historial completo
4. Responder desde la vista de conversación

### Enviar Notificación de Pago

**Automático:**
- Se envía automáticamente al registrar un pago

**Manual:**
- En la vista de Pagos, clic en el botón 📱 junto a cada pago

### Enviar Recordatorio

**Automático:**
- Se ejecuta diariamente mediante cron job

**Manual:**
- En la vista de Préstamos, clic en el botón 📱 junto a cada préstamo activo

---

## 🔧 Endpoints de API

### WhatsApp

- `GET /api/whatsapp` - Obtener historial
- `GET /api/whatsapp/conversaciones` - Obtener conversaciones
- `GET /api/whatsapp/estadisticas` - Obtener estadísticas
- `POST /api/whatsapp/enviar` - Enviar mensaje manual
- `POST /api/whatsapp/notificacion-pago` - Enviar notificación de pago
- `POST /api/whatsapp/recordatorio` - Enviar recordatorio
- `GET /api/whatsapp/webhook` - Webhook (verificación)
- `POST /api/whatsapp/webhook` - Webhook (recibir mensajes)

### Recordatorios

- `POST /api/recordatorios/procesar` - Procesar recordatorios automáticos
- `POST /api/recordatorios/enviar` - Enviar recordatorio manual

---

## 📊 Base de Datos

### Tablas Creadas

1. **whatsapp_historial**
   - Almacena todos los mensajes enviados y recibidos
   - Relacionado con clientes, préstamos y pagos

2. **email_historial**
   - Almacena historial de emails enviados
   - Para auditoría y seguimiento

3. **recordatorios_enviados**
   - Controla qué recordatorios ya se enviaron
   - Evita duplicados en el mismo día

---

## ✅ Funcionalidades Completadas

- ✅ Servicio de WhatsApp completo
- ✅ Servicio de Email completo
- ✅ Notificaciones automáticas de pago
- ✅ Recordatorios automáticos
- ✅ CRM de WhatsApp con interfaz
- ✅ Historial y estadísticas
- ✅ Webhook para recibir mensajes
- ✅ Respuestas automáticas básicas
- ✅ Integración en vistas de Pagos y Préstamos

---

## 🚀 Próximos Pasos (Opcional)

- [ ] Respuestas automáticas más avanzadas (IA)
- [ ] Plantillas de mensajes personalizables
- [ ] Programación de mensajes
- [ ] Chat en tiempo real
- [ ] Integración con otros canales (SMS, Telegram)

---

**¡El sistema de CRM WhatsApp está completo y listo para usar!** 🎉

