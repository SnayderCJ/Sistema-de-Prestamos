package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class ConsultaResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private ConsultaData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public ConsultaData getData() { return data; }
    public void setData(ConsultaData data) { this.data = data; }
    
    public static class ConsultaData {
        @SerializedName("cedula")
        private String cedula;
        
        @SerializedName("nombre")
        private String nombre;
        
        @SerializedName("apellido")
        private String apellido;
        
        // Add more fields as needed
        
        public String getCedula() { return cedula; }
        public void setCedula(String cedula) { this.cedula = cedula; }
        
        public String getNombre() { return nombre; }
        public void setNombre(String nombre) { this.nombre = nombre; }
        
        public String getApellido() { return apellido; }
        public void setApellido(String apellido) { this.apellido = apellido; }
    }
}

