package com.erp.prestamos.services;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.media.RingtoneManager;
import android.os.Build;
import android.util.Log;

import androidx.core.app.NotificationCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

import com.erp.prestamos.R;
import com.erp.prestamos.activities.DashboardActivity;

public class MyFirebaseMessagingService extends FirebaseMessagingService {
    private static final String TAG = "FCMService";
    private static final String CHANNEL_ID = "prestamos_notifications";
    
    @Override
    public void onNewToken(String token) {
        Log.d(TAG, "Refreshed token: " + token);
        // Enviar token al servidor
        sendRegistrationToServer(token);
    }
    
    @Override
    public void onMessageReceived(RemoteMessage remoteMessage) {
        Log.d(TAG, "From: " + remoteMessage.getFrom());
        
        // Verificar si el mensaje contiene datos
        if (remoteMessage.getData().size() > 0) {
            Log.d(TAG, "Message data payload: " + remoteMessage.getData());
        }
        
        // Verificar si el mensaje contiene notificación
        if (remoteMessage.getNotification() != null) {
            Log.d(TAG, "Message Notification Body: " + remoteMessage.getNotification().getBody());
            sendNotification(
                remoteMessage.getNotification().getTitle(),
                remoteMessage.getNotification().getBody(),
                remoteMessage.getData()
            );
        }
    }
    
    private void sendNotification(String title, String messageBody, java.util.Map<String, String> data) {
        Intent intent = new Intent(this, DashboardActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
        
        // Agregar datos extras si existen
        if (data != null && data.containsKey("prestamo_id")) {
            intent.putExtra("prestamo_id", data.get("prestamo_id"));
        }
        
        PendingIntent pendingIntent = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_IMMUTABLE | PendingIntent.FLAG_UPDATE_CURRENT
        );
        
        // Crear canal de notificación (requerido para Android 8.0+)
        createNotificationChannel();
        
        NotificationCompat.Builder notificationBuilder = new NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title != null ? title : "ERP Prestamos")
            .setContentText(messageBody)
            .setAutoCancel(true)
            .setSound(RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION))
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setDefaults(NotificationCompat.DEFAULT_ALL);
        
        NotificationManager notificationManager =
            (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
        
        if (notificationManager != null) {
            notificationManager.notify(0, notificationBuilder.build());
        }
    }
    
    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            CharSequence name = "Notificaciones Prestamos";
            String description = "Canal para notificaciones de préstamos";
            int importance = NotificationManager.IMPORTANCE_HIGH;
            NotificationChannel channel = new NotificationChannel(CHANNEL_ID, name, importance);
            channel.setDescription(description);
            channel.enableLights(true);
            channel.enableVibration(true);
            
            NotificationManager notificationManager = getSystemService(NotificationManager.class);
            if (notificationManager != null) {
                notificationManager.createNotificationChannel(channel);
            }
        }
    }
    
    private void sendRegistrationToServer(String token) {
        // Obtener token de autenticación guardado
        android.content.SharedPreferences prefs = getSharedPreferences("auth_prefs", Context.MODE_PRIVATE);
        String authToken = prefs.getString("auth_token", null);
        
        if (authToken == null) {
            Log.d(TAG, "No hay token de autenticación, guardando token FCM para más tarde");
            // Guardar token para enviarlo después del login
            prefs.edit().putString("fcm_token", token).apply();
            return;
        }
        
        // Enviar token al servidor
        new Thread(() -> {
            try {
                com.erp.prestamos.services.ApiServiceHelper apiHelper = 
                    new com.erp.prestamos.services.ApiServiceHelper(getApplicationContext());
                apiHelper.registrarDispositivoPush(token, "android", Build.MODEL);
            } catch (Exception e) {
                Log.e(TAG, "Error registrando token FCM: " + e.getMessage());
            }
        }).start();
    }
}

