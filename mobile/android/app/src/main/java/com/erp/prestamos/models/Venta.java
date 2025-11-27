package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class Venta {
    @SerializedName("id")
    private int id;
    
    @SerializedName("numero_factura")
    private String numeroFactura;
    
    @SerializedName("cliente_id")
    private int clienteId;
    
    @SerializedName("cliente_nombre")
    private String clienteNombre;
    
    @SerializedName("fecha")
    private String fecha;
    
    @SerializedName("monto_total")
    private double montoTotal;
    
    @SerializedName("metodo_pago")
    private String metodoPago;
    
    @SerializedName("estado")
    private String estado;
    
    @SerializedName("descuento")
    private double descuento;
    
    @SerializedName("impuesto")
    private double impuesto;
    
    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getNumeroFactura() { return numeroFactura; }
    public void setNumeroFactura(String numeroFactura) { this.numeroFactura = numeroFactura; }
    
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    
    public String getClienteNombre() { return clienteNombre; }
    public void setClienteNombre(String clienteNombre) { this.clienteNombre = clienteNombre; }
    
    public String getFecha() { return fecha; }
    public void setFecha(String fecha) { this.fecha = fecha; }
    
    public double getMontoTotal() { return montoTotal; }
    public void setMontoTotal(double montoTotal) { this.montoTotal = montoTotal; }
    
    public String getMetodoPago() { return metodoPago; }
    public void setMetodoPago(String metodoPago) { this.metodoPago = metodoPago; }
    
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }
    
    public double getDescuento() { return descuento; }
    public void setDescuento(double descuento) { this.descuento = descuento; }
    
    public double getImpuesto() { return impuesto; }
    public void setImpuesto(double impuesto) { this.impuesto = impuesto; }
}

