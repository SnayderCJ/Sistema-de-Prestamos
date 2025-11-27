package com.erp.prestamos;

import android.app.Application;
import android.util.Log;

import com.google.firebase.FirebaseApp;
import com.google.firebase.messaging.FirebaseMessaging;

public class MyApplication extends Application {
    private static final String TAG = "MyApplication";
    
    @Override
    public void onCreate() {
        super.onCreate();
        
        // Inicializar Firebase
        FirebaseApp.initializeApp(this);
        
        // Obtener token FCM
        FirebaseMessaging.getInstance().getToken()
            .addOnCompleteListener(task -> {
                if (!task.isSuccessful()) {
                    Log.w(TAG, "Fetching FCM registration token failed", task.getException());
                    return;
                }
                
                // Obtener nuevo token FCM
                String token = task.getResult();
                Log.d(TAG, "FCM Registration Token: " + token);
                
                // Enviar token al servidor si hay sesión activa
                android.content.SharedPreferences prefs = getSharedPreferences("auth_prefs", MODE_PRIVATE);
                String authToken = prefs.getString("auth_token", null);
                
                if (authToken != null) {
                    // Registrar dispositivo
                    com.erp.prestamos.services.ApiServiceHelper apiHelper = 
                        new com.erp.prestamos.services.ApiServiceHelper(this);
                    apiHelper.registrarDispositivoPush(token, "android", android.os.Build.MODEL);
                } else {
                    // Guardar token para enviarlo después del login
                    prefs.edit().putString("fcm_token", token).apply();
                }
            });
    }
}

