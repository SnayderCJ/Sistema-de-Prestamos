import Foundation

struct Pago: Codable, Identifiable {
    let id: Int
    let prestamoId: Int?
    let numeroRecibo: String?
    let numeroPrestamo: String?
    let clienteId: Int?
    let clienteNombre: String?
    let clienteApellido: String?
    let clienteCedula: String?
    let monto: Double?
    let capital: Double?
    let interes: Double?
    let mora: Double?
    let metodoPago: String?
    let fechaPago: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case prestamoId = "prestamo_id"
        case numeroRecibo = "numero_recibo"
        case numeroPrestamo = "numero_prestamo"
        case clienteId = "cliente_id"
        case clienteNombre = "cliente_nombre"
        case clienteApellido = "cliente_apellido"
        case clienteCedula = "cliente_cedula"
        case monto
        case capital
        case interes
        case mora
        case metodoPago = "metodo_pago"
        case fechaPago = "fecha_pago"
    }
}

struct PagosResponse: Codable {
    let success: Bool
    let data: PagosData?
}

struct PagosData: Codable {
    let items: [Pago]
}

struct PagoResponse: Codable {
    let success: Bool
    let data: Pago
}

struct PagoRequest: Codable {
    let prestamoId: Int
    let cuotaId: Int?
    let monto: Double
    let metodoPago: String
    let numeroComprobante: String?
    
    enum CodingKeys: String, CodingKey {
        case prestamoId = "prestamo_id"
        case cuotaId = "cuota_id"
        case monto
        case metodoPago = "metodo_pago"
        case numeroComprobante = "numero_comprobante"
    }
}


