import SwiftUI
import MapKit

struct RutasView: View {
    @StateObject private var viewModel = RutasViewModel()
    @State private var showCreateModal = false
    
    var body: some View {
        NavigationView {
            List {
                ForEach(viewModel.rutas) { ruta in
                    NavigationLink(destination: RutaDetailView(rutaId: ruta.id)) {
                        RutaRow(ruta: ruta)
                    }
                }
            }
            .navigationTitle("Rutas")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { showCreateModal = true }) {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showCreateModal) {
                CreateRutaView(viewModel: viewModel)
            }
            .refreshable {
                await viewModel.refreshRutas()
            }
            .onAppear {
                viewModel.loadRutas()
            }
        }
    }
}

struct RutaRow: View {
    let ruta: Ruta
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(ruta.nombreRuta ?? "")
                .font(.headline)
            
            HStack {
                Text("Fecha: \(formatDate(ruta.fechaRuta))")
                Spacer()
                StatusBadge(status: ruta.estado ?? "")
            }
            .font(.subheadline)
            .foregroundColor(.secondary)
            
            if let supervisor = ruta.supervisorNombre {
                Text("Supervisor: \(supervisor)")
                    .font(.caption)
                    .foregroundColor(.secondary)
            }
        }
        .padding(.vertical, 4)
    }
    
    private func formatDate(_ date: Date?) -> String {
        guard let date = date else { return "" }
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        return formatter.string(from: date)
    }
}

struct RutaDetailView: View {
    let rutaId: Int
    @State private var ruta: Ruta?
    @State private var visitas: [VisitaRuta] = []
    @State private var isLoading = true
    @State private var region = MKCoordinateRegion(
        center: CLLocationCoordinate2D(latitude: 18.4861, longitude: -69.9312),
        span: MKCoordinateSpan(latitudeDelta: 0.1, longitudeDelta: 0.1)
    )
    
    var body: some View {
        ScrollView {
            if isLoading {
                ProgressView()
            } else {
                VStack(alignment: .leading, spacing: 16) {
                    if let ruta = ruta {
                        DetailRow(label: "Nombre", value: ruta.nombreRuta ?? "")
                        DetailRow(label: "Fecha", value: formatDate(ruta.fechaRuta))
                        DetailRow(label: "Estado", value: ruta.estado ?? "")
                    }
                    
                    Text("Visitas")
                        .font(.headline)
                        .padding(.top)
                    
                    ForEach(visitas) { visita in
                        VisitaRow(visita: visita)
                    }
                    
                    Map(coordinateRegion: $region, annotationItems: visitas) { visita in
                        MapPin(coordinate: CLLocationCoordinate2D(
                            latitude: visita.latitud ?? 0,
                            longitude: visita.longitud ?? 0
                        ))
                    }
                    .frame(height: 200)
                    .cornerRadius(12)
                }
                .padding()
            }
        }
        .navigationTitle("Detalle Ruta")
        .onAppear {
            loadRuta()
        }
    }
    
    private func loadRuta() {
        ApiService.shared.getRutaSimple(id: rutaId) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let r):
                    ruta = r
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
        
        ApiService.shared.getVisitasRuta(rutaId: rutaId) { result in
            DispatchQueue.main.async {
                switch result {
                case .success(let v):
                    visitas = v
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
    
    private func formatDate(_ date: Date?) -> String {
        guard let date = date else { return "" }
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        return formatter.string(from: date)
    }
}

struct VisitaRow: View {
    let visita: VisitaRuta
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(visita.clienteNombre ?? "")
                .font(.headline)
            Text("Resultado: \(visita.resultado ?? "")")
                .font(.subheadline)
                .foregroundColor(.secondary)
            if let monto = visita.montoCobrado {
                Text("Monto: \(formatCurrency(monto))")
                    .font(.caption)
            }
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(8)
    }
    
    private func formatCurrency(_ amount: Double) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "DOP"
        return formatter.string(from: NSNumber(value: amount)) ?? "RD$ 0"
    }
}

struct CreateRutaView: View {
    @ObservedObject var viewModel: RutasViewModel
    @Environment(\.presentationMode) var presentationMode
    
    @State private var nombreRuta: String = ""
    @State private var fechaRuta: Date = Date()
    
    private func formatDateForAPI(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.string(from: date)
    }
    
    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("Datos de la Ruta")) {
                    TextField("Nombre de la ruta", text: $nombreRuta)
                    DatePicker("Fecha", selection: $fechaRuta, displayedComponents: .date)
                }
            }
            .navigationTitle("Nueva Ruta")
            .navigationBarItems(
                leading: Button("Cancelar") {
                    presentationMode.wrappedValue.dismiss()
                },
                trailing: Button("Crear") {
                    createRuta()
                }
            )
        }
    }
    
    private func createRuta() {
        guard !nombreRuta.isEmpty else {
            // Mostrar error de validación
            return
        }
        
        let request = RutaRequest(
            nombreRuta: nombreRuta,
            fechaRuta: formatDateForAPI(fechaRuta)
        )
        
        ApiService.shared.createRuta(request) { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success:
                    self?.viewModel.loadRutas()
                    self?.presentationMode.wrappedValue.dismiss()
                case .failure(let error):
                    print("Error creando ruta: \(error)")
                    // Mostrar alerta de error
                }
            }
        }
    }
}

class RutasViewModel: ObservableObject {
    @Published var rutas: [Ruta] = []
    
    func loadRutas() {
        ApiService.shared.getRutas { [weak self] result in
            DispatchQueue.main.async {
                switch result {
                case .success(let rutas):
                    self?.rutas = rutas
                case .failure(let error):
                    print("Error: \(error)")
                }
            }
        }
    }
    
    @MainActor
    func refreshRutas() async {
        await withCheckedContinuation { continuation in
            ApiService.shared.getRutas { [weak self] result in
                DispatchQueue.main.async {
                    switch result {
                    case .success(let rutas):
                        self?.rutas = rutas
                    case .failure(let error):
                        print("Error: \(error)")
                    }
                    continuation.resume()
                }
            }
        }
    }
}


