package com.erp.prestamos.models;

public class NotificacionResponse {
    private boolean success;
    private String message;
    private NotificacionData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public String getMessage() { return message; }
    public void setMessage(String message) { this.message = message; }
    
    public NotificacionData getData() { return data; }
    public void setData(NotificacionData data) { this.data = data; }
    
    public static class NotificacionData {
        private int dispositivo_id;
        
        public int getDispositivoId() { return dispositivo_id; }
        public void setDispositivoId(int dispositivo_id) { this.dispositivo_id = dispositivo_id; }
    }
}

