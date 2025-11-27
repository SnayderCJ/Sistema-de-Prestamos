import Foundation

struct Compra: Codable, Identifiable {
    let id: Int
    let numeroFactura: String?
    let proveedorId: Int?
    let proveedorNombre: String?
    let fecha: String?
    let montoTotal: Double?
    let metodoPago: String?
    let estado: String?
    let descuento: Double?
    let impuesto: Double?
    
    enum CodingKeys: String, CodingKey {
        case id
        case numeroFactura = "numero_factura"
        case proveedorId = "proveedor_id"
        case proveedorNombre = "proveedor_nombre"
        case fecha
        case montoTotal = "monto_total"
        case metodoPago = "metodo_pago"
        case estado
        case descuento
        case impuesto
    }
}

struct ComprasResponse: Codable {
    let success: Bool
    let data: ComprasData?
}

struct ComprasData: Codable {
    let items: [Compra]
    let total: Int?
}

struct CompraResponse: Codable {
    let success: Bool
    let data: Compra
}

