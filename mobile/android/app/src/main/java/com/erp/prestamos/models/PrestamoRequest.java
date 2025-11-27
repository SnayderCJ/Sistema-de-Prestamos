package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class PrestamoRequest {
    @SerializedName("cliente_id")
    private int clienteId;
    
    @SerializedName("monto_solicitado")
    private double montoSolicitado;
    
    @SerializedName("plazo_meses")
    private int plazoMeses;
    
    @SerializedName("tasa_interes_id")
    private int tasaInteresId;
    
    @SerializedName("garantia_tipo")
    private String garantiaTipo;
    
    @SerializedName("observaciones")
    private String observaciones;
    
    public PrestamoRequest() {}
    
    public PrestamoRequest(int clienteId, double montoSolicitado, int plazoMeses, int tasaInteresId) {
        this.clienteId = clienteId;
        this.montoSolicitado = montoSolicitado;
        this.plazoMeses = plazoMeses;
        this.tasaInteresId = tasaInteresId;
    }
    
    // Getters and Setters
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    
    public double getMontoSolicitado() { return montoSolicitado; }
    public void setMontoSolicitado(double montoSolicitado) { this.montoSolicitado = montoSolicitado; }
    
    public int getPlazoMeses() { return plazoMeses; }
    public void setPlazoMeses(int plazoMeses) { this.plazoMeses = plazoMeses; }
    
    public int getTasaInteresId() { return tasaInteresId; }
    public void setTasaInteresId(int tasaInteresId) { this.tasaInteresId = tasaInteresId; }
    
    public String getGarantiaTipo() { return garantiaTipo; }
    public void setGarantiaTipo(String garantiaTipo) { this.garantiaTipo = garantiaTipo; }
    
    public String getObservaciones() { return observaciones; }
    public void setObservaciones(String observaciones) { this.observaciones = observaciones; }
}

