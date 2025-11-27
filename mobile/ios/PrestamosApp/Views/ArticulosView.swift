import SwiftUI

struct ArticulosView: View {
    @StateObject private var viewModel = ArticulosViewModel()
    @State private var showingCreateArticulo = false
    @State private var searchText = ""
    
    var filteredArticulos: [Articulo] {
        if searchText.isEmpty {
            return viewModel.articulos
        }
        return viewModel.articulos.filter { articulo in
            articulo.nombre.localizedCaseInsensitiveContains(searchText) ||
            (articulo.codigo?.localizedCaseInsensitiveContains(searchText) ?? false)
        }
    }
    
    var body: some View {
        NavigationView {
            List {
                ForEach(filteredArticulos) { articulo in
                    NavigationLink(destination: ArticuloDetailView(articulo: articulo)) {
                        ArticuloRowView(articulo: articulo)
                    }
                }
            }
            .navigationTitle("Artículos")
            .searchable(text: $searchText, prompt: "Buscar artículos")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showingCreateArticulo = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showingCreateArticulo) {
                CreateArticuloView()
            }
            .onAppear {
                viewModel.loadArticulos()
            }
            .refreshable {
                viewModel.loadArticulos()
            }
        }
    }
}

struct ArticuloRowView: View {
    let articulo: Articulo
    
    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text(articulo.nombre)
                    .font(.headline)
                Spacer()
                Text(formatCurrency(articulo.precioVenta ?? 0))
                    .font(.headline)
                    .foregroundColor(.green)
            }
            
            HStack {
                if let codigo = articulo.codigo {
                    Text(codigo)
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                
                if let categoria = articulo.categoriaNombre {
                    Text("• \(categoria)")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
            }
            
            HStack {
                Text("Stock: \(articulo.stock ?? 0)")
                    .font(.caption)
                    .foregroundColor(stockColor(articulo.stock ?? 0, articulo.stockMinimo ?? 0))
                Spacer()
                if let utilidad = articulo.utilidadPorcentaje {
                    Text("Utilidad: \(String(format: "%.1f", utilidad))%")
                        .font(.caption)
                        .foregroundColor(.secondary)
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
    
    private func stockColor(_ stock: Int, _ minimo: Int) -> Color {
        if stock <= minimo {
            return .red
        } else if stock <= minimo * 2 {
            return .orange
        }
        return .green
    }
}

class ArticulosViewModel: ObservableObject {
    @Published var articulos: [Articulo] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    
    func loadArticulos() {
        isLoading = true
        ApiService.shared.getArticulos { [weak self] result in
            DispatchQueue.main.async {
                self?.isLoading = false
                switch result {
                case .success(let articulos):
                    self?.articulos = articulos
                case .failure(let error):
                    self?.errorMessage = error.localizedDescription
                }
            }
        }
    }
}

struct ArticuloDetailView: View {
    let articulo: Articulo
    @State private var showingEdit = false
    @State private var showingAjustarStock = false
    
    var body: some View {
        Form {
            Section(header: Text("Información General")) {
                if let codigo = articulo.codigo {
                    HStack {
                        Text("Código:")
                        Spacer()
                        Text(codigo)
                            .foregroundColor(.secondary)
                    }
                }
                
                HStack {
                    Text("Nombre:")
                    Spacer()
                    Text(articulo.nombre)
                        .foregroundColor(.secondary)
                }
                
                if let descripcion = articulo.descripcion {
                    HStack {
                        Text("Descripción:")
                        Spacer()
                        Text(descripcion)
                            .foregroundColor(.secondary)
                    }
                }
                
                if let categoria = articulo.categoriaNombre {
                    HStack {
                        Text("Categoría:")
                        Spacer()
                        Text(categoria)
                            .foregroundColor(.secondary)
                    }
                }
            }
            
            Section(header: Text("Precios")) {
                if let precioCompra = articulo.precioCompra {
                    HStack {
                        Text("Precio de Compra:")
                        Spacer()
                        Text(formatCurrency(precioCompra))
                            .foregroundColor(.secondary)
                    }
                }
                
                if let precioVenta = articulo.precioVenta {
                    HStack {
                        Text("Precio de Venta:")
                        Spacer()
                        Text(formatCurrency(precioVenta))
                            .font(.headline)
                            .foregroundColor(.green)
                    }
                }
                
                if let precioCredito = articulo.precioVentaCredito {
                    HStack {
                        Text("Precio Venta Crédito:")
                        Spacer()
                        Text(formatCurrency(precioCredito))
                            .foregroundColor(.secondary)
                    }
                }
            }
            
            Section(header: Text("Inventario")) {
                HStack {
                    Text("Stock:")
                    Spacer()
                    Text("\(articulo.stock ?? 0)")
                        .foregroundColor(stockColor(articulo.stock ?? 0, articulo.stockMinimo ?? 0))
                        .fontWeight(.bold)
                }
                
                if let minimo = articulo.stockMinimo {
                    HStack {
                        Text("Stock Mínimo:")
                        Spacer()
                        Text("\(minimo)")
                            .foregroundColor(.secondary)
                    }
                }
                
                if let utilidad = articulo.utilidadPorcentaje {
                    HStack {
                        Text("Utilidad:")
                        Spacer()
                        Text("\(String(format: "%.1f", utilidad))%")
                            .foregroundColor(.green)
                    }
                }
            }
            
            Section {
                Button(action: { showingEdit = true }) {
                    HStack {
                        Spacer()
                        Text("Editar Artículo")
                        Spacer()
                    }
                }
                
                Button(action: { showingAjustarStock = true }) {
                    HStack {
                        Spacer()
                        Text("Ajustar Stock")
                            .foregroundColor(.orange)
                        Spacer()
                    }
                }
            }
        }
        .navigationTitle("Detalle de Artículo")
        .navigationBarTitleDisplayMode(.inline)
        .sheet(isPresented: $showingEdit) {
            CreateArticuloView()
        }
        .sheet(isPresented: $showingAjustarStock) {
            AjustarStockView(articulo: articulo)
        }
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        formatter.currencySymbol = "RD$"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0.00"
    }
    
    private func stockColor(_ stock: Int, _ minimo: Int) -> Color {
        if stock <= minimo {
            return .red
        } else if stock <= minimo * 2 {
            return .orange
        }
        return .green
    }
}

struct AjustarStockView: View {
    let articulo: Articulo
    @Environment(\.presentationMode) var presentationMode
    @State private var nuevoStock: Int = 0
    @State private var motivo = ""
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Stock Actual")) {
                    Text("\(articulo.stock ?? 0)")
                        .font(.title2)
                        .fontWeight(.bold)
                }
                
                Section(header: Text("Nuevo Stock")) {
                    TextField("Cantidad", value: $nuevoStock, format: .number)
                        .keyboardType(.numberPad)
                    
                    TextField("Motivo del ajuste", text: $motivo)
                }
            }
            .navigationTitle("Ajustar Stock")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancelar") {
                        presentationMode.wrappedValue.dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Guardar") {
                        // Implementar ajuste de stock
                        presentationMode.wrappedValue.dismiss()
                    }
                }
            }
            .onAppear {
                nuevoStock = articulo.stock ?? 0
            }
        }
    }
}

