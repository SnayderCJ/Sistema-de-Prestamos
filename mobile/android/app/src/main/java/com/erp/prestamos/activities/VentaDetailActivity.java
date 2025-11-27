package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Venta;
import com.erp.prestamos.services.ApiServiceHelper;
import java.text.NumberFormat;
import java.util.Locale;

public class VentaDetailActivity extends AppCompatActivity {
    private TextView tvNumero, tvCliente, tvFecha, tvMonto, tvEstado, tvMetodoPago;
    private Button btnImprimir, btnEnviarEmail;
    private ApiServiceHelper apiService;
    private int ventaId;
    private Venta venta;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_venta_detail);
        
        getSupportActionBar().setTitle("Detalle de Venta");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        ventaId = getIntent().getIntExtra("venta_id", 0);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadVenta();
    }
    
    private void initViews() {
        tvNumero = findViewById(R.id.tvNumero);
        tvCliente = findViewById(R.id.tvCliente);
        tvFecha = findViewById(R.id.tvFecha);
        tvMonto = findViewById(R.id.tvMonto);
        tvEstado = findViewById(R.id.tvEstado);
        tvMetodoPago = findViewById(R.id.tvMetodoPago);
        btnImprimir = findViewById(R.id.btnImprimir);
        btnEnviarEmail = findViewById(R.id.btnEnviarEmail);
        
        btnImprimir.setOnClickListener(v -> {
            Toast.makeText(this, "Imprimiendo factura...", Toast.LENGTH_SHORT).show();
        });
        
        btnEnviarEmail.setOnClickListener(v -> {
            Toast.makeText(this, "Enviando por email...", Toast.LENGTH_SHORT).show();
        });
    }
    
    private void loadVenta() {
        // Por ahora usar datos del intent o cargar desde API
        if (ventaId > 0) {
            // Cargar venta desde API
            // Por ahora mostrar placeholder
            tvNumero.setText("FAC-001");
            tvCliente.setText("Cliente de Ejemplo");
            tvFecha.setText("2024-12-01");
            tvMonto.setText(formatCurrency(1500.00));
            tvEstado.setText("Completada");
            tvMetodoPago.setText("Efectivo");
        }
    }
    
    private String formatCurrency(double amount) {
        NumberFormat formatter = NumberFormat.getCurrencyInstance(new Locale("es", "DO"));
        formatter.setCurrency(java.util.Currency.getInstance("DOP"));
        return formatter.format(amount);
    }
    
    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}

