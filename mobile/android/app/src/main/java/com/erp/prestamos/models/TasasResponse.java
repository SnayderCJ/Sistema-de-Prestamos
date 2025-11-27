package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class TasasResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private List<TasaInteres> data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public List<TasaInteres> getData() { return data; }
    public void setData(List<TasaInteres> data) { this.data = data; }
}

