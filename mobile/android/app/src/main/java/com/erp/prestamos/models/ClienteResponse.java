package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class ClienteResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private Cliente data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public Cliente getData() { return data; }
    public void setData(Cliente data) { this.data = data; }
}

