package com.erp.prestamos.services;

import android.content.Context;
import com.erp.prestamos.models.*;
import com.erp.prestamos.ApiClient;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class ApiServiceHelper {
    private static ApiServiceHelper instance;
    private com.erp.prestamos.services.ApiService apiService;
    private Context context;
    
    private ApiServiceHelper(Context context) {
        this.context = context;
        this.apiService = com.erp.prestamos.ApiClient.getClient().create(com.erp.prestamos.services.ApiService.class);
    }
    
    public static synchronized ApiServiceHelper getInstance(Context context) {
        if (instance == null) {
            instance = new ApiServiceHelper(context.getApplicationContext());
        }
        return instance;
    }
    
    // Login
    public interface LoginCallback {
        void onSuccess(AuthResponse.AuthData authData);
        void onError(String error);
    }
    
    public void login(LoginRequest request, LoginCallback callback) {
        apiService.login(request).enqueue(new retrofit2.Callback<AuthResponse>() {
            @Override
            public void onResponse(retrofit2.Call<AuthResponse> call, retrofit2.Response<AuthResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Credenciales inválidas");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<AuthResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Ruta individual
    public interface RutaCallback {
        void onSuccess(Ruta ruta);
        void onError(String error);
    }
    
    public void getRuta(int id, RutaCallback callback) {
        apiService.getRuta(id).enqueue(new retrofit2.Callback<RutaResponse>() {
            @Override
            public void onResponse(retrofit2.Call<RutaResponse> call, retrofit2.Response<RutaResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al cargar ruta");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<RutaResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Dashboard
    public interface DashboardCallback {
        void onSuccess(DashboardStats stats, List<Prestamo> prestamosVencidos);
        void onError(String error);
    }
    
    public void getDashboard(DashboardCallback callback) {
        apiService.getDashboard().enqueue(new retrofit2.Callback<DashboardResponse>() {
            @Override
            public void onResponse(retrofit2.Call<DashboardResponse> call, retrofit2.Response<DashboardResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    DashboardResponse data = response.body();
                    DashboardStats stats = data.getData().getEstadisticas();
                    List<Prestamo> prestamos = data.getData().getPrestamosVencidos();
                    callback.onSuccess(stats, prestamos);
                } else {
                    callback.onError("Error al cargar dashboard");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<DashboardResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Préstamos
    public interface PrestamosCallback {
        void onSuccess(List<Prestamo> prestamos);
        void onError(String error);
    }
    
    public void getPrestamos(PrestamosCallback callback) {
        Map<String, String> params = new HashMap<>();
        params.put("per_page", "100");
        
        apiService.getPrestamos(params).enqueue(new retrofit2.Callback<PrestamosResponse>() {
            @Override
            public void onResponse(retrofit2.Call<PrestamosResponse> call, retrofit2.Response<PrestamosResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar préstamos");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<PrestamosResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Clientes
    public interface ClientesCallback {
        void onSuccess(List<Cliente> clientes);
        void onError(String error);
    }
    
    public void getClientes(ClientesCallback callback) {
        Map<String, String> params = new HashMap<>();
        params.put("per_page", "1000");
        
        apiService.getClientes(params).enqueue(new retrofit2.Callback<ClientesResponse>() {
            @Override
            public void onResponse(retrofit2.Call<ClientesResponse> call, retrofit2.Response<ClientesResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar clientes");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<ClientesResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Rutas
    public interface RutasCallback {
        void onSuccess(List<Ruta> rutas);
        void onError(String error);
    }
    
    public void getRutas(RutasCallback callback) {
        Map<String, String> params = new HashMap<>();
        
        apiService.getRutas(params).enqueue(new retrofit2.Callback<RutasResponse>() {
            @Override
            public void onResponse(retrofit2.Call<RutasResponse> call, retrofit2.Response<RutasResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar rutas");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<RutasResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Pagos
    public interface PagosCallback {
        void onSuccess(List<Pago> pagos);
        void onError(String error);
    }
    
    public void getPagos(PagosCallback callback) {
        Map<String, String> params = new HashMap<>();
        
        apiService.getPagos(params).enqueue(new retrofit2.Callback<PagosResponse>() {
            @Override
            public void onResponse(retrofit2.Call<PagosResponse> call, retrofit2.Response<PagosResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar pagos");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<PagosResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Préstamo individual
    public interface PrestamoCallback {
        void onSuccess(Prestamo prestamo);
        void onError(String error);
    }
    
    public void getPrestamo(int id, PrestamoCallback callback) {
        apiService.getPrestamo(id).enqueue(new retrofit2.Callback<PrestamoResponse>() {
            @Override
            public void onResponse(retrofit2.Call<PrestamoResponse> call, retrofit2.Response<PrestamoResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al cargar préstamo");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<PrestamoResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Crear préstamo
    public interface CreatePrestamoCallback {
        void onSuccess(Prestamo prestamo);
        void onError(String error);
    }
    
    public void createPrestamo(PrestamoRequest request, CreatePrestamoCallback callback) {
        apiService.createPrestamo(request).enqueue(new retrofit2.Callback<PrestamoResponse>() {
            @Override
            public void onResponse(retrofit2.Call<PrestamoResponse> call, retrofit2.Response<PrestamoResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al crear préstamo");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<PrestamoResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Cliente individual
    public interface ClienteCallback {
        void onSuccess(Cliente cliente);
        void onError(String error);
    }
    
    public void getCliente(int id, ClienteCallback callback) {
        apiService.getCliente(id).enqueue(new retrofit2.Callback<ClienteResponse>() {
            @Override
            public void onResponse(retrofit2.Call<ClienteResponse> call, retrofit2.Response<ClienteResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al cargar cliente");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<ClienteResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Crear cliente
    public interface CreateClienteCallback {
        void onSuccess(Cliente cliente);
        void onError(String error);
    }
    
    public void createCliente(ClienteRequest request, CreateClienteCallback callback) {
        apiService.createCliente(request).enqueue(new retrofit2.Callback<ClienteResponse>() {
            @Override
            public void onResponse(retrofit2.Call<ClienteResponse> call, retrofit2.Response<ClienteResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al crear cliente");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<ClienteResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Crear pago
    public interface CreatePagoCallback {
        void onSuccess(Pago pago);
        void onError(String error);
    }
    
    public void createPago(PagoRequest request, CreatePagoCallback callback) {
        apiService.createPago(request).enqueue(new retrofit2.Callback<PagoResponse>() {
            @Override
            public void onResponse(retrofit2.Call<PagoResponse> call, retrofit2.Response<PagoResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al registrar pago");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<PagoResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Crear ruta
    public interface CreateRutaCallback {
        void onSuccess(Ruta ruta);
        void onError(String error);
    }
    
    public void createRuta(RutaRequest request, CreateRutaCallback callback) {
        apiService.createRuta(request).enqueue(new retrofit2.Callback<RutaResponse>() {
            @Override
            public void onResponse(retrofit2.Call<RutaResponse> call, retrofit2.Response<RutaResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al crear ruta");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<RutaResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Tasas
    public interface TasasCallback {
        void onSuccess(List<TasaInteres> tasas);
        void onError(String error);
    }
    
    public void getTasas(TasasCallback callback) {
        apiService.getTasas().enqueue(new retrofit2.Callback<TasasResponse>() {
            @Override
            public void onResponse(retrofit2.Call<TasasResponse> call, retrofit2.Response<TasasResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    callback.onSuccess(response.body().getData());
                } else {
                    callback.onError("Error al cargar tasas");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<TasasResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Notificaciones - Registrar dispositivo
    public void registrarDispositivoPush(String token, String plataforma, String modelo) {
        DispositivoRequest request = new DispositivoRequest(token, plataforma, modelo);
        apiService.registrarDispositivo(request).enqueue(new retrofit2.Callback<NotificacionResponse>() {
            @Override
            public void onResponse(retrofit2.Call<NotificacionResponse> call, retrofit2.Response<NotificacionResponse> response) {
                if (response.isSuccessful()) {
                    android.util.Log.d("ApiServiceHelper", "Dispositivo registrado correctamente");
                } else {
                    android.util.Log.e("ApiServiceHelper", "Error registrando dispositivo");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<NotificacionResponse> call, Throwable t) {
                android.util.Log.e("ApiServiceHelper", "Error registrando dispositivo: " + t.getMessage());
            }
        });
    }
    
    // Ventas
    public interface VentasCallback {
        void onSuccess(List<Venta> ventas);
        void onError(String error);
    }
    
    public void getVentas(VentasCallback callback) {
        Map<String, String> params = new HashMap<>();
        params.put("per_page", "100");
        
        apiService.getVentas(params).enqueue(new retrofit2.Callback<VentasResponse>() {
            @Override
            public void onResponse(retrofit2.Call<VentasResponse> call, retrofit2.Response<VentasResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar ventas");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<VentasResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Compras
    public interface ComprasCallback {
        void onSuccess(List<Compra> compras);
        void onError(String error);
    }
    
    public void getCompras(ComprasCallback callback) {
        Map<String, String> params = new HashMap<>();
        params.put("per_page", "100");
        
        apiService.getCompras(params).enqueue(new retrofit2.Callback<ComprasResponse>() {
            @Override
            public void onResponse(retrofit2.Call<ComprasResponse> call, retrofit2.Response<ComprasResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar compras");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<ComprasResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
    
    // Artículos
    public interface ArticulosCallback {
        void onSuccess(List<Articulo> articulos);
        void onError(String error);
    }
    
    public void getArticulos(ArticulosCallback callback) {
        Map<String, String> params = new HashMap<>();
        params.put("per_page", "1000");
        
        apiService.getArticulos(params).enqueue(new retrofit2.Callback<ArticulosResponse>() {
            @Override
            public void onResponse(retrofit2.Call<ArticulosResponse> call, retrofit2.Response<ArticulosResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    callback.onSuccess(response.body().getData().getItems());
                } else {
                    callback.onError("Error al cargar artículos");
                }
            }
            
            @Override
            public void onFailure(retrofit2.Call<ArticulosResponse> call, Throwable t) {
                callback.onError(t.getMessage());
            }
        });
    }
}

