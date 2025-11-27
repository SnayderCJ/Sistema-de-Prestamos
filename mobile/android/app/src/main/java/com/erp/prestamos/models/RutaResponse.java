package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class RutaResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Ruta data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public Ruta getData() { return data; }
    public void setData(Ruta data) { this.data = data; }
}

