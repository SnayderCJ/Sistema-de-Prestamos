import SwiftUI
import UserNotifications

@main
struct ImaxPrestamosApp: App {
    @AppStorage("isLoggedIn") private var isLoggedIn = false
    @UIApplicationDelegateAdaptor(AppDelegate.self) var appDelegate
    
    init() {
        // Solicitar permisos de notificaciones al iniciar
        NotificationService.shared.requestAuthorization { granted, error in
            if granted {
                print("✅ Permisos de notificaciones concedidos")
            } else {
                print("❌ Permisos de notificaciones denegados: \(error?.localizedDescription ?? "Desconocido")")
            }
        }
    }
    
    var body: some Scene {
        WindowGroup {
            if isLoggedIn {
                MainTabView()
                    .onAppear {
                        // Registrar dispositivo si hay token pendiente
                        if UserDefaults.standard.bool(forKey: "pending_device_registration"),
                           let token = UserDefaults.standard.string(forKey: "apns_token") {
                            NotificationService.shared.registrarDispositivoEnServidor(token: token)
                        }
                    }
            } else {
                LoginView()
            }
        }
    }
}

// MARK: - AppDelegate para manejar notificaciones remotas
class AppDelegate: NSObject, UIApplicationDelegate {
    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey : Any]? = nil) -> Bool {
        return true
    }
    
    // Manejar registro exitoso para notificaciones remotas
    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        NotificationService.shared.didRegisterForRemoteNotifications(deviceToken: deviceToken)
    }
    
    // Manejar error en registro de notificaciones remotas
    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: Error) {
        NotificationService.shared.didFailToRegisterForRemoteNotifications(error: error)
    }
    
    // Manejar notificación recibida mientras la app está en background
    func application(_ application: UIApplication, didReceiveRemoteNotification userInfo: [AnyHashable : Any], fetchCompletionHandler completionHandler: @escaping (UIBackgroundFetchResult) -> Void) {
        NotificationService.shared.handleRemoteNotification(userInfo: userInfo)
        completionHandler(.newData)
    }
}

