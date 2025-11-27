import SwiftUI

struct VentasView: View {
    @StateObject private var viewModel = VentasViewModel()
    @State private var showingCreateVenta = false
    
    var body: some View {
        NavigationView {
            List {
                ForEach(viewModel.ventas) { venta in
                    NavigationLink(destination: VentaDetailView(venta: venta)) {
                        VentaRowView(venta: venta)
                    }
                }
            }
            .navigationTitle("Ventas")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showingCreateVenta = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showingCreateVenta) {
                CreateVentaView()
            }
            .onAppear {
                viewModel.loadVentas()
            }
            .refreshable {
                viewModel.loadVentas()
            }
        }
    }
}

struct VentaRowView: View {
    let venta: Venta
    
    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text(venta.numeroFactura ?? "N/A")
                    .font(.headline)
                Spacer()
                Text(formatCurrency(venta.montoTotal ?? 0))
                    .font(.headline)
                    .foregroundColor(.green)
            }
            
            if let cliente = venta.clienteNombre {
                Text(cliente)
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }
            
            HStack {
                if let fecha = venta.fecha {
                    Text(formatDate(fecha))
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                Spacer()
                if let estado = venta.estado {
                    Text(estado)
                        .font(.caption)
                        .padding(.horizontal, 8)
                        .padding(.vertical, 4)
                        .background(estadoColor(estado))
                        .cornerRadius(4)
                }
            }
        }
        .padding(.vertical, 4)
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        formatter.currencySymbol = "RD$"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0.00"
    }
    
    private func formatDate(_ dateString: String) -> String {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"
        if let date = formatter.date(from: String(dateString.prefix(10))) {
            formatter.dateFormat = "dd/MM/yyyy"
            return formatter.string(from: date)
        }
        return dateString
    }
    
    private func estadoColor(_ estado: String) -> Color {
        switch estado.lowercased() {
        case "completada", "pagada":
            return .green.opacity(0.2)
        case "pendiente":
            return .orange.opacity(0.2)
        default:
            return .gray.opacity(0.2)
        }
    }
}

class VentasViewModel: ObservableObject {
    @Published var ventas: [Venta] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    
    func loadVentas() {
        isLoading = true
        ApiService.shared.getVentas { [weak self] result in
            DispatchQueue.main.async {
                self?.isLoading = false
                switch result {
                case .success(let ventas):
                    self?.ventas = ventas
                case .failure(let error):
                    self?.errorMessage = error.localizedDescription
                }
            }
        }
    }
}

struct VentaDetailView: View {
    let venta: Venta
    
    var body: some View {
        Form {
            Section(header: Text("Información General")) {
                HStack {
                    Text("Número:")
                    Spacer()
                    Text(venta.numeroFactura ?? "N/A")
                        .foregroundColor(.secondary)
                }
                
                if let cliente = venta.clienteNombre {
                    HStack {
                        Text("Cliente:")
                        Spacer()
                        Text(cliente)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let fecha = venta.fecha {
                    HStack {
                        Text("Fecha:")
                        Spacer()
                        Text(formatDate(fecha))
                            .foregroundColor(.secondary)
                    }
                }
            }
            
            Section(header: Text("Detalles")) {
                HStack {
                    Text("Monto Total:")
                    Spacer()
                    Text(formatCurrency(venta.montoTotal ?? 0))
                        .font(.headline)
                        .foregroundColor(.green)
                }
                
                if let metodo = venta.metodoPago {
                    HStack {
                        Text("Método de Pago:")
                        Spacer()
                        Text(metodo)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let estado = venta.estado {
                    HStack {
                        Text("Estado:")
                        Spacer()
                        Text(estado)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let descuento = venta.descuento, descuento > 0 {
                    HStack {
                        Text("Descuento:")
                        Spacer()
                        Text(formatCurrency(descuento))
                            .foregroundColor(.secondary)
                    }
                }
            }
            
            Section {
                Button(action: {
                    // Imprimir factura
                }) {
                    HStack {
                        Spacer()
                        Label("Imprimir Factura", systemImage: "printer.fill")
                        Spacer()
                    }
                }
                
                Button(action: {
                    // Enviar por email
                }) {
                    HStack {
                        Spacer()
                        Label("Enviar por Email", systemImage: "envelope.fill")
                        Spacer()
                    }
                }
            }
        }
        .navigationTitle("Detalle de Venta")
        .navigationBarTitleDisplayMode(.inline)
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        formatter.currencySymbol = "RD$"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0.00"
    }
    
    private func formatDate(_ dateString: String) -> String {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"
        if let date = formatter.date(from: String(dateString.prefix(10))) {
            formatter.dateFormat = "dd/MM/yyyy"
            return formatter.string(from: date)
        }
        return dateString
    }
}

struct CreateVentaView: View {
    @Environment(\.presentationMode) var presentationMode
    @State private var clienteId: Int = 0
    @State private var fecha = Date()
    @State private var metodoPago = "efectivo"
    @State private var descuento: Double = 0
    @State private var clientes: [Cliente] = []
    @State private var selectedClienteIndex = 0
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Información de Venta")) {
                    Picker("Cliente", selection: $selectedClienteIndex) {
                        ForEach(0..<clientes.count, id: \.self) { index in
                            Text("\(clientes[index].nombre ?? "") \(clientes[index].apellido ?? "")").tag(index)
                        }
                    }
                    
                    DatePicker("Fecha", selection: $fecha, displayedComponents: .date)
                    
                    Picker("Método de Pago", selection: $metodoPago) {
                        Text("Efectivo").tag("efectivo")
                        Text("Tarjeta").tag("tarjeta")
                        Text("Transferencia").tag("transferencia")
                        Text("Cheque").tag("cheque")
                    }
                }
                
                Section(header: Text("Artículos")) {
                    Text("Agregar artículos próximamente")
                        .foregroundColor(.secondary)
                }
                
                Section(header: Text("Descuento")) {
                    TextField("Descuento", value: $descuento, format: .number)
                        .keyboardType(.decimalPad)
                }
            }
            .navigationTitle("Nueva Venta")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancelar") {
                        presentationMode.wrappedValue.dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Guardar") {
                        guardarVenta()
                    }
                }
            }
            .onAppear {
                cargarClientes()
            }
        }
    }
    
    private func cargarClientes() {
        ApiService.shared.getClientes { result in
            switch result {
            case .success(let clientesList):
                DispatchQueue.main.async {
                    self.clientes = clientesList
                }
            case .failure(let error):
                print("Error cargando clientes: \(error)")
            }
        }
    }
    
    private func guardarVenta() {
        // Implementar guardar venta
        presentationMode.wrappedValue.dismiss()
    }
}

