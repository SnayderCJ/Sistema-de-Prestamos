package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class CompraResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Compra data;
    
    public boolean isSuccess() { return success; }
    public Compra getData() { return data; }
}

