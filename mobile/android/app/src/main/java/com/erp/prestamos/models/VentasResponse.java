package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class VentasResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private VentasData data;
    
    public boolean isSuccess() { return success; }
    public VentasData getData() { return data; }
    
    public static class VentasData {
        @SerializedName("items")
        private List<Venta> items;
        
        @SerializedName("total")
        private int total;
        
        public List<Venta> getItems() { return items; }
        public int getTotal() { return total; }
    }
}

