import Foundation

struct Prestamo: Codable, Identifiable {
    let id: Int
    let numeroPrestamo: String?
    let clienteId: Int?
    let clienteNombre: String?
    let clienteApellido: String?
    let montoAprobado: Double?
    let cuotaMensual: Double?
    let estado: String?
    let fechaCreacion: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case numeroPrestamo = "numero_prestamo"
        case clienteId = "cliente_id"
        case clienteNombre = "cliente_nombre"
        case clienteApellido = "cliente_apellido"
        case montoAprobado = "monto_aprobado"
        case cuotaMensual = "cuota_mensual"
        case estado
        case fechaCreacion = "fecha_creacion"
    }
}

struct PrestamosResponse: Codable {
    let success: Bool
    let data: PrestamosData?
}

struct PrestamosData: Codable {
    let items: [Prestamo]
    let pagination: Pagination?
}

struct PrestamoResponse: Codable {
    let success: Bool
    let data: Prestamo
}

struct PrestamoRequest: Codable {
    let clienteId: Int
    let montoSolicitado: Double
    let plazoMeses: Int
    let tasaInteresId: Int
    
    enum CodingKeys: String, CodingKey {
        case clienteId = "cliente_id"
        case montoSolicitado = "monto_solicitado"
        case plazoMeses = "plazo_meses"
        case tasaInteresId = "tasa_interes_id"
    }
}

struct Pagination: Codable {
    let page: Int
    let perPage: Int
    let total: Int
    let totalPages: Int
    
    enum CodingKeys: String, CodingKey {
        case page
        case perPage = "per_page"
        case total
        case totalPages = "total_pages"
    }
}


