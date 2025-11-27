package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class PrestamosResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private PrestamosData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public PrestamosData getData() { return data; }
    public void setData(PrestamosData data) { this.data = data; }
    
    public static class PrestamosData {
        @SerializedName("items")
        private List<Prestamo> items;
        
        @SerializedName("pagination")
        private Pagination pagination;
        
        public List<Prestamo> getItems() { return items; }
        public void setItems(List<Prestamo> items) { this.items = items; }
        
        public Pagination getPagination() { return pagination; }
        public void setPagination(Pagination pagination) { this.pagination = pagination; }
    }
    
    public static class Pagination {
        @SerializedName("page")
        private int page;
        
        @SerializedName("per_page")
        private int perPage;
        
        @SerializedName("total")
        private int total;
        
        @SerializedName("total_pages")
        private int totalPages;
        
        public int getPage() { return page; }
        public void setPage(int page) { this.page = page; }
        
        public int getPerPage() { return perPage; }
        public void setPerPage(int perPage) { this.perPage = perPage; }
        
        public int getTotal() { return total; }
        public void setTotal(int total) { this.total = total; }
        
        public int getTotalPages() { return totalPages; }
        public void setTotalPages(int totalPages) { this.totalPages = totalPages; }
    }
}

