import Foundation

struct Articulo: Codable, Identifiable {
    let id: Int
    let codigo: String?
    let nombre: String
    let descripcion: String?
    let categoriaId: Int?
    let categoriaNombre: String?
    let precioCompra: Double?
    let precioVenta: Double?
    let precioVentaCredito: Double?
    let stock: Int?
    let stockMinimo: Int?
    let utilidadPorcentaje: Double?
    let activo: Bool?
    
    enum CodingKeys: String, CodingKey {
        case id
        case codigo
        case nombre
        case descripcion
        case categoriaId = "categoria_id"
        case categoriaNombre = "categoria_nombre"
        case precioCompra = "precio_compra"
        case precioVenta = "precio_venta"
        case precioVentaCredito = "precio_venta_credito"
        case stock
        case stockMinimo = "stock_minimo"
        case utilidadPorcentaje = "utilidad_porcentaje"
        case activo
    }
}

struct ArticulosResponse: Codable {
    let success: Bool
    let data: ArticulosData?
}

struct ArticulosData: Codable {
    let items: [Articulo]
    let total: Int?
}

struct ArticuloResponse: Codable {
    let success: Bool
    let data: Articulo
}

