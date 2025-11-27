# App Android - ImaxPrestamos

## Estructura del Proyecto

```
app/
├── src/
│   ├── main/
│   │   ├── java/com/imaxprestamos/
│   │   │   ├── api/
│   │   │   │   ├── ApiClient.java
│   │   │   │   ├── ApiService.java
│   │   │   │   └── models/
│   │   │   ├── activities/
│   │   │   │   ├── LoginActivity.java
│   │   │   │   ├── DashboardActivity.java
│   │   │   │   ├── PrestamosActivity.java
│   │   │   │   ├── ClientesActivity.java
│   │   │   │   ├── RutasActivity.java
│   │   │   │   └── PagosActivity.java
│   │   │   ├── fragments/
│   │   │   ├── adapters/
│   │   │   ├── utils/
│   │   │   └── services/
│   │   └── res/
│   └── test/
└── build.gradle
```

## Configuración

1. **build.gradle (Module: app)**
```gradle
dependencies {
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.9.0'
    implementation 'androidx.recyclerview:recyclerview:1.2.1'
    implementation 'com.google.android.material:material:1.4.0'
    implementation 'androidx.lifecycle:lifecycle-viewmodel:2.4.0'
    implementation 'androidx.lifecycle:lifecycle-livedata:2.4.0'
}
```

2. **AndroidManifest.xml**
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.CAMERA" />
```

## Endpoints de la API

Base URL: `https://tu-dominio.com/api/`

- `POST /auth/login` - Autenticación
- `GET /prestamos` - Listar préstamos
- `POST /prestamos` - Crear préstamo
- `GET /clientes` - Listar clientes
- `POST /clientes` - Crear cliente
- `GET /consultas/cedula?cedula=XXX` - Consultar cédula
- `GET /consultas/data-creditos?cedula=XXX` - Consultar data créditos
- `GET /rutas` - Listar rutas
- `POST /rutas` - Crear ruta
- `POST /pagos` - Registrar pago
- `GET /dashboard` - Dashboard

## Funcionalidades Principales

1. **Autenticación**
   - Login con email y contraseña
   - Almacenamiento seguro de token
   - Refresh token automático

2. **Gestión de Préstamos**
   - Crear nuevos préstamos
   - Ver lista de préstamos
   - Detalles de préstamo
   - Aprobar/rechazar (supervisores)

3. **Gestión de Clientes**
   - Buscar clientes
   - Crear nuevos clientes
   - Consultar cédulas
   - Consultar data créditos

4. **Rutas de Supervisores**
   - Crear rutas de cobro
   - Ver visitas programadas
   - Registrar resultados de visita
   - Geolocalización

5. **Pagos**
   - Registrar pagos
   - Ver historial de pagos
   - Generar recibos

6. **Dashboard**
   - Estadísticas generales
   - Préstamos vencidos
   - Cobros del día

## Ejemplo de Implementación

### ApiClient.java
```java
public class ApiClient {
    private static final String BASE_URL = "https://tu-dominio.com/api/";
    private static Retrofit retrofit;
    
    public static Retrofit getClient() {
        if (retrofit == null) {
            OkHttpClient client = new OkHttpClient.Builder()
                .addInterceptor(new AuthInterceptor())
                .build();
            
            retrofit = new Retrofit.Builder()
                .baseUrl(BASE_URL)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build();
        }
        return retrofit;
    }
}
```

### AuthInterceptor.java
```java
public class AuthInterceptor implements Interceptor {
    @Override
    public Response intercept(Chain chain) throws IOException {
        Request original = chain.request();
        String token = SharedPreferencesManager.getToken();
        
        Request.Builder requestBuilder = original.newBuilder()
            .header("Authorization", "Bearer " + token)
            .header("Content-Type", "application/json");
        
        return chain.proceed(requestBuilder.build());
    }
}
```

