import Foundation

struct Venta: Codable, Identifiable {
    let id: Int
    let numeroFactura: String?
    let clienteId: Int?
    let clienteNombre: String?
    let fecha: String?
    let montoTotal: Double?
    let metodoPago: String?
    let estado: String?
    let descuento: Double?
    let impuesto: Double?
    
    enum CodingKeys: String, CodingKey {
        case id
        case numeroFactura = "numero_factura"
        case clienteId = "cliente_id"
        case clienteNombre = "cliente_nombre"
        case fecha
        case montoTotal = "monto_total"
        case metodoPago = "metodo_pago"
        case estado
        case descuento
        case impuesto
    }
}

struct VentasResponse: Codable {
    let success: Bool
    let data: VentasData?
}

struct VentasData: Codable {
    let items: [Venta]
    let total: Int?
}

struct VentaResponse: Codable {
    let success: Bool
    let data: Venta
}

