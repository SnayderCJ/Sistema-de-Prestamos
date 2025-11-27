package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Prestamo;
import com.erp.prestamos.services.ApiServiceHelper;

public class PrestamoDetailActivity extends AppCompatActivity {
    private TextView tvNumero, tvCliente, tvMonto, tvCuota, tvEstado, tvPlazo, tvTasa, tvSaldo, tvCedula;
    private ApiServiceHelper apiService;
    private int prestamoId;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_prestamo_detail);
        
        prestamoId = getIntent().getIntExtra("prestamo_id", 0);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadPrestamo();
    }
    
    private void initViews() {
        tvNumero = findViewById(R.id.tvNumero);
        tvCliente = findViewById(R.id.tvCliente);
        tvMonto = findViewById(R.id.tvMonto);
        tvCuota = findViewById(R.id.tvCuota);
        tvEstado = findViewById(R.id.tvEstado);
        tvPlazo = findViewById(R.id.tvPlazo);
        tvTasa = findViewById(R.id.tvTasa);
        tvSaldo = findViewById(R.id.tvSaldo);
        tvCedula = findViewById(R.id.tvCedula);
    }
    
    private void loadPrestamo() {
        apiService.getPrestamo(prestamoId, new ApiServiceHelper.PrestamoCallback() {
            @Override
            public void onSuccess(Prestamo prestamo) {
                runOnUiThread(() -> mostrarPrestamo(prestamo));
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    tvNumero.setText("Error: " + error);
                });
            }
        });
    }
    
    private void mostrarPrestamo(Prestamo prestamo) {
        tvNumero.setText(prestamo.getNumeroPrestamo() != null ? prestamo.getNumeroPrestamo() : "-");
        tvCliente.setText((prestamo.getClienteNombre() != null ? prestamo.getClienteNombre() : "") + 
                         " " + (prestamo.getClienteApellido() != null ? prestamo.getClienteApellido() : ""));
        if (tvCedula != null) {
            tvCedula.setText(prestamo.getClienteCedula() != null ? prestamo.getClienteCedula() : "-");
        }
        tvMonto.setText(formatCurrency(prestamo.getMontoAprobado()));
        tvCuota.setText(formatCurrency(prestamo.getCuotaMensual()));
        tvEstado.setText(prestamo.getEstado() != null ? prestamo.getEstado() : "-");
        tvPlazo.setText(prestamo.getPlazoMeses() + " meses");
        tvTasa.setText(String.format("%.2f%%", prestamo.getTasaMensual()));
        tvSaldo.setText(formatCurrency(prestamo.getSaldoActual()));
    }
    
    private String formatCurrency(double amount) {
        return String.format("RD$ %.2f", amount);
    }
}

