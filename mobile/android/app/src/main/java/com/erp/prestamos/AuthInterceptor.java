package com.erp.prestamos;

import android.content.Context;
import android.content.SharedPreferences;
import okhttp3.Interceptor;
import okhttp3.Request;
import okhttp3.Response;
import java.io.IOException;

public class AuthInterceptor implements Interceptor {
    private SharedPreferences prefs;
    private Context context;
    
    public AuthInterceptor(Context context) {
        this.context = context;
        this.prefs = context.getSharedPreferences("app_prefs", Context.MODE_PRIVATE);
    }
    
    @Override
    public Response intercept(Chain chain) throws IOException {
        Request original = chain.request();
        String token = getToken();
        
        Request.Builder requestBuilder = original.newBuilder()
            .header("Content-Type", "application/json");
        
        if (token != null && !token.isEmpty()) {
            requestBuilder.header("Authorization", "Bearer " + token);
        }
        
        Request request = requestBuilder.build();
        Response response = chain.proceed(request);
        
        // Manejar errores 401 (no autorizado)
        if (response.code() == 401) {
            // Limpiar token
            clearToken();
        }
        
        return response;
    }
    
    private String getToken() {
        return prefs.getString("auth_token", null);
    }
    
    private void clearToken() {
        prefs.edit().remove("auth_token").apply();
    }
}
