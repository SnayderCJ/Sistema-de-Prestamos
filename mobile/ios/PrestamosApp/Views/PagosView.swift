import SwiftUI

struct PagosView: View {
    @StateObject private var viewModel = PagosViewModel()
    @State private var showCreateModal = false
    
    var body: some View {
        NavigationView {
            List {
                ForEach(viewModel.pagos) { pago in
                    PagoRow(pago: pago)
                }
            }
            .navigationTitle("Pagos")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showCreateModal = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showCreateModal) {
                CreatePagoView(viewModel: viewModel)
            }
            .refreshable {
                await viewModel.refreshPagos()
            }
            .onAppear {
                viewModel.loadPagos()
            }
        }
    }
}

struct PagoRow: View {
    let pago: Pago
    @State private var showShareSheet = false
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(pago.numeroRecibo ?? "")
                    .font(.headline)
                Spacer()
                Text(formatCurrency(pago.monto ?? 0))
                    .font(.headline)
                    .foregroundColor(.green)
                Button(action: { showShareSheet = true }) {
                    Image(systemName: "square.and.arrow.up")
                }
            }
            
            Text("Préstamo: \(pago.numeroPrestamo ?? "")")
                .font(.subheadline)
                .foregroundColor(.secondary)
            
            if let nombre = pago.clienteNombre, let apellido = pago.clienteApellido {
                Text("Cliente: \(nombre) \(apellido)")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("Capital: \(formatCurrency(pago.capital ?? 0))")
                Spacer()
                Text("Interés: \(formatCurrency(pago.interes ?? 0))")
            }
            .font(.caption)
            
            Text("Fecha: \(pago.fechaPago ?? "")")
                .font(.caption)
                .foregroundColor(.secondary)
        }
        .padding(.vertical, 4)
        .sheet(isPresented: $showShareSheet) {
            ShareSheet(items: ReciboExporter.compartirRecibo(pago))
        }
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0"
    }
}

struct CreatePagoView: View {
    @ObservedObject var viewModel: PagosViewModel
    @Environment(\.presentationMode) var presentationMode
    
    @State private var prestamoId: Int = 0
    @State private var monto: String = ""
    @State private var metodoPago: String = "efectivo"
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Datos del Pago")) {
                    Picker("Préstamo", selection: $prestamoId) {
                        Text("Seleccionar...").tag(0)
                        // Aquí se cargarían los préstamos
                    }
                    
                    TextField("Monto", text: $monto)
                        .keyboardType(.decimalPad)
                    
                    Picker("Método de Pago", selection: $metodoPago) {
                        Text("Efectivo").tag("efectivo")
                        Text("Transferencia").tag("transferencia")
                        Text("Cheque").tag("cheque")
                        Text("Tarjeta").tag("tarjeta")
                    }
                }
            }
            .navigationTitle("Registrar Pago")
            .navigationBarItems(
                leading: Button("Cancelar") {
                    presentationMode.wrappedValue.dismiss()
                },
                trailing: Button("Registrar") {
                    registrarPago()
                }
            )
        }
    }
    
    private func registrarPago() {
        guard let montoValue = Double(monto),
              montoValue > 0,
              prestamoId > 0 else {
            // Mostrar error de validación
            return
        }
        
        let request = PagoRequest(
            prestamoId: prestamoId,
            monto: montoValue,
            metodoPago: metodoPago
        )
        
        ApiService.shared.registrarPago(request) { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success:
                    self?.viewModel.loadPagos()
                    self?.presentationMode.wrappedValue.dismiss()
                case .failure(let error):
                    print("Error registrando pago: \(error)")
                    // Mostrar alerta de error
                }
            }
        }
    }
}

class PagosViewModel: ObservableObject {
    @Published var pagos: [Pago] = []
    
    func loadPagos() {
        ApiService.shared.getPagos { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success(let pagos):
                    self?.pagos = pagos
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
    
    @MainActor
    func refreshPagos() async {
        await withCheckedContinuation { continuation in
            ApiService.shared.getPagos { [weak self] result in
                DispatchQueue.main.async {
                    switch result {
                    case .success(let pagos):
                        self?.pagos = pagos
                    case .failure(let error):
                        print("Error: \(error)")
                    }
                    continuation.resume()
                }
            }
        }
    }
}


