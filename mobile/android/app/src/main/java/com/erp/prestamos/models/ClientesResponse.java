package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class ClientesResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private ClientesData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public ClientesData getData() { return data; }
    public void setData(ClientesData data) { this.data = data; }
    
    public static class ClientesData {
        @SerializedName("items")
        private List<Cliente> items;
        
        public List<Cliente> getItems() { return items; }
        public void setItems(List<Cliente> items) { this.items = items; }
    }
}

