# 🚀 Guía de Instalación - ImaxPrestamos

## Requisitos Previos

- PHP >= 7.4
- MySQL >= 5.7 o MariaDB >= 10.3
- Apache con mod_rewrite o Nginx
- Composer (opcional, para dependencias)

## Paso 1: Configurar Base de Datos

```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE imaxprestamos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar esquema
mysql -u root -p imaxprestamos < database/schema_prestamos.sql
```

## Paso 2: Configurar Backend

### 2.1 Configurar Base de Datos

Editar `api/config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'imaxprestamos';
private $username = 'tu_usuario';
private $password = 'tu_contraseña';
```

### 2.2 Configurar Variables de Entorno

Copiar el archivo de ejemplo:

```bash
cp api/config/config.example.php api/config/config.php
```

Editar `api/config/config.php` y configurar:

- `JWT_SECRET`: Cambiar por un secreto seguro
- `DATA_CREDITOS_API_KEY`: API key de Data Créditos
- `JCE_API_KEY`: API key de JCE
- `DGII_API_KEY`: API key de DGII

### 2.3 Instalar Dependencias (Opcional)

```bash
cd api
composer install
```

## Paso 3: Configurar Servidor Web

### Apache

Asegurarse de que `mod_rewrite` esté habilitado:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Configurar VirtualHost:

```apache
<VirtualHost *:80>
    ServerName prestamos.local
    DocumentRoot /ruta/al/proyecto/api
    
    <Directory /ruta/al/proyecto/api>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name prestamos.local;
    root /ruta/al/proyecto/api;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Paso 4: Verificar Instalación

### 4.1 Probar Conexión a Base de Datos

Crear archivo de prueba `api/test_db.php`:

```php
<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance();
    echo "✅ Conexión a base de datos exitosa";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
```

Acceder: `http://prestamos.local/test_db.php`

### 4.2 Probar API

```bash
# Login
curl -X POST http://prestamos.local/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sistema.com","password":"admin123"}'

# Obtener token y probar endpoint
curl -X GET http://prestamos.local/prestamos \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

## Paso 5: Usuario por Defecto

El sistema crea un usuario administrador por defecto:

- **Email**: admin@sistema.com
- **Contraseña**: admin123
- **Cédula**: 00000000000

⚠️ **IMPORTANTE**: Cambiar la contraseña después del primer acceso.

## Paso 6: Configurar Apps Móviles

### Android

1. Abrir Android Studio
2. Importar proyecto desde `mobile/android/`
3. Configurar `BASE_URL` en `ApiClient.java`
4. Compilar y ejecutar

### iOS

1. Abrir Xcode
2. Abrir proyecto desde `mobile/ios/`
3. Ejecutar `pod install`
4. Configurar `BASE_URL` en `ApiService.swift`
5. Compilar y ejecutar

## Solución de Problemas

### Error 500 - Internal Server Error

1. Verificar permisos de archivos:
```bash
chmod -R 755 api/
chmod -R 777 api/logs/  # Si existe directorio de logs
```

2. Verificar logs de PHP:
```bash
tail -f /var/log/apache2/error.log
# o
tail -f /var/log/php-fpm/error.log
```

### Error de Conexión a Base de Datos

1. Verificar credenciales en `api/config/database.php`
2. Verificar que MySQL esté corriendo:
```bash
sudo systemctl status mysql
```

3. Verificar que el usuario tenga permisos:
```sql
GRANT ALL PRIVILEGES ON erp_multicajas_rd.* TO 'tu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### CORS Errors

Verificar que `.htaccess` esté configurado correctamente y que `mod_headers` esté habilitado en Apache.

## Seguridad en Producción

1. ✅ Cambiar `JWT_SECRET` por uno seguro
2. ✅ Usar HTTPS
3. ✅ Deshabilitar `DEBUG` en producción
4. ✅ Configurar firewall
5. ✅ Hacer backups regulares de la base de datos
6. ✅ Cambiar contraseñas por defecto
7. ✅ Configurar rate limiting
8. ✅ Validar y sanitizar todas las entradas

## Backup de Base de Datos

```bash
# Backup
mysqldump -u root -p erp_multicajas_rd > backup_$(date +%Y%m%d).sql

# Restaurar
mysql -u root -p erp_multicajas_rd < backup_20240101.sql
```

## Próximos Pasos

1. ✅ Configurar API keys de servicios externos
2. ✅ Personalizar tasas de interés según necesidades
3. ✅ Configurar sucursales
4. ✅ Crear usuarios del sistema
5. ✅ Probar todas las funcionalidades
6. ✅ Configurar backups automáticos

---

¿Necesitas ayuda? Revisa la documentación en `README.md` o los logs del sistema.

