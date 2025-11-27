package com.erp.prestamos.utils;

import android.content.Context;
import android.content.SharedPreferences;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

/**
 * Helper para manejo de datos offline
 */
public class OfflineHelper {
    private static final String PREFS_NAME = "offline_data";
    private static final String KEY_PENDING_ACTIONS = "pending_actions";
    private static final String KEY_LAST_SYNC = "last_sync";
    
    private SharedPreferences prefs;
    private Gson gson;
    
    public OfflineHelper(Context context) {
        prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        gson = new Gson();
    }
    
    /**
     * Guardar acción pendiente para sincronizar
     */
    public void guardarAccionPendiente(String tipo, Object datos) {
        List<PendingAction> acciones = obtenerAccionesPendientes();
        
        PendingAction accion = new PendingAction();
        accion.tipo = tipo;
        accion.datos = gson.toJson(datos);
        accion.timestamp = System.currentTimeMillis();
        accion.intentos = 0;
        
        acciones.add(accion);
        
        guardarAcciones(acciones);
    }
    
    /**
     * Obtener acciones pendientes
     */
    public List<PendingAction> obtenerAccionesPendientes() {
        String json = prefs.getString(KEY_PENDING_ACTIONS, "[]");
        Type listType = new TypeToken<List<PendingAction>>(){}.getType();
        return gson.fromJson(json, listType);
    }
    
    /**
     * Eliminar acción pendiente
     */
    public void eliminarAccionPendiente(PendingAction accion) {
        List<PendingAction> acciones = obtenerAccionesPendientes();
        acciones.remove(accion);
        guardarAcciones(acciones);
    }
    
    /**
     * Incrementar intentos de una acción
     */
    public void incrementarIntentos(PendingAction accion) {
        List<PendingAction> acciones = obtenerAccionesPendientes();
        for (PendingAction a : acciones) {
            if (a.equals(accion)) {
                a.intentos++;
                break;
            }
        }
        guardarAcciones(acciones);
    }
    
    /**
     * Guardar acciones
     */
    private void guardarAcciones(List<PendingAction> acciones) {
        String json = gson.toJson(acciones);
        prefs.edit().putString(KEY_PENDING_ACTIONS, json).apply();
    }
    
    /**
     * Guardar última sincronización
     */
    public void guardarUltimaSincronizacion() {
        prefs.edit().putLong(KEY_LAST_SYNC, System.currentTimeMillis()).apply();
    }
    
    /**
     * Obtener última sincronización
     */
    public long obtenerUltimaSincronizacion() {
        return prefs.getLong(KEY_LAST_SYNC, 0);
    }
    
    /**
     * Verificar si hay datos pendientes
     */
    public boolean hayDatosPendientes() {
        List<PendingAction> acciones = obtenerAccionesPendientes();
        return !acciones.isEmpty();
    }
    
    /**
     * Limpiar acciones antiguas (más de 7 días)
     */
    public void limpiarAccionesAntiguas() {
        List<PendingAction> acciones = obtenerAccionesPendientes();
        long sieteDiasAtras = System.currentTimeMillis() - (7 * 24 * 60 * 60 * 1000);
        
        List<PendingAction> accionesValidas = new ArrayList<>();
        for (PendingAction accion : acciones) {
            if (accion.timestamp > sieteDiasAtras) {
                accionesValidas.add(accion);
            }
        }
        
        guardarAcciones(accionesValidas);
    }
    
    /**
     * Clase para acciones pendientes
     */
    public static class PendingAction {
        public String tipo;
        public String datos;
        public long timestamp;
        public int intentos;
        
        @Override
        public boolean equals(Object obj) {
            if (this == obj) return true;
            if (obj == null || getClass() != obj.getClass()) return false;
            PendingAction that = (PendingAction) obj;
            return tipo.equals(that.tipo) && 
                   datos.equals(that.datos) && 
                   timestamp == that.timestamp;
        }
    }
}

