package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class ClienteRequest {
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
    private Double ingresosMensuales;
    
    public ClienteRequest() {}
    
    public ClienteRequest(String cedula, String nombre, String apellido) {
        this.cedula = cedula;
        this.nombre = nombre;
        this.apellido = apellido;
    }
    
    // Getters and Setters
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
    
    public Double getIngresosMensuales() { return ingresosMensuales; }
    public void setIngresosMensuales(Double ingresosMensuales) { this.ingresosMensuales = ingresosMensuales; }
}

