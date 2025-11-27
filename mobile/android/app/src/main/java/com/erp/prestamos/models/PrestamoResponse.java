package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class PrestamoResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Prestamo data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public Prestamo getData() { return data; }
    public void setData(Prestamo data) { this.data = data; }
}

