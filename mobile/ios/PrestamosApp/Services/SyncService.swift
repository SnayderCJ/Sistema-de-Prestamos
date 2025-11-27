import Foundation
import Alamofire

/**
 * Servicio de sincronización offline para iOS
 */
class SyncService {
    private let offlineHelper = OfflineHelper()
    private let apiService = ApiService.shared
    
    /**
     * Sincronizar todas las acciones pendientes
     */
    func sincronizar() {
        let acciones = offlineHelper.obtenerAccionesPendientes()
        
        if acciones.isEmpty {
            print("SyncService: No hay acciones pendientes")
            return
        }
        
        print("SyncService: Sincronizando \(acciones.count) acciones pendientes")
        
        for accion in acciones {
            do {
                try sincronizarAccion(accion)
                offlineHelper.eliminarAccionPendiente(accion)
            } catch {
                print("SyncService: Error sincronizando acción: \(error.localizedDescription)")
                offlineHelper.incrementarIntentos(accion)
                
                // Si tiene más de 5 intentos, eliminarla
                if accion.intentos >= 5 {
                    offlineHelper.eliminarAccionPendiente(accion)
                }
            }
        }
        
        offlineHelper.guardarUltimaSincronizacion()
    }
    
    /**
     * Sincronizar una acción específica
     */
    private func sincronizarAccion(_ accion: OfflineHelper.PendingAction) throws {
        switch accion.tipo {
        case "crear_prestamo":
            guard let data = accion.datos.data(using: .utf8),
                  let request = try? JSONDecoder().decode(PrestamoRequest.self, from: data) else {
                throw NSError(domain: "SyncService", code: -1, userInfo: [NSLocalizedDescriptionKey: "Error decodificando datos"])
            }
            
            apiService.createPrestamo(request) { result in
                switch result {
                case .success:
                    print("SyncService: Préstamo sincronizado")
                case .failure(let error):
                    throw error
                }
            }
            
        case "crear_cliente":
            guard let data = accion.datos.data(using: .utf8),
                  let request = try? JSONDecoder().decode(ClienteRequest.self, from: data) else {
                throw NSError(domain: "SyncService", code: -1, userInfo: [NSLocalizedDescriptionKey: "Error decodificando datos"])
            }
            
            apiService.createCliente(request) { result in
                switch result {
                case .success:
                    print("SyncService: Cliente sincronizado")
                case .failure(let error):
                    throw error
                }
            }
            
        case "registrar_pago":
            guard let data = accion.datos.data(using: .utf8),
                  let request = try? JSONDecoder().decode(PagoRequest.self, from: data) else {
                throw NSError(domain: "SyncService", code: -1, userInfo: [NSLocalizedDescriptionKey: "Error decodificando datos"])
            }
            
            apiService.registrarPago(request) { result in
                switch result {
                case .success:
                    print("SyncService: Pago sincronizado")
                case .failure(let error):
                    throw error
                }
            }
            
        default:
            print("SyncService: Tipo de acción desconocido: \(accion.tipo)")
        }
    }
    
    /**
     * Verificar conectividad y sincronizar si hay conexión
     */
    func sincronizarSiHayConexion() {
        let manager = NetworkReachabilityManager()
        
        if manager?.isReachable ?? false {
            DispatchQueue.global(qos: .background).async {
                self.sincronizar()
            }
        } else {
            print("SyncService: Sin conexión, no se puede sincronizar")
        }
    }
}

