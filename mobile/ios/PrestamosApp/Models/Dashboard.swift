import Foundation

struct DashboardResponse: Codable {
    let success: Bool
    let data: DashboardData?
}

struct DashboardData: Codable {
    let estadisticas: DashboardStats?
    let prestamosVencidos: [PrestamoVencidoData]?
    let graficos: DashboardGraficos?
    
    enum CodingKeys: String, CodingKey {
        case estadisticas
        case prestamosVencidos = "prestamos_vencidos"
        case graficos
    }
}

struct DashboardStats: Codable {
    let prestamosActivos: Int?
    let montoTotal: Double?
    let prestamosVencidos: Int?
    let cobrosHoy: Double?
    
    enum CodingKeys: String, CodingKey {
        case prestamosActivos = "prestamos_activos"
        case montoTotal = "monto_total"
        case prestamosVencidos = "prestamos_vencidos"
        case cobrosHoy = "cobros_hoy"
    }
}

struct PrestamoVencidoData: Codable {
    let id: Int
    let numeroPrestamo: String?
    let clienteNombre: String?
    let clienteApellido: String?
    let monto: Double?
    let diasVencido: Int?
    let moraTotal: Double?
    
    enum CodingKeys: String, CodingKey {
        case id
        case numeroPrestamo = "numero_prestamo"
        case clienteNombre = "cliente_nombre"
        case clienteApellido = "cliente_apellido"
        case monto
        case diasVencido = "dias_vencido"
        case moraTotal = "mora_total"
    }
}

struct DashboardGraficos: Codable {
    // Estructura para gráficos si se implementan
}


