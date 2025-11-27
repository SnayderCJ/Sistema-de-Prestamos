import Foundation
import UserNotifications
import UIKit

class NotificationService: NSObject, UNUserNotificationCenterDelegate {
    static let shared = NotificationService()
    
    private override init() {
        super.init()
    }
    
    // MARK: - Solicitar Permisos
    func requestAuthorization(completion: @escaping (Bool, Error?) -> Void) {
        UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .sound, .badge]) { granted, error in
            DispatchQueue.main.async {
                if granted {
                    self.registerForRemoteNotifications()
                }
                completion(granted, error)
            }
        }
        
        // Configurar el delegate
        UNUserNotificationCenter.current().delegate = self
    }
    
    // MARK: - Registrar para Notificaciones Remotas
    private func registerForRemoteNotifications() {
        DispatchQueue.main.async {
            UIApplication.shared.registerForRemoteNotifications()
        }
    }
    
    // MARK: - Manejar Token de Dispositivo
    func didRegisterForRemoteNotifications(deviceToken: Data) {
        let tokenParts = deviceToken.map { data in String(format: "%02.2hhx", data) }
        let token = tokenParts.joined()
        
        print("📱 APNs Token: \(token)")
        
        // Guardar token localmente
        UserDefaults.standard.set(token, forKey: "apns_token")
        
        // Enviar token al servidor si el usuario está autenticado
        if KeychainHelper.getToken() != nil {
            registrarDispositivoEnServidor(token: token)
        } else {
            // Guardar para enviar después del login
            UserDefaults.standard.set(true, forKey: "pending_device_registration")
        }
    }
    
    // MARK: - Manejar Error de Registro
    func didFailToRegisterForRemoteNotifications(error: Error) {
        print("❌ Error registrando para notificaciones remotas: \(error.localizedDescription)")
    }
    
    // MARK: - Registrar Dispositivo en Servidor
    func registrarDispositivoEnServidor(token: String) {
        guard let authToken = KeychainHelper.getToken() else {
            print("⚠️ No hay token de autenticación")
            return
        }
        
        let modelo = UIDevice.current.model
        let nombreModelo = UIDevice.current.name
        
        let request: [String: Any] = [
            "token": token,
            "plataforma": "ios",
            "modelo": "\(modelo) - \(nombreModelo)"
        ]
        
        ApiService.shared.registrarDispositivoPush(token: token, plataforma: "ios", modelo: "\(modelo) - \(nombreModelo)") { result in
            switch result {
            case .success:
                print("✅ Dispositivo registrado correctamente en el servidor")
                UserDefaults.standard.set(false, forKey: "pending_device_registration")
            case .failure(let error):
                print("❌ Error registrando dispositivo: \(error.localizedDescription)")
            }
        }
    }
    
    // MARK: - UNUserNotificationCenterDelegate
    
    // Notificación recibida mientras la app está en primer plano
    func userNotificationCenter(_ center: UNUserNotificationCenter, willPresent notification: UNNotification, withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void) {
        // Mostrar notificación incluso si la app está en primer plano
        if #available(iOS 14.0, *) {
            completionHandler([.banner, .sound, .badge])
        } else {
            completionHandler([.alert, .sound, .badge])
        }
    }
    
    // Usuario tocó la notificación
    func userNotificationCenter(_ center: UNUserNotificationCenter, didReceive response: UNNotificationResponse, withCompletionHandler completionHandler: @escaping () -> Void) {
        let userInfo = response.notification.request.content.userInfo
        
        // Manejar datos de la notificación
        if let prestamoId = userInfo["prestamo_id"] as? String {
            // Navegar al préstamo específico
            NotificationCenter.default.post(
                name: NSNotification.Name("NavegarAPrestamo"),
                object: nil,
                userInfo: ["prestamo_id": prestamoId]
            )
        }
        
        completionHandler()
    }
    
    // MARK: - Manejar Notificación Remota
    func handleRemoteNotification(userInfo: [AnyHashable: Any]) {
        print("📬 Notificación remota recibida: \(userInfo)")
        
        // Crear notificación local si es necesario
        let content = UNMutableNotificationContent()
        
        if let title = userInfo["title"] as? String {
            content.title = title
        } else {
            content.title = "ERP Prestamos"
        }
        
        if let body = userInfo["body"] as? String {
            content.body = body
        } else if let alert = userInfo["alert"] as? String {
            content.body = alert
        }
        
        content.sound = .default
        content.badge = 1
        
        // Agregar datos personalizados
        if let prestamoId = userInfo["prestamo_id"] as? String {
            content.userInfo = ["prestamo_id": prestamoId]
        }
        
        let request = UNNotificationRequest(
            identifier: UUID().uuidString,
            content: content,
            trigger: nil
        )
        
        UNUserNotificationCenter.current().add(request) { error in
            if let error = error {
                print("❌ Error mostrando notificación: \(error.localizedDescription)")
            }
        }
    }
}

