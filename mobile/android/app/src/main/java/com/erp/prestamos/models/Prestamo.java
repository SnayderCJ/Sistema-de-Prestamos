package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class Prestamo {
    @SerializedName("id")
    private int id;
    
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
    
    @SerializedName("monto_aprobado")
    private double montoAprobado;
    
    @SerializedName("monto_solicitado")
    private double montoSolicitado;
    
    @SerializedName("cuota_mensual")
    private double cuotaMensual;
    
    @SerializedName("plazo_meses")
    private int plazoMeses;
    
    @SerializedName("tasa_mensual")
    private double tasaMensual;
    
    @SerializedName("estado")
    private String estado;
    
    @SerializedName("saldo_actual")
    private double saldoActual;
    
    @SerializedName("fecha_creacion")
    private String fechaCreacion;
    
    @SerializedName("fecha_aprobacion")
    private String fechaAprobacion;
    
    @SerializedName("garantia_tipo")
    private String garantiaTipo;
    
    @SerializedName("observaciones")
    private String observaciones;
    
    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
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
    
    public double getMontoAprobado() { return montoAprobado; }
    public void setMontoAprobado(double montoAprobado) { this.montoAprobado = montoAprobado; }
    
    public double getMontoSolicitado() { return montoSolicitado; }
    public void setMontoSolicitado(double montoSolicitado) { this.montoSolicitado = montoSolicitado; }
    
    public double getCuotaMensual() { return cuotaMensual; }
    public void setCuotaMensual(double cuotaMensual) { this.cuotaMensual = cuotaMensual; }
    
    public int getPlazoMeses() { return plazoMeses; }
    public void setPlazoMeses(int plazoMeses) { this.plazoMeses = plazoMeses; }
    
    public double getTasaMensual() { return tasaMensual; }
    public void setTasaMensual(double tasaMensual) { this.tasaMensual = tasaMensual; }
    
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }
    
    public double getSaldoActual() { return saldoActual; }
    public void setSaldoActual(double saldoActual) { this.saldoActual = saldoActual; }
    
    public String getFechaCreacion() { return fechaCreacion; }
    public void setFechaCreacion(String fechaCreacion) { this.fechaCreacion = fechaCreacion; }
    
    public String getFechaAprobacion() { return fechaAprobacion; }
    public void setFechaAprobacion(String fechaAprobacion) { this.fechaAprobacion = fechaAprobacion; }
    
    public String getGarantiaTipo() { return garantiaTipo; }
    public void setGarantiaTipo(String garantiaTipo) { this.garantiaTipo = garantiaTipo; }
    
    public String getObservaciones() { return observaciones; }
    public void setObservaciones(String observaciones) { this.observaciones = observaciones; }
}

