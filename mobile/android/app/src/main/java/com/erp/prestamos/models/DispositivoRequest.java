package com.erp.prestamos.models;

public class DispositivoRequest {
    private String token;
    private String plataforma;
    private String modelo;
    
    public DispositivoRequest(String token, String plataforma, String modelo) {
        this.token = token;
        this.plataforma = plataforma;
        this.modelo = modelo;
    }
    
    public String getToken() { return token; }
    public void setToken(String token) { this.token = token; }
    
    public String getPlataforma() { return plataforma; }
    public void setPlataforma(String plataforma) { this.plataforma = plataforma; }
    
    public String getModelo() { return modelo; }
    public void setModelo(String modelo) { this.modelo = modelo; }
}

