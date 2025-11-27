package com.erp.prestamos.activities;

import android.content.Intent;
import android.os.Bundle;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.adapters.PrestamoVencidoAdapter;
import com.erp.prestamos.models.DashboardStats;
import com.erp.prestamos.models.Prestamo;
import com.erp.prestamos.services.ApiServiceHelper;
import java.util.ArrayList;
import java.util.List;

public class DashboardActivity extends AppCompatActivity {
    private TextView tvPrestamosActivos, tvMontoTotal, tvPrestamosVencidos, tvCobrosHoy;
    private RecyclerView recyclerViewPrestamosVencidos;
    private PrestamoVencidoAdapter adapter;
    private ApiServiceHelper apiService;
    private android.widget.Button btnVentas, btnCompras, btnArticulos;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadDashboard();
    }
    
    private void initViews() {
        tvPrestamosActivos = findViewById(R.id.tvPrestamosActivos);
        tvMontoTotal = findViewById(R.id.tvMontoTotal);
        tvPrestamosVencidos = findViewById(R.id.tvPrestamosVencidos);
        tvCobrosHoy = findViewById(R.id.tvCobrosHoy);
        
        recyclerViewPrestamosVencidos = findViewById(R.id.recyclerViewPrestamosVencidos);
        recyclerViewPrestamosVencidos.setLayoutManager(new LinearLayoutManager(this));
        adapter = new PrestamoVencidoAdapter(new ArrayList<>());
        recyclerViewPrestamosVencidos.setAdapter(adapter);
        
        btnVentas = findViewById(R.id.btnVentas);
        btnCompras = findViewById(R.id.btnCompras);
        btnArticulos = findViewById(R.id.btnArticulos);
        
        btnVentas.setOnClickListener(v -> {
            Intent intent = new Intent(this, VentasActivity.class);
            startActivity(intent);
        });
        
        btnCompras.setOnClickListener(v -> {
            Intent intent = new Intent(this, ComprasActivity.class);
            startActivity(intent);
        });
        
        btnArticulos.setOnClickListener(v -> {
            Intent intent = new Intent(this, ArticulosActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadDashboard() {
        // Mostrar loading
        showLoading(true);
        
        apiService.getDashboard(new ApiServiceHelper.DashboardCallback() {
            @Override
            public void onSuccess(DashboardStats stats, List<Prestamo> prestamosVencidos) {
                runOnUiThread(() -> {
                    showLoading(false);
                    
                    if (stats != null) {
                        tvPrestamosActivos.setText(String.valueOf(stats.getPrestamosActivos()));
                        tvMontoTotal.setText(formatCurrency(stats.getMontoTotal()));
                        tvPrestamosVencidos.setText(String.valueOf(stats.getPrestamosVencidos()));
                        tvCobrosHoy.setText(formatCurrency(stats.getCobrosHoy()));
                    }
                    
                    if (prestamosVencidos != null) {
                        adapter.updatePrestamos(prestamosVencidos);
                    }
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    showLoading(false);
                    android.widget.Toast.makeText(
                        DashboardActivity.this,
                        "Error al cargar dashboard: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    private void showLoading(boolean show) {
        // Implementar indicador de carga si existe
        // Por ahora solo ocultamos/mostramos contenido
    }
    
    private String formatCurrency(double amount) {
        return String.format("RD$ %.2f", amount);
    }
}

