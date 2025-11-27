import SwiftUI

struct DashboardView: View {
    @StateObject private var viewModel = DashboardViewModel()
    
    var body: some View {
        NavigationView {
            ScrollView {
                VStack(spacing: 20) {
                    // Estadísticas
                    HStack(spacing: 15) {
                        StatCard(title: "Préstamos Activos", value: "\(viewModel.stats.prestamosActivos)", color: .blue)
                        StatCard(title: "Monto Total", value: formatCurrency(viewModel.stats.montoTotal), color: .green)
                    }
                    
                    HStack(spacing: 15) {
                        StatCard(title: "Préstamos Vencidos", value: "\(viewModel.stats.prestamosVencidos)", color: .red)
                        StatCard(title: "Cobros Hoy", value: formatCurrency(viewModel.stats.cobrosHoy), color: .orange)
                    }
                    
                    // Acceso Rápido
                    VStack(alignment: .leading, spacing: 10) {
                        Text("Acceso Rápido")
                            .font(.headline)
                            .padding(.horizontal)
                        
                        HStack(spacing: 10) {
                            NavigationLink(destination: VentasView()) {
                                QuickAccessButton(title: "Ventas", icon: "cart.fill", color: .green)
                            }
                            
                            NavigationLink(destination: ComprasView()) {
                                QuickAccessButton(title: "Compras", icon: "bag.fill", color: .blue)
                            }
                            
                            NavigationLink(destination: ArticulosView()) {
                                QuickAccessButton(title: "Artículos", icon: "cube.box.fill", color: .orange)
                            }
                        }
                    }
                    
                    // Préstamos Vencidos
                    if !viewModel.prestamosVencidos.isEmpty {
                        VStack(alignment: .leading, spacing: 10) {
                            Text("Préstamos Vencidos")
                                .font(.headline)
                                .padding(.horizontal)
                            
                            ForEach(viewModel.prestamosVencidos) { prestamo in
                                PrestamoVencidoRow(prestamo: prestamo)
                            }
                        }
                    }
                }
                .padding()
            }
            .navigationTitle("Dashboard")
            .onAppear {
                viewModel.loadDashboard()
            }
        }
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        formatter.currencySymbol = "RD$"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0"
    }
}

struct StatCard: View {
    let title: String
    let value: String
    let color: Color
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(value)
                .font(.title2)
                .fontWeight(.bold)
                .foregroundColor(color)
            Text(title)
                .font(.caption)
                .foregroundColor(.secondary)
        }
        .frame(maxWidth: .infinity)
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }
}

struct QuickAccessButton: View {
    let title: String
    let icon: String
    let color: Color
    
    var body: some View {
        VStack(spacing: 8) {
            Image(systemName: icon)
                .font(.title2)
                .foregroundColor(color)
            Text(title)
                .font(.caption)
                .foregroundColor(.primary)
        }
        .frame(maxWidth: .infinity)
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }
}

struct PrestamoVencidoRow: View {
    let prestamo: PrestamoVencido
    
    var body: some View {
        HStack {
            VStack(alignment: .leading) {
                Text(prestamo.numeroPrestamo)
                    .font(.headline)
                Text(prestamo.clienteNombre)
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }
            Spacer()
            VStack(alignment: .trailing) {
                Text(formatCurrency(prestamo.monto))
                    .font(.headline)
                Text("\(prestamo.diasVencido) días")
                    .font(.caption)
                    .foregroundColor(.red)
            }
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(8)
        .shadow(radius: 2)
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0"
    }
}

struct PrestamoVencido: Identifiable {
    let id: Int
    let numeroPrestamo: String
    let clienteNombre: String
    let monto: Double
    let diasVencido: Int
}

class DashboardViewModel: ObservableObject {
    @Published var stats = DashboardStats()
    @Published var prestamosVencidos: [PrestamoVencido] = []
    
    func loadDashboard() {
        ApiService.shared.getDashboard { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success(let dashboard):
                    self?.stats = DashboardStats(
                        prestamosActivos: dashboard.prestamosActivos ?? 0,
                        montoTotal: dashboard.montoTotal ?? 0,
                        prestamosVencidos: dashboard.prestamosVencidos ?? 0,
                        cobrosHoy: dashboard.cobrosHoy ?? 0
                    )
                    self?.prestamosVencidos = (dashboard.prestamosVencidosList ?? []).map { p in
                        PrestamoVencido(
                            id: p.id,
                            numeroPrestamo: p.numeroPrestamo ?? "",
                            clienteNombre: "\(p.clienteNombre ?? "") \(p.clienteApellido ?? "")",
                            monto: p.monto ?? 0,
                            diasVencido: p.diasVencido ?? 0
                        )
                    }
                case .failure(let error):
                    print("Error loading dashboard: \(error)")
                }
            }
        }
    }
}

struct DashboardStats {
    var prestamosActivos: Int = 0
    var montoTotal: Double = 0
    var prestamosVencidos: Int = 0
    var cobrosHoy: Double = 0
}

