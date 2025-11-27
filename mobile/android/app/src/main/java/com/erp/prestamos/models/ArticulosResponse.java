package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ArticulosResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private ArticulosData data;
    
    public boolean isSuccess() { return success; }
    public ArticulosData getData() { return data; }
    
    public static class ArticulosData {
        @SerializedName("items")
        private List<Articulo> items;
        
        @SerializedName("total")
        private int total;
        
        public List<Articulo> getItems() { return items; }
        public int getTotal() { return total; }
    }
}

