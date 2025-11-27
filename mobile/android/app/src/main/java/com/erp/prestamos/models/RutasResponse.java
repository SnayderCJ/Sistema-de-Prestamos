package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class RutasResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private RutasData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public RutasData getData() { return data; }
    public void setData(RutasData data) { this.data = data; }
    
    public static class RutasData {
        @SerializedName("items")
        private List<Ruta> items;
        
        public List<Ruta> getItems() { return items; }
        public void setItems(List<Ruta> items) { this.items = items; }
    }
}

