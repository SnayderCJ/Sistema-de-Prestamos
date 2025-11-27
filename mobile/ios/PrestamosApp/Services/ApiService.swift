import Foundation
import Alamofire

class ApiService {
    static let shared = ApiService()
    private let baseURL = "http://tu-servidor.com/api/"
    
    private var headers: HTTPHeaders {
        var headers = HTTPHeaders()
        headers["Content-Type"] = "application/json"
        if let token = KeychainHelper.getToken() {
            headers["Authorization"] = "Bearer \(token)"
        }
        return headers
    }
    
    // MARK: - Auth
    func login(email: String, password: String, completion: @escaping (Result<AuthResponse, Error>) -> Void) {
        AF.request(
            baseURL + "auth/login",
            method: .post,
            parameters: ["email": email, "password": password],
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseDecodable(of: AuthResponse.self) { response in
            switch response.result {
            case .success(let authResponse):
                if let token = authResponse.data?.token {
                    KeychainHelper.saveToken(token)
                }
                completion(.success(authResponse))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    func loginSimple(email: String, password: String, completion: @escaping (Result<Bool, Error>) -> Void) {
        login(email: email, password: password) { result in
            switch result {
            case .success:
                completion(.success(true))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    // MARK: - Préstamos
    func getPrestamos(page: Int = 1, perPage: Int = 20, completion: @escaping (Result<PrestamosResponse, Error>) -> Void) {
        AF.request(
            baseURL + "prestamos",
            method: .get,
            parameters: ["page": page, "per_page": perPage],
            headers: headers
        ).validate().responseDecodable(of: PrestamosResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    func getPrestamosSimple(completion: @escaping (Result<[Prestamo], Error>) -> Void) {
        getPrestamos { result in
            switch result {
            case .success(let response):
                completion(.success(response.data?.items ?? []))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    func getPrestamo(id: Int, completion: @escaping (Result<PrestamoResponse, Error>) -> Void) {
        AF.request(
            baseURL + "prestamos/\(id)",
            method: .get,
            headers: headers
        ).validate().responseDecodable(of: PrestamoResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    func getPrestamoSimple(id: Int, completion: @escaping (Result<Prestamo, Error>) -> Void) {
        getPrestamo(id: id) { result in
            switch result {
            case .success(let response):
                completion(.success(response.data))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    func createPrestamo(_ prestamo: PrestamoRequest, completion: @escaping (Result<PrestamoResponse, Error>) -> Void) {
        AF.request(
            baseURL + "prestamos",
            method: .post,
            parameters: prestamo,
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseDecodable(of: PrestamoResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Clientes
    func getClientes(page: Int = 1, completion: @escaping (Result<[Cliente], Error>) -> Void) {
        AF.request(
            baseURL + "clientes",
            method: .get,
            parameters: ["page": page],
            headers: headers
        ).validate().responseDecodable(of: ClientesResponse.self) { response in
            switch response.result {
            case .success(let data): 
                completion(.success(data.data?.items ?? []))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Consultas
    func consultarCedula(_ cedula: String, completion: @escaping (Result<ConsultaResponse, Error>) -> Void) {
        AF.request(
            baseURL + "consultas/cedula",
            method: .get,
            parameters: ["cedula": cedula],
            headers: headers
        ).validate().responseDecodable(of: ConsultaResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    func consultarDataCreditos(_ cedula: String, completion: @escaping (Result<DataCreditosResponse, Error>) -> Void) {
        AF.request(
            baseURL + "consultas/data-creditos",
            method: .get,
            parameters: ["cedula": cedula],
            headers: headers
        ).validate().responseDecodable(of: DataCreditosResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Rutas
    func getRutas(page: Int = 1, completion: @escaping (Result<[Ruta], Error>) -> Void) {
        AF.request(
            baseURL + "rutas",
            method: .get,
            parameters: ["page": page],
            headers: headers
        ).validate().responseDecodable(of: RutasResponse.self) { response in
            switch response.result {
            case .success(let data): 
                completion(.success(data.data?.items ?? []))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Pagos
    func registrarPago(_ pago: PagoRequest, completion: @escaping (Result<PagoResponse, Error>) -> Void) {
        AF.request(
            baseURL + "pagos",
            method: .post,
            parameters: pago,
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseDecodable(of: PagoResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Dashboard
    func getDashboard(completion: @escaping (Result<DashboardResponse, Error>) -> Void) {
        AF.request(
            baseURL + "dashboard",
            method: .get,
            headers: headers
        ).validate().responseDecodable(of: DashboardResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Clientes
    func getCliente(id: Int, completion: @escaping (Result<ClienteResponse, Error>) -> Void) {
        AF.request(
            baseURL + "clientes/\(id)",
            method: .get,
            headers: headers
        ).validate().responseDecodable(of: ClienteResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    func getClienteSimple(id: Int, completion: @escaping (Result<Cliente, Error>) -> Void) {
        getCliente(id: id) { result in
            switch result {
            case .success(let response):
                completion(.success(response.data))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    func createCliente(_ cliente: ClienteRequest, completion: @escaping (Result<ClienteResponse, Error>) -> Void) {
        AF.request(
            baseURL + "clientes",
            method: .post,
            parameters: cliente,
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseDecodable(of: ClienteResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Rutas
    func getRuta(id: Int, completion: @escaping (Result<RutaResponse, Error>) -> Void) {
        AF.request(
            baseURL + "rutas/\(id)",
            method: .get,
            headers: headers
        ).validate().responseDecodable(of: RutaResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    func getRutaSimple(id: Int, completion: @escaping (Result<Ruta, Error>) -> Void) {
        getRuta(id: id) { result in
            switch result {
            case .success(let response):
                completion(.success(response.data))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    func createRuta(_ ruta: RutaRequest, completion: @escaping (Result<RutaResponse, Error>) -> Void) {
        AF.request(
            baseURL + "rutas",
            method: .post,
            parameters: ruta,
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseDecodable(of: RutaResponse.self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    func getVisitasRuta(rutaId: Int, completion: @escaping (Result<[VisitaRuta], Error>) -> Void) {
        AF.request(
            baseURL + "rutas/\(rutaId)/visitas",
            method: .get,
            headers: headers
        ).validate().responseDecodable(of: [VisitaRuta].self) { response in
            switch response.result {
            case .success(let data): completion(.success(data))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Pagos
    func getPagos(completion: @escaping (Result<[Pago], Error>) -> Void) {
        AF.request(
            baseURL + "pagos",
            method: .get,
            headers: headers
        ).validate().responseDecodable(of: PagosResponse.self) { response in
            switch response.result {
            case .success(let data): 
                completion(.success(data.data?.items ?? []))
            case .failure(let error): completion(.failure(error))
            }
        }
    }
    
    // MARK: - Ventas
    func getVentas(completion: @escaping (Result<[Venta], Error>) -> Void) {
        AF.request(
            baseURL + "ventas",
            method: .get,
            parameters: ["per_page": 100],
            headers: headers
        ).validate().responseDecodable(of: VentasResponse.self) { response in
            switch response.result {
            case .success(let data):
                completion(.success(data.data?.items ?? []))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    // MARK: - Compras
    func getCompras(completion: @escaping (Result<[Compra], Error>) -> Void) {
        AF.request(
            baseURL + "compras",
            method: .get,
            parameters: ["per_page": 100],
            headers: headers
        ).validate().responseDecodable(of: ComprasResponse.self) { response in
            switch response.result {
            case .success(let data):
                completion(.success(data.data?.items ?? []))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    // MARK: - Artículos
    func getArticulos(completion: @escaping (Result<[Articulo], Error>) -> Void) {
        AF.request(
            baseURL + "articulos",
            method: .get,
            parameters: ["per_page": 1000],
            headers: headers
        ).validate().responseDecodable(of: ArticulosResponse.self) { response in
            switch response.result {
            case .success(let data):
                completion(.success(data.data?.items ?? []))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
    
    // MARK: - Notificaciones
    func registrarDispositivoPush(token: String, plataforma: String, modelo: String, completion: @escaping (Result<Bool, Error>) -> Void) {
        let parameters: [String: Any] = [
            "token": token,
            "plataforma": plataforma,
            "modelo": modelo
        ]
        
        AF.request(
            baseURL + "notificaciones/registrar-dispositivo",
            method: .post,
            parameters: parameters,
            encoding: JSONEncoding.default,
            headers: headers
        ).validate().responseData { response in
            switch response.result {
            case .success:
                completion(.success(true))
            case .failure(let error):
                completion(.failure(error))
            }
        }
    }
}

