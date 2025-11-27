package com.erp.prestamos.services;

import android.content.Context;
import android.util.Log;
import com.erp.prestamos.utils.OfflineHelper;
import com.erp.prestamos.models.*;
import com.google.gson.Gson;
import java.util.List;

/**
 * Servicio de sincronización offline
 */
public class SyncService {
    private static final String TAG = "SyncService";
    private Context context;
    private ApiServiceHelper apiHelper;
    private OfflineHelper offlineHelper;
    private Gson gson;
    
    public SyncService(Context context) {
        this.context = context;
        this.apiHelper = ApiServiceHelper.getInstance(context);
        this.offlineHelper = new OfflineHelper(context);
        this.gson = new Gson();
    }
    
    /**
     * Sincronizar todas las acciones pendientes
     */
    public void sincronizar() {
        List<OfflineHelper.PendingAction> acciones = offlineHelper.obtenerAccionesPendientes();
        
        if (acciones.isEmpty()) {
            Log.d(TAG, "No hay acciones pendientes para sincronizar");
            return;
        }
        
        Log.d(TAG, "Sincronizando " + acciones.size() + " acciones pendientes");
        
        for (OfflineHelper.PendingAction accion : acciones) {
            try {
                sincronizarAccion(accion);
                offlineHelper.eliminarAccionPendiente(accion);
            } catch (Exception e) {
                Log.e(TAG, "Error sincronizando acción: " + e.getMessage());
                offlineHelper.incrementarIntentos(accion);
                
                // Si tiene más de 5 intentos, eliminarla
                if (accion.intentos >= 5) {
                    offlineHelper.eliminarAccionPendiente(accion);
                }
            }
        }
        
        offlineHelper.guardarUltimaSincronizacion();
    }
    
    /**
     * Sincronizar una acción específica
     */
    private void sincronizarAccion(OfflineHelper.PendingAction accion) throws Exception {
        switch (accion.tipo) {
            case "crear_prestamo":
                PrestamoRequest prestamoRequest = gson.fromJson(accion.datos, PrestamoRequest.class);
                apiHelper.createPrestamo(prestamoRequest, new ApiServiceHelper.CreatePrestamoCallback() {
                    @Override
                    public void onSuccess(Prestamo prestamo) {
                        Log.d(TAG, "Préstamo sincronizado: " + prestamo.getId());
                    }
                    
                    @Override
                    public void onError(String error) {
                        throw new RuntimeException(error);
                    }
                });
                break;
                
            case "crear_cliente":
                ClienteRequest clienteRequest = gson.fromJson(accion.datos, ClienteRequest.class);
                apiHelper.createCliente(clienteRequest, new ApiServiceHelper.CreateClienteCallback() {
                    @Override
                    public void onSuccess(Cliente cliente) {
                        Log.d(TAG, "Cliente sincronizado: " + cliente.getId());
                    }
                    
                    @Override
                    public void onError(String error) {
                        throw new RuntimeException(error);
                    }
                });
                break;
                
            case "registrar_pago":
                PagoRequest pagoRequest = gson.fromJson(accion.datos, PagoRequest.class);
                apiHelper.createPago(pagoRequest, new ApiServiceHelper.CreatePagoCallback() {
                    @Override
                    public void onSuccess(Pago pago) {
                        Log.d(TAG, "Pago sincronizado: " + pago.getId());
                    }
                    
                    @Override
                    public void onError(String error) {
                        throw new RuntimeException(error);
                    }
                });
                break;
                
            case "crear_ruta":
                RutaRequest rutaRequest = gson.fromJson(accion.datos, RutaRequest.class);
                apiHelper.createRuta(rutaRequest, new ApiServiceHelper.CreateRutaCallback() {
                    @Override
                    public void onSuccess(Ruta ruta) {
                        Log.d(TAG, "Ruta sincronizada: " + ruta.getId());
                    }
                    
                    @Override
                    public void onError(String error) {
                        throw new RuntimeException(error);
                    }
                });
                break;
                
            default:
                Log.w(TAG, "Tipo de acción desconocido: " + accion.tipo);
        }
    }
    
    /**
     * Verificar conectividad y sincronizar si hay conexión
     */
    public void sincronizarSiHayConexion() {
        android.net.ConnectivityManager cm = 
            (android.net.ConnectivityManager) context.getSystemService(Context.CONNECTIVITY_SERVICE);
        
        android.net.NetworkInfo activeNetwork = cm.getActiveNetworkInfo();
        boolean isConnected = activeNetwork != null && activeNetwork.isConnectedOrConnecting();
        
        if (isConnected) {
            new Thread(() -> sincronizar()).start();
        } else {
            Log.d(TAG, "Sin conexión, no se puede sincronizar");
        }
    }
}

