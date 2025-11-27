package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class ArticuloResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Articulo data;
    
    public boolean isSuccess() { return success; }
    public Articulo getData() { return data; }
}

