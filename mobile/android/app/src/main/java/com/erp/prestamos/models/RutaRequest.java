package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class RutaRequest {
    @SerializedName("nombre_ruta")
    private String nombreRuta;
    
    @SerializedName("fecha_ruta")
    private String fechaRuta;
    
    @SerializedName("cobrador_id")
    private Integer cobradorId;
    
    public RutaRequest() {}
    
    public RutaRequest(String nombreRuta, String fechaRuta) {
        this.nombreRuta = nombreRuta;
        this.fechaRuta = fechaRuta;
    }
    
    // Getters and Setters
    public String getNombreRuta() { return nombreRuta; }
    public void setNombreRuta(String nombreRuta) { this.nombreRuta = nombreRuta; }
    
    public String getFechaRuta() { return fechaRuta; }
    public void setFechaRuta(String fechaRuta) { this.fechaRuta = fechaRuta; }
    
    public Integer getCobradorId() { return cobradorId; }
    public void setCobradorId(Integer cobradorId) { this.cobradorId = cobradorId; }
}

