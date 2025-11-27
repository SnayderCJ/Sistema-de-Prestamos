package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class Pago {
    @SerializedName("id")
    private int id;
    
    @SerializedName("numero_recibo")
    private String numeroRecibo;
    
    @SerializedName("prestamo_id")
    private int prestamoId;
    
    @SerializedName("numero_prestamo")
    private String numeroPrestamo;
    
    @SerializedName("cliente_id")
    private int clienteId;
    
    @SerializedName("cliente_nombre")
    private String clienteNombre;
    
    @SerializedName("cliente_apellido")
    private String clienteApellido;
    
    @SerializedName("cliente_cedula")
    private String clienteCedula;
    
    @SerializedName("monto")
    private double monto;
    
    @SerializedName("capital")
    private double capital;
    
    @SerializedName("interes")
    private double interes;
    
    @SerializedName("mora")
    private double mora;
    
    @SerializedName("metodo_pago")
    private String metodoPago;
    
    @SerializedName("numero_comprobante")
    private String numeroComprobante;
    
    @SerializedName("fecha_pago")
    private String fechaPago;
    
    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getNumeroRecibo() { return numeroRecibo; }
    public void setNumeroRecibo(String numeroRecibo) { this.numeroRecibo = numeroRecibo; }
    
    public int getPrestamoId() { return prestamoId; }
    public void setPrestamoId(int prestamoId) { this.prestamoId = prestamoId; }
    
    public String getNumeroPrestamo() { return numeroPrestamo; }
    public void setNumeroPrestamo(String numeroPrestamo) { this.numeroPrestamo = numeroPrestamo; }
    
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    
    public String getClienteNombre() { return clienteNombre; }
    public void setClienteNombre(String clienteNombre) { this.clienteNombre = clienteNombre; }
    
    public String getClienteApellido() { return clienteApellido; }
    public void setClienteApellido(String clienteApellido) { this.clienteApellido = clienteApellido; }
    
    public String getClienteCedula() { return clienteCedula; }
    public void setClienteCedula(String clienteCedula) { this.clienteCedula = clienteCedula; }
    
    public double getMonto() { return monto; }
    public void setMonto(double monto) { this.monto = monto; }
    
    public double getCapital() { return capital; }
    public void setCapital(double capital) { this.capital = capital; }
    
    public double getInteres() { return interes; }
    public void setInteres(double interes) { this.interes = interes; }
    
    public double getMora() { return mora; }
    public void setMora(double mora) { this.mora = mora; }
    
    public String getMetodoPago() { return metodoPago; }
    public void setMetodoPago(String metodoPago) { this.metodoPago = metodoPago; }
    
    public String getNumeroComprobante() { return numeroComprobante; }
    public void setNumeroComprobante(String numeroComprobante) { this.numeroComprobante = numeroComprobante; }
    
    public String getFechaPago() { return fechaPago; }
    public void setFechaPago(String fechaPago) { this.fechaPago = fechaPago; }
}

