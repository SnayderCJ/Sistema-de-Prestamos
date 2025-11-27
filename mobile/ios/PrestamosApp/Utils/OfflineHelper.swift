import Foundation

/**
 * Helper para manejo de datos offline en iOS
 */
class OfflineHelper {
    private let userDefaults = UserDefaults.standard
    private let keyPendingActions = "offline_pending_actions"
    private let keyLastSync = "offline_last_sync"
    
    struct PendingAction: Codable, Equatable {
        let tipo: String
        let datos: String
        let timestamp: TimeInterval
        var intentos: Int
    }
    
    /**
     * Guardar acción pendiente para sincronizar
     */
    func guardarAccionPendiente(tipo: String, datos: Codable) {
        var acciones = obtenerAccionesPendientes()
        
        let datosJson: String
        if let jsonData = try? JSONEncoder().encode(datos),
           let jsonString = String(data: jsonData, encoding: .utf8) {
            datosJson = jsonString
        } else {
            return
        }
        
        let accion = PendingAction(
            tipo: tipo,
            datos: datosJson,
            timestamp: Date().timeIntervalSince1970,
            intentos: 0
        )
        
        acciones.append(accion)
        guardarAcciones(acciones)
    }
    
    /**
     * Obtener acciones pendientes
     */
    func obtenerAccionesPendientes() -> [PendingAction] {
        guard let data = userDefaults.data(forKey: keyPendingActions),
              let acciones = try? JSONDecoder().decode([PendingAction].self, from: data) else {
            return []
        }
        return acciones
    }
    
    /**
     * Eliminar acción pendiente
     */
    func eliminarAccionPendiente(_ accion: PendingAction) {
        var acciones = obtenerAccionesPendientes()
        acciones.removeAll { $0 == accion }
        guardarAcciones(acciones)
    }
    
    /**
     * Incrementar intentos de una acción
     */
    func incrementarIntentos(_ accion: PendingAction) {
        var acciones = obtenerAccionesPendientes()
        if let index = acciones.firstIndex(of: accion) {
            acciones[index].intentos += 1
        }
        guardarAcciones(acciones)
    }
    
    /**
     * Guardar acciones
     */
    private func guardarAcciones(_ acciones: [PendingAction]) {
        if let data = try? JSONEncoder().encode(acciones) {
            userDefaults.set(data, forKey: keyPendingActions)
        }
    }
    
    /**
     * Guardar última sincronización
     */
    func guardarUltimaSincronizacion() {
        userDefaults.set(Date().timeIntervalSince1970, forKey: keyLastSync)
    }
    
    /**
     * Obtener última sincronización
     */
    func obtenerUltimaSincronizacion() -> TimeInterval {
        return userDefaults.double(forKey: keyLastSync)
    }
    
    /**
     * Verificar si hay datos pendientes
     */
    func hayDatosPendientes() -> Bool {
        return !obtenerAccionesPendientes().isEmpty
    }
    
    /**
     * Limpiar acciones antiguas (más de 7 días)
     */
    func limpiarAccionesAntiguas() {
        let sieteDiasAtras = Date().timeIntervalSince1970 - (7 * 24 * 60 * 60)
        let acciones = obtenerAccionesPendientes()
        let accionesValidas = acciones.filter { $0.timestamp > sieteDiasAtras }
        guardarAcciones(accionesValidas)
    }
}

