package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Cliente;
import com.erp.prestamos.models.PrestamoRequest;
import com.erp.prestamos.models.TasaInteres;
import com.erp.prestamos.services.ApiServiceHelper;
import java.util.ArrayList;
import java.util.List;

public class CreatePrestamoActivity extends AppCompatActivity {
    private EditText etMonto, etPlazo;
    private Spinner spinnerCliente, spinnerTasa;
    private Button btnCrear;
    private ApiServiceHelper apiService;
    private List<Cliente> clientesList = new ArrayList<>();
    private List<TasaInteres> tasasList = new ArrayList<>();
    private ArrayAdapter<Cliente> clienteAdapter;
    private ArrayAdapter<TasaInteres> tasaAdapter;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_prestamo);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        cargarClientes();
        cargarTasas();
    }
    
    private void initViews() {
        etMonto = findViewById(R.id.etMonto);
        etPlazo = findViewById(R.id.etPlazo);
        spinnerCliente = findViewById(R.id.spinnerCliente);
        spinnerTasa = findViewById(R.id.spinnerTasa);
        btnCrear = findViewById(R.id.btnCrear);
        
        btnCrear.setOnClickListener(v -> crearPrestamo());
    }
    
    private void cargarClientes() {
        apiService.getClientes(new ApiServiceHelper.ClientesCallback() {
            @Override
            public void onSuccess(List<Cliente> clientes) {
                runOnUiThread(() -> {
                    clientesList = clientes != null ? clientes : new ArrayList<>();
                    clienteAdapter = new ArrayAdapter<>(CreatePrestamoActivity.this, 
                        android.R.layout.simple_spinner_item, clientesList);
                    clienteAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    spinnerCliente.setAdapter(clienteAdapter);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    Toast.makeText(CreatePrestamoActivity.this, 
                        "Error al cargar clientes: " + error, Toast.LENGTH_SHORT).show();
                });
            }
        });
    }
    
    private void cargarTasas() {
        apiService.getTasas(new ApiServiceHelper.TasasCallback() {
            @Override
            public void onSuccess(List<TasaInteres> tasas) {
                runOnUiThread(() -> {
                    tasasList = tasas != null ? tasas : new ArrayList<>();
                    
                    // Si no hay tasas desde la API, usar tasas por defecto
                    if (tasasList.isEmpty()) {
                        TasaInteres tasa1 = new TasaInteres();
                        tasa1.setId(1);
                        tasa1.setNombre("Tasa Estándar");
                        tasa1.setTasaAnual(24.0);
                        tasa1.setTasaMensual(2.0);
                        tasa1.setActiva(true);
                        tasasList.add(tasa1);
                        
                        TasaInteres tasa2 = new TasaInteres();
                        tasa2.setId(2);
                        tasa2.setNombre("Tasa Preferencial");
                        tasa2.setTasaAnual(18.0);
                        tasa2.setTasaMensual(1.5);
                        tasa2.setActiva(true);
                        tasasList.add(tasa2);
                        
                        TasaInteres tasa3 = new TasaInteres();
                        tasa3.setId(3);
                        tasa3.setNombre("Tasa Especial");
                        tasa3.setTasaAnual(30.0);
                        tasa3.setTasaMensual(2.5);
                        tasa3.setActiva(true);
                        tasasList.add(tasa3);
                    }
                    
                    tasaAdapter = new ArrayAdapter<>(CreatePrestamoActivity.this, 
                        android.R.layout.simple_spinner_item, tasasList);
                    tasaAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    spinnerTasa.setAdapter(tasaAdapter);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    // En caso de error, usar tasas por defecto
                    tasasList = new ArrayList<>();
                    
                    TasaInteres tasa1 = new TasaInteres();
                    tasa1.setId(1);
                    tasa1.setNombre("Tasa Estándar");
                    tasa1.setTasaAnual(24.0);
                    tasa1.setTasaMensual(2.0);
                    tasa1.setActiva(true);
                    tasasList.add(tasa1);
                    
                    TasaInteres tasa2 = new TasaInteres();
                    tasa2.setId(2);
                    tasa2.setNombre("Tasa Preferencial");
                    tasa2.setTasaAnual(18.0);
                    tasa2.setTasaMensual(1.5);
                    tasa2.setActiva(true);
                    tasasList.add(tasa2);
                    
                    tasaAdapter = new ArrayAdapter<>(CreatePrestamoActivity.this, 
                        android.R.layout.simple_spinner_item, tasasList);
                    tasaAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    spinnerTasa.setAdapter(tasaAdapter);
                });
            }
        });
    }
    
    private void crearPrestamo() {
        String montoStr = etMonto.getText().toString();
        String plazoStr = etPlazo.getText().toString();
        
        if (montoStr.isEmpty() || plazoStr.isEmpty()) {
            Toast.makeText(this, "Complete todos los campos", Toast.LENGTH_SHORT).show();
            return;
        }
        
        try {
            double monto = Double.parseDouble(montoStr);
            int plazo = Integer.parseInt(plazoStr);
            
            if (monto <= 0 || plazo <= 0) {
                Toast.makeText(this, "Los valores deben ser mayores a 0", Toast.LENGTH_SHORT).show();
                return;
            }
            
            // Obtener cliente_id y tasa_interes_id del spinner
            Cliente clienteSeleccionado = (Cliente) spinnerCliente.getSelectedItem();
            TasaInteres tasaSeleccionada = (TasaInteres) spinnerTasa.getSelectedItem();
            
            if (clienteSeleccionado == null) {
                Toast.makeText(this, "Seleccione un cliente", Toast.LENGTH_SHORT).show();
                return;
            }
            
            if (tasaSeleccionada == null) {
                Toast.makeText(this, "Seleccione una tasa de interés", Toast.LENGTH_SHORT).show();
                return;
            }
            
            int clienteId = clienteSeleccionado.getId();
            int tasaId = tasaSeleccionada.getId();
            
            PrestamoRequest request = new PrestamoRequest();
            request.setClienteId(clienteId);
            request.setMontoSolicitado(monto);
            request.setPlazoMeses(plazo);
            request.setTasaInteresId(tasaId);
            
            apiService.createPrestamo(request, new ApiServiceHelper.CreatePrestamoCallback() {
                @Override
                public void onSuccess(com.erp.prestamos.models.Prestamo prestamo) {
                    runOnUiThread(() -> {
                        Toast.makeText(CreatePrestamoActivity.this, "Préstamo creado correctamente", Toast.LENGTH_SHORT).show();
                        finish();
                    });
                }
                
                @Override
                public void onError(String error) {
                    runOnUiThread(() -> {
                        Toast.makeText(CreatePrestamoActivity.this, "Error: " + error, Toast.LENGTH_SHORT).show();
                    });
                }
            });
        } catch (NumberFormatException e) {
            Toast.makeText(this, "Valores inválidos", Toast.LENGTH_SHORT).show();
        }
    }
}

