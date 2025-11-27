package com.erp.prestamos.models;

import java.util.List;

public class NotificacionesResponse {
    private boolean success;
    private NotificacionesData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public NotificacionesData getData() { return data; }
    public void setData(NotificacionesData data) { this.data = data; }
    
    public static class NotificacionesData {
        private List<Notificacion> data;
        private Pagination pagination;
        
        public List<Notificacion> getData() { return data; }
        public void setData(List<Notificacion> data) { this.data = data; }
        
        public Pagination getPagination() { return pagination; }
        public void setPagination(Pagination pagination) { this.pagination = pagination; }
    }
    
    public static class Notificacion {
        private int id;
        private String titulo;
        private String mensaje;
        private String tipo;
        private boolean leida;
        private String fecha_creacion;
        
        public int getId() { return id; }
        public void setId(int id) { this.id = id; }
        
        public String getTitulo() { return titulo; }
        public void setTitulo(String titulo) { this.titulo = titulo; }
        
        public String getMensaje() { return mensaje; }
        public void setMensaje(String mensaje) { this.mensaje = mensaje; }
        
        public String getTipo() { return tipo; }
        public void setTipo(String tipo) { this.tipo = tipo; }
        
        public boolean isLeida() { return leida; }
        public void setLeida(boolean leida) { this.leida = leida; }
        
        public String getFechaCreacion() { return fecha_creacion; }
        public void setFechaCreacion(String fecha_creacion) { this.fecha_creacion = fecha_creacion; }
    }
    
    public static class Pagination {
        private int page;
        private int per_page;
        private int total;
        private int total_pages;
        
        public int getPage() { return page; }
        public void setPage(int page) { this.page = page; }
        
        public int getPerPage() { return per_page; }
        public void setPerPage(int per_page) { this.per_page = per_page; }
        
        public int getTotal() { return total; }
        public void setTotal(int total) { this.total = total; }
        
        public int getTotalPages() { return total_pages; }
        public void setTotalPages(int total_pages) { this.total_pages = total_pages; }
    }
}

