package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class ArticuloRequest {
    @SerializedName("codigo")
    private String codigo;
    
    @SerializedName("nombre")
    private String nombre;
    
    @SerializedName("descripcion")
    private String descripcion;
    
    @SerializedName("categoria_id")
    private int categoriaId;
    
    @SerializedName("precio_compra")
    private double precioCompra;
    
    @SerializedName("precio_venta")
    private double precioVenta;
    
    @SerializedName("precio_venta_credito")
    private double precioVentaCredito;
    
    @SerializedName("stock")
    private int stock;
    
    @SerializedName("stock_minimo")
    private int stockMinimo;
    
    @SerializedName("utilidad_porcentaje")
    private double utilidadPorcentaje;
    
    // Getters and Setters
    public String getCodigo() { return codigo; }
    public void setCodigo(String codigo) { this.codigo = codigo; }
    
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    
    public String getDescripcion() { return descripcion; }
    public void setDescripcion(String descripcion) { this.descripcion = descripcion; }
    
    public int getCategoriaId() { return categoriaId; }
    public void setCategoriaId(int categoriaId) { this.categoriaId = categoriaId; }
    
    public double getPrecioCompra() { return precioCompra; }
    public void setPrecioCompra(double precioCompra) { this.precioCompra = precioCompra; }
    
    public double getPrecioVenta() { return precioVenta; }
    public void setPrecioVenta(double precioVenta) { this.precioVenta = precioVenta; }
    
    public double getPrecioVentaCredito() { return precioVentaCredito; }
    public void setPrecioVentaCredito(double precioVentaCredito) { this.precioVentaCredito = precioVentaCredito; }
    
    public int getStock() { return stock; }
    public void setStock(int stock) { this.stock = stock; }
    
    public int getStockMinimo() { return stockMinimo; }
    public void setStockMinimo(int stockMinimo) { this.stockMinimo = stockMinimo; }
    
    public double getUtilidadPorcentaje() { return utilidadPorcentaje; }
    public void setUtilidadPorcentaje(double utilidadPorcentaje) { this.utilidadPorcentaje = utilidadPorcentaje; }
}

