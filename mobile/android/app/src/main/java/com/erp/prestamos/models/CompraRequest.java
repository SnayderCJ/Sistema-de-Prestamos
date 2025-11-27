package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class CompraRequest {
    @SerializedName("proveedor_id")
    private int proveedorId;
    
    @SerializedName("fecha")
    private String fecha;
    
    @SerializedName("metodo_pago")
    private String metodoPago;
    
    @SerializedName("items")
    private List<CompraItem> items;
    
    @SerializedName("descuento")
    private double descuento;
    
    @SerializedName("observaciones")
    private String observaciones;
    
    // Getters and Setters
    public int getProveedorId() { return proveedorId; }
    public void setProveedorId(int proveedorId) { this.proveedorId = proveedorId; }
    
    public String getFecha() { return fecha; }
    public void setFecha(String fecha) { this.fecha = fecha; }
    
    public String getMetodoPago() { return metodoPago; }
    public void setMetodoPago(String metodoPago) { this.metodoPago = metodoPago; }
    
    public List<CompraItem> getItems() { return items; }
    public void setItems(List<CompraItem> items) { this.items = items; }
    
    public double getDescuento() { return descuento; }
    public void setDescuento(double descuento) { this.descuento = descuento; }
    
    public String getObservaciones() { return observaciones; }
    public void setObservaciones(String observaciones) { this.observaciones = observaciones; }
    
    public static class CompraItem {
        @SerializedName("articulo_id")
        private int articuloId;
        
        @SerializedName("cantidad")
        private int cantidad;
        
        @SerializedName("precio_unitario")
        private double precioUnitario;
        
        @SerializedName("descuento")
        private double descuento;
        
        // Getters and Setters
        public int getArticuloId() { return articuloId; }
        public void setArticuloId(int articuloId) { this.articuloId = articuloId; }
        
        public int getCantidad() { return cantidad; }
        public void setCantidad(int cantidad) { this.cantidad = cantidad; }
        
        public double getPrecioUnitario() { return precioUnitario; }
        public void setPrecioUnitario(double precioUnitario) { this.precioUnitario = precioUnitario; }
        
        public double getDescuento() { return descuento; }
        public void setDescuento(double descuento) { this.descuento = descuento; }
    }
}

