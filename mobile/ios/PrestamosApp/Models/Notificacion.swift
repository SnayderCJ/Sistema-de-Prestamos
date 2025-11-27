import Foundation

// MARK: - Notificación Response
struct NotificacionResponse: Codable {
    let success: Bool
    let message: String?
    let data: NotificacionData?
}

struct NotificacionData: Codable {
    let dispositivo_id: Int?
}

// MARK: - Notificación Model
struct Notificacion: Codable, Identifiable {
    let id: Int
    let usuario_id: Int
    let titulo: String
    let mensaje: String
    let tipo: String
    let leida: Bool
    let datos: [String: String]?
    let fecha_creacion: String
    
    var fechaCreacion: Date? {
        let formatter = ISO8601DateFormatter()
        return formatter.date(from: fecha_creacion)
    }
}

// MARK: - Notificaciones Response
struct NotificacionesResponse: Codable {
    let success: Bool
    let data: NotificacionesData?
}

struct NotificacionesData: Codable {
    let items: [Notificacion]
    let total: Int
    let page: Int
    let per_page: Int
}

// MARK: - Cantidad No Leídas Response
struct CantidadNoLeidasResponse: Codable {
    let success: Bool
    let data: CantidadNoLeidasData?
}

struct CantidadNoLeidasData: Codable {
    let cantidad: Int
}

