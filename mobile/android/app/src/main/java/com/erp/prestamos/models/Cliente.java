package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class Cliente {
    @SerializedName("id")
    private int id;
    
    @SerializedName("cedula")
    private String cedula;
    
    @SerializedName("nombre")
    private String nombre;
    
    @SerializedName("apellido")
    private String apellido;
    
    @SerializedName("email")
    private String email;
    
    @SerializedName("telefono")
    private String telefono;
    
    @SerializedName("direccion")
    private String direccion;
    
    @SerializedName("ciudad")
    private String ciudad;
    
    @SerializedName("provincia")
    private String provincia;
    
    @SerializedName("ocupacion")
    private String ocupacion;
    
    @SerializedName("ingresos_mensuales")
    private double ingresosMensuales;
    
    @SerializedName("estado_credito")
    private String estadoCredito;
    
    @SerializedName("total_prestamos")
    private int totalPrestamos;
    
    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getCedula() { return cedula; }
    public void setCedula(String cedula) { this.cedula = cedula; }
    
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    
    public String getApellido() { return apellido; }
    public void setApellido(String apellido) { this.apellido = apellido; }
    
    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }
    
    public String getTelefono() { return telefono; }
    public void setTelefono(String telefono) { this.telefono = telefono; }
    
    public String getDireccion() { return direccion; }
    public void setDireccion(String direccion) { this.direccion = direccion; }
    
    public String getCiudad() { return ciudad; }
    public void setCiudad(String ciudad) { this.ciudad = ciudad; }
    
    public String getProvincia() { return provincia; }
    public void setProvincia(String provincia) { this.provincia = provincia; }
    
    public String getOcupacion() { return ocupacion; }
    public void setOcupacion(String ocupacion) { this.ocupacion = ocupacion; }
    
    public double getIngresosMensuales() { return ingresosMensuales; }
    public void setIngresosMensuales(double ingresosMensuales) { this.ingresosMensuales = ingresosMensuales; }
    
    public String getEstadoCredito() { return estadoCredito; }
    public void setEstadoCredito(String estadoCredito) { this.estadoCredito = estadoCredito; }
    
    public int getTotalPrestamos() { return totalPrestamos; }
    public void setTotalPrestamos(int totalPrestamos) { this.totalPrestamos = totalPrestamos; }
}

