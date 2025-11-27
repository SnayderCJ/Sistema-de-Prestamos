package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import java.text.NumberFormat;
import java.util.Locale;

public class CompraDetailActivity extends AppCompatActivity {
    private TextView tvNumero, tvProveedor, tvFecha, tvMonto, tvEstado, tvMetodoPago;
    private Button btnImprimir;
    private int compraId;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_compra_detail);
        
        getSupportActionBar().setTitle("Detalle de Compra");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        compraId = getIntent().getIntExtra("compra_id", 0);
        
        initViews();
        loadCompra();
    }
    
    private void initViews() {
        tvNumero = findViewById(R.id.tvNumero);
        tvProveedor = findViewById(R.id.tvProveedor);
        tvFecha = findViewById(R.id.tvFecha);
        tvMonto = findViewById(R.id.tvMonto);
        tvEstado = findViewById(R.id.tvEstado);
        tvMetodoPago = findViewById(R.id.tvMetodoPago);
        btnImprimir = findViewById(R.id.btnImprimir);
        
        btnImprimir.setOnClickListener(v -> {
            Toast.makeText(this, "Imprimiendo compra...", Toast.LENGTH_SHORT).show();
        });
    }
    
    private void loadCompra() {
        // Por ahora mostrar placeholder
        tvNumero.setText("COMP-001");
        tvProveedor.setText("Proveedor de Ejemplo");
        tvFecha.setText("2024-12-01");
        tvMonto.setText(formatCurrency(2500.00));
        tvEstado.setText("Pendiente");
        tvMetodoPago.setText("Transferencia");
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

