package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class VentaResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Venta data;
    
    public boolean isSuccess() { return success; }
    public Venta getData() { return data; }
}