struct CreateArticuloView: View {
    @Environment(\.presentationMode) var presentationMode
    @State private var codigo = ""
    @State private var nombre = ""
    @State private var descripcion = ""
    @State private var categoriaId: Int = 0
    @State private var precioCompra: Double = 0
    @State private var precioVenta: Double = 0
    @State private var precioVentaCredito: Double = 0
    @State private var stock: Int = 0
    @State private var stockMinimo: Int = 0
    @State private var utilidadPorcentaje: Double = 0
    @State private var categorias: [String] = ["General", "Electrónica", "Ropa", "Alimentos"]
    @State private var selectedCategoriaIndex = 0
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Información del Artículo")) {
                    TextField("Código *", text: $codigo)
                    TextField("Nombre *", text: $nombre)
                    TextField("Descripción", text: $descripcion)
                    
                    Picker("Categoría", selection: $selectedCategoriaIndex) {
                        ForEach(0..<categorias.count, id: \.self) { index in
                            Text(categorias[index]).tag(index)
                        }
                    }
                }
                
                Section(header: Text("Precios")) {
                    TextField("Precio de Compra *", value: $precioCompra, format: .number)
                        .keyboardType(.decimalPad)
                    TextField("Precio de Venta *", value: $precioVenta, format: .number)
                        .keyboardType(.decimalPad)
                    TextField("Precio Venta Crédito", value: $precioVentaCredito, format: .number)
                        .keyboardType(.decimalPad)
                }
                
                Section(header: Text("Inventario")) {
                    TextField("Stock Inicial *", value: $stock, format: .number)
                        .keyboardType(.numberPad)
                    TextField("Stock Mínimo *", value: $stockMinimo, format: .number)
                        .keyboardType(.numberPad)
                    TextField("% Utilidad", value: $utilidadPorcentaje, format: .number)
                        .keyboardType(.decimalPad)
                }
            }
            .navigationTitle("Nuevo Artículo")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancelar") {
                        presentationMode.wrappedValue.dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Guardar") {
                        guardarArticulo()
                    }
                }
            }
        }
    }
    
    private func guardarArticulo() {
        guard !codigo.isEmpty, !nombre.isEmpty else {
            return
        }
        
        // Implementar guardar artículo
        presentationMode.wrappedValue.dismiss()
    }
}

