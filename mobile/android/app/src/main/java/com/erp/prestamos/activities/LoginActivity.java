package com.erp.prestamos.activities;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.AuthResponse;
import com.erp.prestamos.models.LoginRequest;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.gson.Gson;

public class LoginActivity extends AppCompatActivity {
    private android.widget.EditText etEmail, etPassword;
    private Button btnLogin;
    private ProgressBar progressBar;
    private TextView tvError;
    private ApiServiceHelper apiService;
    private SharedPreferences prefs;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);
        
        apiService = ApiServiceHelper.getInstance(this);
        prefs = getSharedPreferences("app_prefs", MODE_PRIVATE);
        
        // Verificar si ya está logueado
        if (prefs.contains("auth_token")) {
            startActivity(new Intent(this, DashboardActivity.class));
            finish();
            return;
        }
        
        initViews();
    }
    
    private void initViews() {
        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        progressBar = findViewById(R.id.progressBar);
        tvError = findViewById(R.id.tvError);
        
        btnLogin.setOnClickListener(v -> login());
    }
    
    private void login() {
        String email = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString();
        
        if (email.isEmpty() || password.isEmpty()) {
            showError("Por favor complete todos los campos");
            return;
        }
        
        showLoading(true);
        btnLogin.setEnabled(false);
        
        LoginRequest request = new LoginRequest(email, password);
        
        apiService.login(request, new ApiServiceHelper.LoginCallback() {
            @Override
            public void onSuccess(com.erp.prestamos.models.AuthResponse.AuthData authData) {
                runOnUiThread(() -> {
                    showLoading(false);
                    
                    // Guardar token
                    prefs.edit()
                        .putString("auth_token", authData.getToken())
                        .putString("user", new Gson().toJson(authData.getUser()))
                        .apply();
                    
                    // Registrar dispositivo FCM si hay token guardado
                    android.content.SharedPreferences authPrefs = getSharedPreferences("auth_prefs", MODE_PRIVATE);
                    String fcmToken = authPrefs.getString("fcm_token", null);
                    if (fcmToken != null) {
                        apiService.registrarDispositivoPush(fcmToken, "android", android.os.Build.MODEL);
                        authPrefs.edit().remove("fcm_token").apply();
                    }
                    
                    // Ir a dashboard
                    startActivity(new Intent(LoginActivity.this, DashboardActivity.class));
                    finish();
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    showLoading(false);
                    showError(error);
                });
            }
        });
    }
    
    private void showLoading(boolean show) {
        progressBar.setVisibility(show ? android.view.View.VISIBLE : android.view.View.GONE);
        btnLogin.setEnabled(!show);
    }
    
    private void showError(String message) {
        tvError.setText(message);
        tvError.setVisibility(android.view.View.VISIBLE);
    }
}
