package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class DashboardResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private DashboardData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public DashboardData getData() { return data; }
    public void setData(DashboardData data) { this.data = data; }
    
    public static class DashboardData {
        @SerializedName("estadisticas")
        private DashboardStats estadisticas;
        
        @SerializedName("prestamos_vencidos")
        private List<Prestamo> prestamosVencidos;
        
        public DashboardStats getEstadisticas() { return estadisticas; }
        public void setEstadisticas(DashboardStats estadisticas) { this.estadisticas = estadisticas; }
        
        public List<Prestamo> getPrestamosVencidos() { return prestamosVencidos; }
        public void setPrestamosVencidos(List<Prestamo> prestamosVencidos) { this.prestamosVencidos = prestamosVencidos; }
    }
}

