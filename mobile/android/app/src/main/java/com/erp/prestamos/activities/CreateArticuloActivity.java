package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.ArticuloRequest;
import com.erp.prestamos.services.ApiServiceHelper;

public class CreateArticuloActivity extends AppCompatActivity {
    private EditText etCodigo, etNombre, etDescripcion;
    private EditText etPrecioCompra, etPrecioVenta, etPrecioVentaCredito;
    private EditText etStock, etStockMinimo, etUtilidadPorcentaje;
    private Spinner spinnerCategoria;
    private Button btnGuardar;
    private ApiServiceHelper apiService;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_articulo);
        
        getSupportActionBar().setTitle("Nuevo Artículo");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        apiService = ApiServiceHelper.getInstance(this);
        initViews();
    }
    
    private void initViews() {
        etCodigo = findViewById(R.id.etCodigo);
        etNombre = findViewById(R.id.etNombre);
        etDescripcion = findViewById(R.id.etDescripcion);
        spinnerCategoria = findViewById(R.id.spinnerCategoria);
        etPrecioCompra = findViewById(R.id.etPrecioCompra);
        etPrecioVenta = findViewById(R.id.etPrecioVenta);
        etPrecioVentaCredito = findViewById(R.id.etPrecioVentaCredito);
        etStock = findViewById(R.id.etStock);
        etStockMinimo = findViewById(R.id.etStockMinimo);
        etUtilidadPorcentaje = findViewById(R.id.etUtilidadPorcentaje);
        btnGuardar = findViewById(R.id.btnGuardar);
        
        btnGuardar.setOnClickListener(v -> guardarArticulo());
        
        // Cargar categorías (por ahora placeholder)
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
            this,
            android.R.layout.simple_spinner_item,
            new String[]{"Seleccionar categoría...", "General", "Electrónica", "Ropa", "Alimentos"}
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerCategoria.setAdapter(adapter);
    }
    
    private void guardarArticulo() {
        if (etCodigo.getText().toString().trim().isEmpty()) {
            Toast.makeText(this, "El código es requerido", Toast.LENGTH_SHORT).show();
            return;
        }
        
        if (etNombre.getText().toString().trim().isEmpty()) {
            Toast.makeText(this, "El nombre es requerido", Toast.LENGTH_SHORT).show();
            return;
        }
        
        ArticuloRequest request = new ArticuloRequest();
        request.setCodigo(etCodigo.getText().toString().trim());
        request.setNombre(etNombre.getText().toString().trim());
        request.setDescripcion(etDescripcion.getText().toString().trim());
        request.setCategoriaId(spinnerCategoria.getSelectedItemPosition());
        
        try {
            request.setPrecioCompra(Double.parseDouble(etPrecioCompra.getText().toString()));
            request.setPrecioVenta(Double.parseDouble(etPrecioVenta.getText().toString()));
            if (!etPrecioVentaCredito.getText().toString().isEmpty()) {
                request.setPrecioVentaCredito(Double.parseDouble(etPrecioVentaCredito.getText().toString()));
            }
            request.setStock(Integer.parseInt(etStock.getText().toString()));
            request.setStockMinimo(Integer.parseInt(etStockMinimo.getText().toString()));
            if (!etUtilidadPorcentaje.getText().toString().isEmpty()) {
                request.setUtilidadPorcentaje(Double.parseDouble(etUtilidadPorcentaje.getText().toString()));
            }
        } catch (NumberFormatException e) {
            Toast.makeText(this, "Verifique los valores numéricos", Toast.LENGTH_SHORT).show();
            return;
        }
        
        // Implementar guardar artículo
        Toast.makeText(this, "Guardando artículo...", Toast.LENGTH_SHORT).show();
    }
    
    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}

