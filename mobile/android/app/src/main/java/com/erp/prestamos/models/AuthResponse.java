package com.erp.prestamos.models;

import com.google.gson.annotations.SerializedName;

public class AuthResponse {
    @SerializedName("success")
    private boolean success;
    
    @SerializedName("data")
    private AuthData data;
    
    public boolean isSuccess() { return success; }
    public void setSuccess(boolean success) { this.success = success; }
    
    public AuthData getData() { return data; }
    public void setData(AuthData data) { this.data = data; }
    
    public static class AuthData {
        @SerializedName("token")
        private String token;
        
        @SerializedName("user")
        private User user;
        
        public String getToken() { return token; }
        public void setToken(String token) { this.token = token; }
        
        public User getUser() { return user; }
        public void setUser(User user) { this.user = user; }
    }
    
    public static class User {
        @SerializedName("id")
        private int id;
        
        @SerializedName("nombre")
        private String nombre;
        
        @SerializedName("apellido")
        private String apellido;
        
        @SerializedName("email")
        private String email;
        
        @SerializedName("rol")
        private String rol;
        
        public int getId() { return id; }
        public void setId(int id) { this.id = id; }
        
        public String getNombre() { return nombre; }
        public void setNombre(String nombre) { this.nombre = nombre; }
        
        public String getApellido() { return apellido; }
        public void setApellido(String apellido) { this.apellido = apellido; }
        
        public String getEmail() { return email; }
        public void setEmail(String email) { this.email = email; }
        
        public String getRol() { return rol; }
        public void setRol(String rol) { this.rol = rol; }
    }
}

