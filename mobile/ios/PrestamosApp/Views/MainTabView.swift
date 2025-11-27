import SwiftUI

struct MainTabView: View {
    var body: some View {
        TabView {
            DashboardView()
                .tabItem {
                    Image(systemName: "chart.bar.fill")
                    Text("Dashboard")
                }
            
            PrestamosView()
                .tabItem {
                    Image(systemName: "dollarsign.circle.fill")
                    Text("Préstamos")
                }
            
            ClientesView()
                .tabItem {
                    Image(systemName: "person.2.fill")
                    Text("Clientes")
                }
            
            RutasView()
                .tabItem {
                    Image(systemName: "map.fill")
                    Text("Rutas")
                }
            
            PagosView()
                .tabItem {
                    Image(systemName: "creditcard.fill")
                    Text("Pagos")
                }
            
            VentasView()
                .tabItem {
                    Image(systemName: "cart.fill")
                    Text("Ventas")
                }
            
            ComprasView()
                .tabItem {
                    Image(systemName: "bag.fill")
                    Text("Compras")
                }
            
            ArticulosView()
                .tabItem {
                    Image(systemName: "cube.box.fill")
                    Text("Artículos")
                }
        }
    }
}


