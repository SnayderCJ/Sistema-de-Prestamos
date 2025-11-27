package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class Ruta {
    @SerializedName("id")
    private int id;
    
    @SerializedName("nombre_ruta")
    private String nombreRuta;
    
    @SerializedName("fecha_ruta")
    private String fechaRuta;
    
    @SerializedName("supervisor_id")
    private Integer supervisorId;
    
    @SerializedName("supervisor_nombre")
    private String supervisorNombre;
    
    @SerializedName("supervisor_apellido")
    private String supervisorApellido;
    
    @SerializedName("cobrador_id")
    private Integer cobradorId;
    
    @SerializedName("cobrador_nombre")
    private String cobradorNombre;
    
    @SerializedName("cobrador_apellido")
    private String cobradorApellido;
    
    @SerializedName("estado")
    private String estado;
    
    @SerializedName("total_visitas")
    private int totalVisitas;
    
    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getNombreRuta() { return nombreRuta; }
    public void setNombreRuta(String nombreRuta) { this.nombreRuta = nombreRuta; }
    
    public String getFechaRuta() { return fechaRuta; }
    public void setFechaRuta(String fechaRuta) { this.fechaRuta = fechaRuta; }
    
    public Integer getSupervisorId() { return supervisorId; }
    public void setSupervisorId(Integer supervisorId) { this.supervisorId = supervisorId; }
    
    public String getSupervisorNombre() { return supervisorNombre; }
    public void setSupervisorNombre(String supervisorNombre) { this.supervisorNombre = supervisorNombre; }
    
    public String getSupervisorApellido() { return supervisorApellido; }
    public void setSupervisorApellido(String supervisorApellido) { this.supervisorApellido = supervisorApellido; }
    
    public Integer getCobradorId() { return cobradorId; }
    public void setCobradorId(Integer cobradorId) { this.cobradorId = cobradorId; }
    
    public String getCobradorNombre() { return cobradorNombre; }
    public void setCobradorNombre(String cobradorNombre) { this.cobradorNombre = cobradorNombre; }
    
    public String getCobradorApellido() { return cobradorApellido; }
    public void setCobradorApellido(String cobradorApellido) { this.cobradorApellido = cobradorApellido; }
    
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }
    
    public int getTotalVisitas() { return totalVisitas; }
    public void setTotalVisitas(int totalVisitas) { this.totalVisitas = totalVisitas; }
}

