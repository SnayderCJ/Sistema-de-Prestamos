package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Cliente;
import com.erp.prestamos.services.ApiServiceHelper;

public class ClienteDetailActivity extends AppCompatActivity {
    private TextView tvCedula, tvNombre, tvEmail, tvTelefono, tvDireccion, tvIngresos;
    private ApiServiceHelper apiService;
    private int clienteId;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_cliente_detail);
        
        clienteId = getIntent().getIntExtra("cliente_id", 0);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadCliente();
    }
    
    private void initViews() {
        tvCedula = findViewById(R.id.tvCedula);
        tvNombre = findViewById(R.id.tvNombre);
        tvEmail = findViewById(R.id.tvEmail);
        tvTelefono = findViewById(R.id.tvTelefono);
        tvDireccion = findViewById(R.id.tvDireccion);
        tvIngresos = findViewById(R.id.tvIngresos);
    }
    
    private void loadCliente() {
        apiService.getCliente(clienteId, new ApiServiceHelper.ClienteCallback() {
            @Override
            public void onSuccess(Cliente cliente) {
                runOnUiThread(() -> mostrarCliente(cliente));
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    tvCedula.setText("Error: " + error);
                });
            }
        });
    }
    
    private void mostrarCliente(Cliente cliente) {
        tvCedula.setText(cliente.getCedula());
        tvNombre.setText(cliente.getNombre() + " " + cliente.getApellido());
        tvEmail.setText(cliente.getEmail() != null ? cliente.getEmail() : "-");
        tvTelefono.setText(cliente.getTelefono() != null ? cliente.getTelefono() : "-");
        tvDireccion.setText(cliente.getDireccion() != null ? cliente.getDireccion() : "-");
        tvIngresos.setText(formatCurrency(cliente.getIngresosMensuales()));
    }
    
    private String formatCurrency(double amount) {
        return String.format("RD$ %.2f", amount);
    }
}

