package com.erp.prestamos.models;

public class CantidadNoLeidasResponse {
    private boolean success;
    private int cantidad;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public int getCantidad() { return cantidad; }
    public void setCantidad(int cantidad) { this.cantidad = cantidad; }
}

