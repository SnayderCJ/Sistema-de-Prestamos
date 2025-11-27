package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.PagoRequest;
import com.erp.prestamos.models.Prestamo;
import com.erp.prestamos.services.ApiServiceHelper;
import java.util.ArrayList;
import java.util.List;

public class CreatePagoActivity extends AppCompatActivity {
    private EditText etMonto;
    private Spinner spinnerPrestamo, spinnerMetodoPago;
    private Button btnRegistrar;
    private ApiServiceHelper apiService;
    private List<Prestamo> prestamosList = new ArrayList<>();
    private ArrayAdapter<Prestamo> prestamoAdapter;
    private ArrayAdapter<String> metodoPagoAdapter;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_pago);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        cargarPrestamos();
    }
    
    private void initViews() {
        etMonto = findViewById(R.id.etMonto);
        spinnerPrestamo = findViewById(R.id.spinnerPrestamo);
        spinnerMetodoPago = findViewById(R.id.spinnerMetodoPago);
        btnRegistrar = findViewById(R.id.btnRegistrar);
        
        // Configurar spinner de métodos de pago
        List<String> metodosPago = new ArrayList<>();
        metodosPago.add("efectivo");
        metodosPago.add("transferencia");
        metodosPago.add("cheque");
        metodosPago.add("tarjeta");
        
        metodoPagoAdapter = new ArrayAdapter<>(this, 
            android.R.layout.simple_spinner_item, metodosPago);
        metodoPagoAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerMetodoPago.setAdapter(metodoPagoAdapter);
        
        btnRegistrar.setOnClickListener(v -> registrarPago());
    }
    
    private void cargarPrestamos() {
        apiService.getPrestamos(new ApiServiceHelper.PrestamosCallback() {
            @Override
            public void onSuccess(List<Prestamo> prestamos) {
                runOnUiThread(() -> {
                    prestamosList = prestamos != null ? prestamos : new ArrayList<>();
                    prestamoAdapter = new ArrayAdapter<>(CreatePagoActivity.this, 
                        android.R.layout.simple_spinner_item, prestamosList) {
                        @Override
                        public android.view.View getView(int position, android.view.View convertView, android.view.ViewGroup parent) {
                            android.view.View view = super.getView(position, convertView, parent);
                            Prestamo prestamo = prestamosList.get(position);
                            String displayText = prestamo.getNumeroPrestamo() != null ? 
                                prestamo.getNumeroPrestamo() : "Préstamo #" + prestamo.getId();
                            if (prestamo.getClienteNombre() != null) {
                                displayText += " - " + prestamo.getClienteNombre() + 
                                    (prestamo.getClienteApellido() != null ? " " + prestamo.getClienteApellido() : "");
                            }
                            ((android.widget.TextView) view).setText(displayText);
                            return view;
                        }
                        
                        @Override
                        public android.view.View getDropDownView(int position, android.view.View convertView, android.view.ViewGroup parent) {
                            return getView(position, convertView, parent);
                        }
                    };
                    prestamoAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    spinnerPrestamo.setAdapter(prestamoAdapter);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    Toast.makeText(CreatePagoActivity.this, 
                        "Error al cargar préstamos: " + error, Toast.LENGTH_SHORT).show();
                });
            }
        });
    }
    
    private void registrarPago() {
        String montoStr = etMonto.getText().toString();
        
        if (montoStr.isEmpty()) {
            Toast.makeText(this, "Complete todos los campos", Toast.LENGTH_SHORT).show();
            return;
        }
        
        try {
            double monto = Double.parseDouble(montoStr);
            
            if (monto <= 0) {
                Toast.makeText(this, "El monto debe ser mayor a 0", Toast.LENGTH_SHORT).show();
                return;
            }
            
            // Obtener prestamo_id del spinner y metodo_pago del spinner
            Prestamo prestamoSeleccionado = (Prestamo) spinnerPrestamo.getSelectedItem();
            String metodoPago = (String) spinnerMetodoPago.getSelectedItem();
            
            if (prestamoSeleccionado == null) {
                Toast.makeText(this, "Seleccione un préstamo", Toast.LENGTH_SHORT).show();
                return;
            }
            
            if (metodoPago == null || metodoPago.isEmpty()) {
                metodoPago = "efectivo";
            }
            
            int prestamoId = prestamoSeleccionado.getId();
            
            PagoRequest request = new PagoRequest();
            request.setPrestamoId(prestamoId);
            request.setMonto(monto);
            request.setMetodoPago(metodoPago);
            
            apiService.createPago(request, new ApiServiceHelper.CreatePagoCallback() {
                @Override
                public void onSuccess(com.erp.prestamos.models.Pago pago) {
                    runOnUiThread(() -> {
                        Toast.makeText(CreatePagoActivity.this, "Pago registrado correctamente", Toast.LENGTH_SHORT).show();
                        finish();
                    });
                }
                
                @Override
                public void onError(String error) {
                    runOnUiThread(() -> {
                        Toast.makeText(CreatePagoActivity.this, "Error: " + error, Toast.LENGTH_SHORT).show();
                    });
                }
            });
        } catch (NumberFormatException e) {
            Toast.makeText(this, "Monto inválido", Toast.LENGTH_SHORT).show();
        }
    }
}

