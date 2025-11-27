package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class DataCreditosResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private DataCreditosData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public DataCreditosData getData() { return data; }
    public void setData(DataCreditosData data) { this.data = data; }
    
    public static class DataCreditosData {
        @SerializedName("cedula")
        private String cedula;
        
        @SerializedName("score")
        private Double score;
        
        @SerializedName("deudas")
        private Double deudas;
        
        // Add more fields as needed
        
        public String getCedula() { return cedula; }
        public void setCedula(String cedula) { this.cedula = cedula; }
        
        public Double getScore() { return score; }
        public void setScore(Double score) { this.score = score; }
        
        public Double getDeudas() { return deudas; }
        public void setDeudas(Double deudas) { this.deudas = deudas; }
    }
}

