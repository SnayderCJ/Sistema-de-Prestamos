package com.erp.prestamos.models;

public class MarcarLeidaRequest {
    private int notificacion_id;
    
    public MarcarLeidaRequest(int notificacion_id) {
        this.notificacion_id = notificacion_id;
    }
    
    public int getNotificacionId() { return notificacion_id; }
    public void setNotificacionId(int notificacion_id) { this.notificacion_id = notificacion_id; }
}

