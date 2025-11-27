import SwiftUI

struct PrestamosView: View {
    @StateObject private var viewModel = PrestamosViewModel()
    @State private var showCreateModal = false
    
    var body: some View {
        NavigationView {
            List {
                ForEach(viewModel.prestamos) { prestamo in
                    NavigationLink(destination: PrestamoDetailView(prestamoId: prestamo.id)) {
                        PrestamoRow(prestamo: prestamo)
                    }
                }
            }
            .refreshable {
                await viewModel.refreshPrestamos()
            }
            .navigationTitle("Préstamos")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showCreateModal = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showCreateModal) {
                CreatePrestamoView(viewModel: viewModel)
            }
            .onAppear {
                viewModel.loadPrestamos()
            }
        }
    }
}

struct PrestamoRow: View {
    let prestamo: Prestamo
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(prestamo.numeroPrestamo ?? "")
                    .font(.headline)
                Spacer()
                StatusBadge(status: prestamo.estado ?? "")
            }
            
            Text("\(prestamo.clienteNombre ?? "") \(prestamo.clienteApellido ?? "")")
                .font(.subheadline)
                .foregroundColor(.secondary)
            
            HStack {
                Text("Monto: \(formatCurrency(prestamo.montoAprobado ?? 0))")
                Spacer()
                Text("Cuota: \(formatCurrency(prestamo.cuotaMensual ?? 0))")
            }
            .font(.caption)
        }
        .padding(.vertical, 4)
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0"
    }
}

struct StatusBadge: View {
    let status: String
    
    var body: some View {
        Text(status)
            .font(.caption)
            .padding(.horizontal, 8)
            .padding(.vertical, 4)
            .background(statusColor)
            .foregroundColor(.white)
            .cornerRadius(8)
    }
    
    var statusColor: Color {
        switch status.lowercased() {
        case "vigente", "aprobado":
            return .green
        case "vencido":
            return .red
        case "pendiente":
            return .orange
        default:
            return .gray
        }
    }
}

struct PrestamoDetailView: View {
    let prestamoId: Int
    @State private var prestamo: Prestamo?
    @State private var isLoading = true
    
    var body: some View {
        ScrollView {
            if isLoading {
                ProgressView()
            } else if let prestamo = prestamo {
                VStack(alignment: .leading, spacing: 16) {
                    DetailRow(label: "Número", value: prestamo.numeroPrestamo ?? "")
                    DetailRow(label: "Cliente", value: "\(prestamo.clienteNombre ?? "") \(prestamo.clienteApellido ?? "")")
                    DetailRow(label: "Monto", value: formatCurrency(prestamo.montoAprobado ?? 0))
                    DetailRow(label: "Cuota Mensual", value: formatCurrency(prestamo.cuotaMensual ?? 0))
                    DetailRow(label: "Estado", value: prestamo.estado ?? "")
                }
                .padding()
            }
        }
        .navigationTitle("Detalle Préstamo")
        .onAppear {
            loadPrestamo()
        }
    }
    
    private func loadPrestamo() {
        ApiService.shared.getPrestamoSimple(id: prestamoId) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let p):
                    prestamo = p
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0"
    }
}

struct DetailRow: View {
    let label: String
    let value: String
    
    var body: some View {
        HStack {
            Text(label)
                .font(.headline)
            Spacer()
            Text(value)
                .foregroundColor(.secondary)
        }
    }
}

struct CreatePrestamoView: View {
    @ObservedObject var viewModel: PrestamosViewModel
    @Environment(\.presentationMode) var presentationMode
    
    @State private var clienteId: Int = 0
    @State private var monto: String = ""
    @State private var plazo: String = ""
    @State private var tasaId: Int = 0
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Datos del Préstamo")) {
                    TextField("Monto", text: $monto)
                        .keyboardType(.decimalPad)
                    TextField("Plazo (meses)", text: $plazo)
                        .keyboardType(.numberPad)
                }
            }
            .navigationTitle("Nuevo Préstamo")
            .navigationBarItems(
                leading: Button("Cancelar") {
                    presentationMode.wrappedValue.dismiss()
                },
                trailing: Button("Crear") {
                    createPrestamo()
                }
            )
        }
    }
    
    private func createPrestamo() {
        guard let montoValue = Double(monto),
              let plazoValue = Int(plazo),
              montoValue > 0,
              plazoValue > 0,
              clienteId > 0,
              tasaId > 0 else {
            // Mostrar error de validación
            return
        }
        
        let request = PrestamoRequest(
            clienteId: clienteId,
            montoSolicitado: montoValue,
            plazoMeses: plazoValue,
            tasaInteresId: tasaId
        )
        
        ApiService.shared.createPrestamo(request) { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success:
                    self?.viewModel.loadPrestamos()
                    self?.presentationMode.wrappedValue.dismiss()
                case .failure(let error):
                    print("Error creando préstamo: \(error)")
                    // Mostrar alerta de error
                }
            }
        }
    }
}

class PrestamosViewModel: ObservableObject {
    @Published var prestamos: [Prestamo] = []
    
    func loadPrestamos() {
        ApiService.shared.getPrestamosSimple { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success(let prestamos):
                    self?.prestamos = prestamos
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
    
    @MainActor
    func refreshPrestamos() async {
        await withCheckedContinuation { continuation in
            ApiService.shared.getPrestamosSimple { [weak self] result in
                DispatchQueue.main.async {
                    switch result {
                    case .success(let prestamos):
                        self?.prestamos = prestamos
                    case .failure(let error):
                        print("Error: \(error)")
                    }
                    continuation.resume()
                }
            }
        }
    }
}


