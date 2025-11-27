import Foundation

struct Ruta: Codable, Identifiable {
    let id: Int
    let nombreRuta: String?
    let supervisorId: Int?
    let supervisorNombre: String?
    let cobradorId: Int?
    let cobradorNombre: String?
    let fechaRuta: Date?
    let estado: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case nombreRuta = "nombre_ruta"
        case supervisorId = "supervisor_id"
        case supervisorNombre = "supervisor_nombre"
        case cobradorId = "cobrador_id"
        case cobradorNombre = "cobrador_nombre"
        case fechaRuta = "fecha_ruta"
        case estado
    }
}

struct RutasResponse: Codable {
    let success: Bool
    let data: RutasData?
}

struct RutasData: Codable {
    let items: [Ruta]
}

struct RutaResponse: Codable {
    let success: Bool
    let data: Ruta
}

struct RutaRequest: Codable {
    let nombreRuta: String
    let supervisorId: Int?
    let cobradorId: Int?
    let fechaRuta: String
    
    enum CodingKeys: String, CodingKey {
        case nombreRuta = "nombre_ruta"
        case supervisorId = "supervisor_id"
        case cobradorId = "cobrador_id"
        case fechaRuta = "fecha_ruta"
    }
}

struct VisitaRuta: Codable, Identifiable {
    let id: Int
    let rutaId: Int?
    let prestamoId: Int?
    let clienteId: Int?
    let clienteNombre: String?
    let resultado: String?
    let montoCobrado: Double?
    let latitud: Double?
    let longitud: Double?
    let fechaVisita: Date?
    
    enum CodingKeys: String, CodingKey {
        case id
        case rutaId = "ruta_id"
        case prestamoId = "prestamo_id"
        case clienteId = "cliente_id"
        case clienteNombre = "cliente_nombre"
        case resultado
        case montoCobrado = "monto_cobrado"
        case latitud
        case longitud
        case fechaVisita = "fecha_visita"
    }
}


