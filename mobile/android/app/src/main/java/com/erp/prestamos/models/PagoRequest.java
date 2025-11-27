package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class PagoRequest {
    @SerializedName("prestamo_id")
    private int prestamoId;
    
    @SerializedName("cuota_id")
    private Integer cuotaId;
    
    @SerializedName("monto")
    private double monto;
    
    @SerializedName("metodo_pago")
    private String metodoPago;
    
    @SerializedName("numero_comprobante")
    private String numeroComprobante;
    
    public PagoRequest() {}
    
    public PagoRequest(int prestamoId, double monto, String metodoPago) {
        this.prestamoId = prestamoId;
        this.monto = monto;
        this.metodoPago = metodoPago;
    }
    
    // Getters and Setters
    public int getPrestamoId() { return prestamoId; }
    public void setPrestamoId(int prestamoId) { this.prestamoId = prestamoId; }
    
    public Integer getCuotaId() { return cuotaId; }
    public void setCuotaId(Integer cuotaId) { this.cuotaId = cuotaId; }
    
    public double getMonto() { return monto; }
    public void setMonto(double monto) { this.monto = monto; }
    
    public String getMetodoPago() { return metodoPago; }
    public void setMetodoPago(String metodoPago) { this.metodoPago = metodoPago; }
    
    public String getNumeroComprobante() { return numeroComprobante; }
    public void setNumeroComprobante(String numeroComprobante) { this.numeroComprobante = numeroComprobante; }
}

