import Foundation

struct Cliente: Codable, Identifiable {
    let id: Int
    let cedula: String?
    let nombre: String?
    let apellido: String?
    let email: String?
    let telefono: String?
    let direccion: String?
    let ciudad: String?
    let provincia: String?
    let ocupacion: String?
    let ingresosMensuales: Double?
    let estadoCredito: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case cedula
        case nombre
        case apellido
        case email
        case telefono
        case direccion
        case ciudad
        case provincia
        case ocupacion
        case ingresosMensuales = "ingresos_mensuales"
        case estadoCredito = "estado_credito"
    }
}

struct ClientesResponse: Codable {
    let success: Bool
    let data: ClientesData?
}

struct ClientesData: Codable {
    let items: [Cliente]
}

struct ClienteResponse: Codable {
    let success: Bool
    let data: Cliente
}

struct ClienteRequest: Codable {
    let cedula: String
    let nombre: String
    let apellido: String
    let email: String?
    let telefono: String?
    let direccion: String?
    let ciudad: String?
    let provincia: String?
    let ocupacion: String?
    let ingresosMensuales: Double?
    
    enum CodingKeys: String, CodingKey {
        case cedula
        case nombre
        case apellido
        case email
        case telefono
        case direccion
        case ciudad
        case provincia
        case ocupacion
        case ingresosMensuales = "ingresos_mensuales"
    }
}


