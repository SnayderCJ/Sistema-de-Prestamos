import SwiftUI

struct ComprasView: View {
    @StateObject private var viewModel = ComprasViewModel()
    @State private var showingCreateCompra = false
    
    var body: some View {
        NavigationView {
            List {
                ForEach(viewModel.compras) { compra in
                    NavigationLink(destination: CompraDetailView(compra: compra)) {
                        CompraRowView(compra: compra)
                    }
                }
            }
            .navigationTitle("Compras")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showingCreateCompra = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showingCreateCompra) {
                CreateCompraView()
            }
            .onAppear {
                viewModel.loadCompras()
            }
            .refreshable {
                viewModel.loadCompras()
            }
        }
    }
}

struct CompraRowView: View {
    let compra: Compra
    
    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text(compra.numeroFactura ?? "N/A")
                    .font(.headline)
                Spacer()
                Text(formatCurrency(compra.montoTotal ?? 0))
                    .font(.headline)
                    .foregroundColor(.blue)
            }
            
            if let proveedor = compra.proveedorNombre {
                Text(proveedor)
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }
            
            HStack {
                if let fecha = compra.fecha {
                    Text(formatDate(fecha))
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                Spacer()
                if let estado = compra.estado {
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

class ComprasViewModel: ObservableObject {
    @Published var compras: [Compra] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    
    func loadCompras() {
        isLoading = true
        ApiService.shared.getCompras { [weak self] result in
            DispatchQueue.main.async {
                self?.isLoading = false
                switch result {
                case .success(let compras):
                    self?.compras = compras
                case .failure(let error):
                    self?.errorMessage = error.localizedDescription
                }
            }
        }
    }
}

struct CompraDetailView: View {
    let compra: Compra
    
    var body: some View {
        Form {
            Section(header: Text("Información General")) {
                HStack {
                    Text("Número:")
                    Spacer()
                    Text(compra.numeroFactura ?? "N/A")
                        .foregroundColor(.secondary)
                }
                
                if let proveedor = compra.proveedorNombre {
                    HStack {
                        Text("Proveedor:")
                        Spacer()
                        Text(proveedor)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let fecha = compra.fecha {
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
                    Text(formatCurrency(compra.montoTotal ?? 0))
                        .font(.headline)
                        .foregroundColor(.blue)
                }
                
                if let metodo = compra.metodoPago {
                    HStack {
                        Text("Método de Pago:")
                        Spacer()
                        Text(metodo)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let estado = compra.estado {
                    HStack {
                        Text("Estado:")
                        Spacer()
                        Text(estado)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let descuento = compra.descuento, descuento > 0 {
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
                    // Imprimir compra
                }) {
                    HStack {
                        Spacer()
                        Label("Imprimir Compra", systemImage: "printer.fill")
                        Spacer()
                    }
                }
            }
        }
        .navigationTitle("Detalle de Compra")
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

struct CreateCompraView: View {
    @Environment(\.presentationMode) var presentationMode
    @State private var proveedorId: Int = 0
    @State private var fecha = Date()
    @State private var metodoPago = "efectivo"
    @State private var descuento: Double = 0
    @State private var proveedores: [String] = ["Proveedor 1", "Proveedor 2", "Proveedor 3"]
    @State private var selectedProveedorIndex = 0
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Información de Compra")) {
                    Picker("Proveedor", selection: $selectedProveedorIndex) {
                        ForEach(0..<proveedores.count, id: \.self) { index in
                            Text(proveedores[index]).tag(index)
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
            .navigationTitle("Nueva Compra")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancelar") {
                        presentationMode.wrappedValue.dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Guardar") {
                        guardarCompra()
                    }
                }
            }
        }
    }
    
    private func guardarCompra() {
        // Implementar guardar compra
        presentationMode.wrappedValue.dismiss()
    }
}

