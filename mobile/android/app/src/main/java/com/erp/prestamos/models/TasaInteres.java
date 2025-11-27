package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class TasaInteres {
    @SerializedName("id")
    private int id;
    
    @SerializedName("nombre")
    private String nombre;
    
    @SerializedName("tasa_anual")
    private double tasaAnual;
    
    @SerializedName("tasa_mensual")
    private double tasaMensual;
    
    @SerializedName("activa")
    private boolean activa;
    
    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    
    public double getTasaAnual() { return tasaAnual; }
    public void setTasaAnual(double tasaAnual) { this.tasaAnual = tasaAnual; }
    
    public double getTasaMensual() { return tasaMensual; }
    public void setTasaMensual(double tasaMensual) { this.tasaMensual = tasaMensual; }
    
    public boolean isActiva() { return activa; }
    public void setActiva(boolean activa) { this.activa = activa; }
    
    @Override
    public String toString() {
        if (nombre != null && !nombre.isEmpty()) {
            return nombre + " (" + String.format("%.2f", tasaAnual) + "% anual)";
        }
        return String.format("%.2f%% anual", tasaAnual);
    }
}

