# App iOS - ImaxPrestamos

## Estructura del Proyecto

```
ImaxPrestamosApp/
├── ImaxPrestamosApp/
│   ├── Models/
│   │   ├── Prestamo.swift
│   │   ├── Cliente.swift
│   │   ├── Pago.swift
│   │   └── Ruta.swift
│   ├── Services/
│   │   ├── ApiService.swift
│   │   ├── AuthService.swift
│   │   └── NetworkManager.swift
│   ├── Views/
│   │   ├── LoginView.swift
│   │   ├── DashboardView.swift
│   │   ├── PrestamosView.swift
│   │   ├── ClientesView.swift
│   │   ├── RutasView.swift
│   │   └── PagosView.swift
│   ├── ViewModels/
│   └── Utils/
├── PrestamosAppTests/
└── Podfile
```

## Configuración

1. **Podfile**
```ruby
platform :ios, '13.0'
use_frameworks!

target 'PrestamosApp' do
  pod 'Alamofire', '~> 5.4'
  pod 'SwiftyJSON', '~> 4.0'
  pod 'KeychainSwift', '~> 19.0'
end
```

2. **Info.plist**
```xml
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>
<key>NSCameraUsageDescription</key>
<string>Necesitamos acceso a la cámara para tomar fotos en las visitas</string>
<key>NSLocationWhenInUseUsageDescription</key>
<string>Necesitamos tu ubicación para registrar las visitas</string>
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
   - Almacenamiento seguro de token en Keychain
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
   - Geolocalización con MapKit

5. **Pagos**
   - Registrar pagos
   - Ver historial de pagos
   - Generar recibos

6. **Dashboard**
   - Estadísticas generales
   - Préstamos vencidos
   - Cobros del día

## Ejemplo de Implementación

### ApiService.swift
```swift
import Alamofire

class ApiService {
    static let shared = ApiService()
    private let baseURL = "https://tu-dominio.com/api/"
    
    private var headers: HTTPHeaders {
        var headers = HTTPHeaders()
        headers["Content-Type"] = "application/json"
        if let token = KeychainHelper.getToken() {
            headers["Authorization"] = "Bearer \(token)"
        }
        return headers
    }
    
    func login(email: String, password: String, completion: @escaping (Result<AuthResponse, Error>) -> Void) {
        AF.request(
            baseURL + "auth/login",
            method: .post,
            parameters: ["email": email, "password": password],
            encoding: JSONEncoding.default,
            headers: headers
        ).responseDecodable(of: AuthResponse.self) { response in
            switch response.result {
            case .success(let authResponse):
                KeychainHelper.saveToken(authResponse.token)
                completion(.success(authResponse))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
}
```

### NetworkManager.swift
```swift
import Alamofire

class NetworkManager {
    static let shared = NetworkManager()
    
    private let session: Session = {
        let configuration = URLSessionConfiguration.default
        configuration.timeoutIntervalForRequest = 30
        return Session(configuration: configuration)
    }()
    
    func request<T: Decodable>(
        _ endpoint: String,
        method: HTTPMethod = .get,
        parameters: Parameters? = nil,
        completion: @escaping (Result<T, Error>) -> Void
    ) {
        var headers = HTTPHeaders()
        headers["Content-Type"] = "application/json"
        if let token = KeychainHelper.getToken() {
            headers["Authorization"] = "Bearer \(token)"
        }
        
        session.request(
            endpoint,
            method: method,
            parameters: parameters,
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseDecodable(of: T.self) { response in
            switch response.result {
            case .success(let value):
                completion(.success(value))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
}
```

