import Foundation

struct ConsultaResponse: Codable {
    let success: Bool
    let data: ConsultaData?
}

struct ConsultaData: Codable {
    let cedula: String?
    let nombre: String?
    let apellido: String?
    let fechaNacimiento: String?
    let lugarNacimiento: String?
    let nacionalidad: String?
    let estadoCivil: String?
    
    enum CodingKeys: String, CodingKey {
        case cedula
        case nombre
        case apellido
        case fechaNacimiento = "fecha_nacimiento"
        case lugarNacimiento = "lugar_nacimiento"
        case nacionalidad
        case estadoCivil = "estado_civil"
    }
}

struct DataCreditosResponse: Codable {
    let success: Bool
    let data: DataCreditosData?
}

struct DataCreditosData: Codable {
    let cedula: String?
    let score: Int?
    let historial: [HistorialCredito]?
    let deudas: [Deuda]?
}

struct HistorialCredito: Codable {
    let fecha: String?
    let tipo: String?
    let monto: Double?
    let estado: String?
}

struct Deuda: Codable {
    let institucion: String?
    let monto: Double?
    let estado: String?
    let fecha: String?
}


