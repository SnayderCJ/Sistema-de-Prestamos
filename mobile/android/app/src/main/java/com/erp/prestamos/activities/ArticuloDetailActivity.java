package com.erp.prestamos.activities;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import java.text.NumberFormat;
import java.util.Locale;

public class ArticuloDetailActivity extends AppCompatActivity {
    private TextView tvCodigo, tvNombre, tvCategoria, tvPrecioCompra, tvPrecioVenta, tvStock;
    private Button btnEditar, btnAjustarStock;
    private int articuloId;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_articulo_detail);
        
        getSupportActionBar().setTitle("Detalle de Artículo");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        articuloId = getIntent().getIntExtra("articulo_id", 0);
        
        initViews();
        loadArticulo();
    }
    
    private void initViews() {
        tvCodigo = findViewById(R.id.tvCodigo);
        tvNombre = findViewById(R.id.tvNombre);
        tvCategoria = findViewById(R.id.tvCategoria);
        tvPrecioCompra = findViewById(R.id.tvPrecioCompra);
        tvPrecioVenta = findViewById(R.id.tvPrecioVenta);
        tvStock = findViewById(R.id.tvStock);
        btnEditar = findViewById(R.id.btnEditar);
        btnAjustarStock = findViewById(R.id.btnAjustarStock);
        
        btnEditar.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreateArticuloActivity.class);
            intent.putExtra("articulo_id", articuloId);
            startActivity(intent);
        });
        
        btnAjustarStock.setOnClickListener(v -> {
            Toast.makeText(this, "Ajustar stock próximamente", Toast.LENGTH_SHORT).show();
        });
    }
    
    private void loadArticulo() {
        // Por ahora mostrar placeholder
        tvCodigo.setText("ART-001");
        tvNombre.setText("Artículo de Ejemplo");
        tvCategoria.setText("General");
        tvPrecioCompra.setText(formatCurrency(50.00));
        tvPrecioVenta.setText(formatCurrency(100.00));
        tvStock.setText("50");
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

