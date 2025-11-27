package com.erp.prestamos.services;

import com.erp.prestamos.models.*;
import retrofit2.Call;
import retrofit2.http.*;

import java.util.List;
import java.util.Map;

public interface ApiService {
    
    // MARK: - Auth
    @POST("auth/login")
    Call<AuthResponse> login(@Body LoginRequest request);
    
    @POST("auth/refresh")
    Call<AuthResponse> refreshToken(@Body Map<String, String> data);
    
    // MARK: - Dashboard
    @GET("dashboard")
    Call<DashboardResponse> getDashboard();
    
    // MARK: - Préstamos
    @GET("prestamos")
    Call<PrestamosResponse> getPrestamos(@QueryMap Map<String, String> params);
    
    @GET("prestamos/{id}")
    Call<PrestamoResponse> getPrestamo(@Path("id") int id);
    
    @POST("prestamos")
    Call<PrestamoResponse> createPrestamo(@Body PrestamoRequest request);
    
    @PUT("prestamos/{id}")
    Call<PrestamoResponse> updatePrestamo(@Path("id") int id, @Body PrestamoRequest request);
    
    // MARK: - Clientes
    @GET("clientes")
    Call<ClientesResponse> getClientes(@QueryMap Map<String, String> params);
    
    @GET("clientes/{id}")
    Call<ClienteResponse> getCliente(@Path("id") int id);
    
    @POST("clientes")
    Call<ClienteResponse> createCliente(@Body ClienteRequest request);
    
    // MARK: - Rutas
    @GET("rutas")
    Call<RutasResponse> getRutas(@QueryMap Map<String, String> params);
    
    @GET("rutas/{id}")
    Call<RutaResponse> getRuta(@Path("id") int id);
    
    @POST("rutas")
    Call<RutaResponse> createRuta(@Body RutaRequest request);
    
    // MARK: - Pagos
    @GET("pagos")
    Call<PagosResponse> getPagos(@QueryMap Map<String, String> params);
    
    @POST("pagos")
    Call<PagoResponse> createPago(@Body PagoRequest request);
    
    // MARK: - Tasas
    @GET("tasas")
    Call<TasasResponse> getTasas();
    
    // MARK: - Consultas
    @GET("consultas/cedula")
    Call<ConsultaResponse> consultarCedula(@Query("cedula") String cedula);
    
    @GET("consultas/data-creditos")
    Call<DataCreditosResponse> consultarDataCreditos(@Query("cedula") String cedula);
    
    // MARK: - Ventas
    @GET("ventas")
    Call<VentasResponse> getVentas(@QueryMap Map<String, String> params);
    
    @GET("ventas/{id}")
    Call<VentaResponse> getVenta(@Path("id") int id);
    
    @POST("ventas")
    Call<VentaResponse> createVenta(@Body VentaRequest request);
    
    // MARK: - Compras
    @GET("compras")
    Call<ComprasResponse> getCompras(@QueryMap Map<String, String> params);
    
    @GET("compras/{id}")
    Call<CompraResponse> getCompra(@Path("id") int id);
    
    @POST("compras")
    Call<CompraResponse> createCompra(@Body CompraRequest request);
    
    // MARK: - Artículos
    @GET("articulos")
    Call<ArticulosResponse> getArticulos(@QueryMap Map<String, String> params);
    
    @GET("articulos/{id}")
    Call<ArticuloResponse> getArticulo(@Path("id") int id);
    
    @POST("articulos")
    Call<ArticuloResponse> createArticulo(@Body ArticuloRequest request);
    
    @PUT("articulos/{id}")
    Call<ArticuloResponse> updateArticulo(@Path("id") int id, @Body ArticuloRequest request);
    
    // MARK: - Notificaciones
    @POST("notificaciones/registrar-dispositivo")
    Call<NotificacionResponse> registrarDispositivo(@Body DispositivoRequest request);
}


