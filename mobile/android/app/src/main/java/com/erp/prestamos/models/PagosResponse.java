package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class PagosResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private PagosData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public PagosData getData() { return data; }
    public void setData(PagosData data) { this.data = data; }
    
    public static class PagosData {
        @SerializedName("items")
        private List<Pago> items;
        
        public List<Pago> getItems() { return items; }
        public void setItems(List<Pago> items) { this.items = items; }
    }
}

