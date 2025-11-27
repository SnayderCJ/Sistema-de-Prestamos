import SwiftUI

struct ClientesView: View {
    @StateObject private var viewModel = ClientesViewModel()
    @State private var searchText = ""
    @State private var showCreateModal = false
    
    var filteredClientes: [Cliente] {
        if searchText.isEmpty {
            return viewModel.clientes
        }
        return viewModel.clientes.filter { cliente in
            (cliente.nombre?.localizedCaseInsensitiveContains(searchText) ?? false) ||
            (cliente.cedula?.localizedCaseInsensitiveContains(searchText) ?? false)
        }
    }
    
    var body: some View {
        NavigationView {
            VStack {
                SearchBar(text: $searchText)
                
                List {
                    ForEach(filteredClientes) { cliente in
                        NavigationLink(destination: ClienteDetailView(clienteId: cliente.id)) {
                            ClienteRow(cliente: cliente)
                        }
                    }
                }
            }
            .navigationTitle("Clientes")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showCreateModal = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showCreateModal) {
                CreateClienteView(viewModel: viewModel)
            }
            .refreshable {
                await viewModel.refreshClientes()
            }
            .onAppear {
                viewModel.loadClientes()
            }
        }
    }
}

struct ClienteRow: View {
    let cliente: Cliente
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("\(cliente.nombre ?? "") \(cliente.apellido ?? "")")
                .font(.headline)
            
            Text("Cédula: \(cliente.cedula ?? "")")
                .font(.subheadline)
                .foregroundColor(.secondary)
            
            if let telefono = cliente.telefono {
                Text("Tel: \(telefono)")
                    .font(.caption)
                    .foregroundColor(.secondary)
            }
        }
        .padding(.vertical, 4)
    }
}

struct ClienteDetailView: View {
    let clienteId: Int
    @State private var cliente: Cliente?
    @State private var isLoading = true
    
    var body: some View {
        ScrollView {
            if isLoading {
                ProgressView()
            } else if let cliente = cliente {
                VStack(alignment: .leading, spacing: 16) {
                    DetailRow(label: "Cédula", value: cliente.cedula ?? "")
                    DetailRow(label: "Nombre", value: "\(cliente.nombre ?? "") \(cliente.apellido ?? "")")
                    DetailRow(label: "Email", value: cliente.email ?? "")
                    DetailRow(label: "Teléfono", value: cliente.telefono ?? "")
                    DetailRow(label: "Dirección", value: cliente.direccion ?? "")
                }
                .padding()
            }
        }
        .navigationTitle("Detalle Cliente")
        .onAppear {
            loadCliente()
        }
    }
    
    private func loadCliente() {
        ApiService.shared.getClienteSimple(id: clienteId) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let c):
                    cliente = c
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
}

struct SearchBar: View {
    @Binding var text: String
    
    var body: some View {
        HStack {
            Image(systemName: "magnifyingglass")
            TextField("Buscar cliente...", text: $text)
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(10)
        .padding(.horizontal)
    }
}

struct CreateClienteView: View {
    @ObservedObject var viewModel: ClientesViewModel
    @Environment(\.presentationMode) var presentationMode
    
    @State private var cedula: String = ""
    @State private var nombre: String = ""
    @State private var apellido: String = ""
    @State private var telefono: String = ""
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Datos del Cliente")) {
                    TextField("Cédula", text: $cedula)
                    TextField("Nombre", text: $nombre)
                    TextField("Apellido", text: $apellido)
                    TextField("Teléfono", text: $telefono)
                        .keyboardType(.phonePad)
                }
            }
            .navigationTitle("Nuevo Cliente")
            .navigationBarItems(
                leading: Button("Cancelar") {
                    presentationMode.wrappedValue.dismiss()
                },
                trailing: Button("Crear") {
                    createCliente()
                }
            )
        }
    }
    
    private func createCliente() {
        guard !cedula.isEmpty,
              !nombre.isEmpty,
              !apellido.isEmpty else {
            // Mostrar error de validación
            return
        }
        
        let request = ClienteRequest(
            cedula: cedula,
            nombre: nombre,
            apellido: apellido,
            telefono: telefono.isEmpty ? nil : telefono
        )
        
        ApiService.shared.createCliente(request) { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success:
                    self?.viewModel.loadClientes()
                    self?.presentationMode.wrappedValue.dismiss()
                case .failure(let error):
                    print("Error creando cliente: \(error)")
                    // Mostrar alerta de error
                }
            }
        }
    }
}

class ClientesViewModel: ObservableObject {
    @Published var clientes: [Cliente] = []
    
    func loadClientes() {
        ApiService.shared.getClientes { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success(let clientes):
                    self?.clientes = clientes
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
    
    @MainActor
    func refreshClientes() async {
        await withCheckedContinuation { continuation in
            ApiService.shared.getClientes { [weak self] result in
                DispatchQueue.main.async {
                    switch result {
                    case .success(let clientes):
                        self?.clientes = clientes
                    case .failure(let error):
                        print("Error: \(error)")
                    }
                    continuation.resume()
                }
            }
        }
    }
}


