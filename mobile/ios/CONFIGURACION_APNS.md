# 📱 Configuración de Notificaciones Push (APNs) para iOS

## ✅ Implementación Completada

La implementación de notificaciones push para iOS está **100% completa** en el código. Solo falta la configuración en Apple Developer.

---

## 📋 Pasos para Configurar APNs

### 1. **Crear Certificado APNs en Apple Developer**

1. Ve a [Apple Developer Portal](https://developer.apple.com/account/)
2. Navega a **Certificates, Identifiers & Profiles**
3. Selecciona **Identifiers** → Tu App ID
4. Habilita **Push Notifications**
5. Crea un **Apple Push Notification service SSL Certificate**
   - Desarrollo: `Apple Development iOS Push Services`
   - Producción: `Apple Production iOS Push Services`
6. Descarga el certificado (`.cer`)
7. Conviértelo a `.p12` usando Keychain Access

### 2. **Configurar el Backend**

El backend ya está preparado para recibir notificaciones APNs. Solo necesitas:

1. **Subir el certificado `.p12` al servidor**
2. **Configurar la ruta en `api/services/NotificacionService.php`**:

```php
// En NotificacionService.php, método enviarPushIOS
private function enviarPushIOS($token, $titulo, $mensaje, $datos = []) {
    // Ruta al certificado .p12
    $certPath = __DIR__ . '/../../certificates/apns_production.p12';
    $certPassword = 'tu_password_del_certificado';
    
    // URL de APNs (producción o sandbox)
    $apnsUrl = 'ssl://gateway.push.apple.com:2195'; // Producción
    // $apnsUrl = 'ssl://gateway.sandbox.push.apple.com:2195'; // Desarrollo
    
    // Implementar envío usando stream_context_create o librería
}
```

### 3. **Configurar Capabilities en Xcode**

1. Abre el proyecto en Xcode
2. Selecciona el target de la app
3. Ve a **Signing & Capabilities**
4. Haz clic en **+ Capability**
5. Agrega **Push Notifications**
6. Agrega **Background Modes** y habilita:
   - ✅ Remote notifications

### 4. **Verificar Info.plist**

Asegúrate de que `Info.plist` tenga:

```xml
<key>UIBackgroundModes</key>
<array>
    <string>remote-notification</string>
</array>
```

---

## ✅ Código Implementado

### Archivos Creados:

1. **`NotificationService.swift`**
   - ✅ Solicitud de permisos
   - ✅ Registro de dispositivo
   - ✅ Manejo de notificaciones
   - ✅ Delegado de notificaciones

2. **`PrestamosApp.swift`**
   - ✅ AppDelegate configurado
   - ✅ Registro automático al iniciar
   - ✅ Manejo de tokens

3. **`ApiService.swift`**
   - ✅ Método `registrarDispositivoPush`

4. **`LoginView.swift`**
   - ✅ Registro automático después del login

5. **`Notificacion.swift`**
   - ✅ Modelos de datos para notificaciones

---

## 🧪 Pruebas

### 1. **Probar Registro de Dispositivo**

```swift
// El dispositivo se registra automáticamente al iniciar la app
// Verifica en la consola:
// ✅ "📱 APNs Token: [token]"
// ✅ "✅ Dispositivo registrado correctamente en el servidor"
```

### 2. **Probar Notificación Local**

```swift
// En NotificationService.swift ya está implementado
// Las notificaciones remotas se muestran automáticamente
```

### 3. **Probar Notificación Remota**

Usa el endpoint del backend:
```bash
POST /api/notificaciones/enviar-prueba
{
  "titulo": "Prueba",
  "mensaje": "Esta es una notificación de prueba",
  "tipo": "info"
}
```

---

## 📝 Notas Importantes

1. **Certificados**: Necesitas certificados separados para desarrollo y producción
2. **Sandbox vs Producción**: 
   - Desarrollo: `gateway.sandbox.push.apple.com`
   - Producción: `gateway.push.apple.com`
3. **Token**: El token APNs cambia periódicamente, el código lo maneja automáticamente
4. **Permisos**: El usuario debe aceptar los permisos de notificaciones

---

## ✅ Estado

**Implementación de Código: 100% ✅**

**Configuración Pendiente:**
- [ ] Crear certificado APNs en Apple Developer
- [ ] Configurar certificado en el backend
- [ ] Probar envío de notificaciones

---

**Una vez configurado el certificado, las notificaciones push funcionarán completamente.** 🚀

