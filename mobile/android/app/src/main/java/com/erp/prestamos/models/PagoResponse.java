package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class PagoResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Pago data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public Pago getData() { return data; }
    public void setData(Pago data) { this.data = data; }
}

