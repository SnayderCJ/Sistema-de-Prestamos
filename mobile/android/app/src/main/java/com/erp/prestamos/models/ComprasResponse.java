package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ComprasResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private ComprasData data;
    
    public boolean isSuccess() { return success; }
    public ComprasData getData() { return data; }
    
    public static class ComprasData {
        @SerializedName("items")
        private List<Compra> items;
        
        @SerializedName("total")
        private int total;
        
        public List<Compra> getItems() { return items; }
        public int getTotal() { return total; }
    }
}

