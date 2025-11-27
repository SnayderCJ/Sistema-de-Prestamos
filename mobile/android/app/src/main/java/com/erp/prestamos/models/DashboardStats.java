package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class DashboardStats {
    @SerializedName("prestamos_activos")
    private int prestamosActivos;
    
    @SerializedName("monto_total")
    private double montoTotal;
    
    @SerializedName("prestamos_vencidos")
    private int prestamosVencidos;
    
    @SerializedName("cobros_hoy")
    private double cobrosHoy;
    
    @SerializedName("mora_total")
    private double moraTotal;
    
    // Getters and Setters
    public int getPrestamosActivos() { return prestamosActivos; }
    public void setPrestamosActivos(int prestamosActivos) { this.prestamosActivos = prestamosActivos; }
    
    public double getMontoTotal() { return montoTotal; }
    public void setMontoTotal(double montoTotal) { this.montoTotal = montoTotal; }
    
    public int getPrestamosVencidos() { return prestamosVencidos; }
    public void setPrestamosVencidos(int prestamosVencidos) { this.prestamosVencidos = prestamosVencidos; }
    
    public double getCobrosHoy() { return cobrosHoy; }
    public void setCobrosHoy(double cobrosHoy) { this.cobrosHoy = cobrosHoy; }
    
    public double getMoraTotal() { return moraTotal; }
    public void setMoraTotal(double moraTotal) { this.moraTotal = moraTotal; }
}

