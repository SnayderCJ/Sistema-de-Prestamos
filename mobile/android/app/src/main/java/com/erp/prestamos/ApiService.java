package com.imaxprestamos;

import retrofit2.Call;
import retrofit2.http.*;

public interface ApiService {
    // Auth
    @POST("auth/login")
    Call<AuthResponse> login(@Body LoginRequest request);
    
    // Préstamos
    @GET("prestamos")
    Call<PrestamosResponse> getPrestamos(@Query("page") int page, @Query("per_page") int perPage);
    
    @GET("prestamos/{id}")
    Call<PrestamoResponse> getPrestamo(@Path("id") int id);
    
    @POST("prestamos")
    Call<PrestamoResponse> createPrestamo(@Body PrestamoRequest request);
    
    @PUT("prestamos/{id}")
    Call<PrestamoResponse> updatePrestamo(@Path("id") int id, @Body PrestamoRequest request);
    
    // Clientes
    @GET("clientes")
    Call<ClientesResponse> getClientes(@Query("page") int page, @Query("per_page") int perPage);
    
    @POST("clientes")
    Call<ClienteResponse> createCliente(@Body ClienteRequest request);
    
    // Consultas
    @GET("consultas/cedula")
    Call<ConsultaResponse> consultarCedula(@Query("cedula") String cedula);
    
    @GET("consultas/data-creditos")
    Call<DataCreditosResponse> consultarDataCreditos(@Query("cedula") String cedula);
    
    // Rutas
    @GET("rutas")
    Call<RutasResponse> getRutas(@Query("page") int page);
    
    @POST("rutas")
    Call<RutaResponse> createRuta(@Body RutaRequest request);
    
    // Pagos
    @POST("pagos")
    Call<PagoResponse> registrarPago(@Body PagoRequest request);
    
    // Notificaciones
    @POST("notificaciones/registrar-dispositivo")
    Call<NotificacionResponse> registrarDispositivo(@Body DispositivoRequest request);
    
    @GET("notificaciones")
    Call<NotificacionesResponse> getNotificaciones(@Query("page") int page, @Query("per_page") int perPage);
    
    @GET("notificaciones/cantidad-no-leidas")
    Call<CantidadNoLeidasResponse> getCantidadNoLeidas();
    
    @PUT("notificaciones/marcar-leida")
    Call<NotificacionResponse> marcarComoLeida(@Body MarcarLeidaRequest request);
    
    @PUT("notificaciones/marcar-todas-leidas")
    Call<NotificacionResponse> marcarTodasComoLeidas();
    
    // Dashboard
    @GET("dashboard")
    Call<DashboardResponse> getDashboard();
    
    @GET("dashboard/estadisticas")
    Call<EstadisticasResponse> getEstadisticas();
}

