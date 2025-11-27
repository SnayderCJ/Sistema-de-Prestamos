package com.erp.prestamos.activities;

import android.app.DatePickerDialog;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.RutaRequest;
import com.erp.prestamos.services.ApiServiceHelper;
import java.util.Calendar;

public class CreateRutaActivity extends AppCompatActivity {
    private EditText etNombreRuta, etFechaRuta;
    private Button btnCrear, btnFecha;
    private ApiServiceHelper apiService;
    private Calendar calendar;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_ruta);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        calendar = Calendar.getInstance();
    }
    
    private void initViews() {
        etNombreRuta = findViewById(R.id.etNombreRuta);
        etFechaRuta = findViewById(R.id.etFechaRuta);
        btnCrear = findViewById(R.id.btnCrear);
        btnFecha = findViewById(R.id.btnFecha);
        
        btnFecha.setOnClickListener(v -> mostrarDatePicker());
        btnCrear.setOnClickListener(v -> crearRuta());
    }
    
    private void mostrarDatePicker() {
        DatePickerDialog datePickerDialog = new DatePickerDialog(
            this,
            (view, year, month, dayOfMonth) -> {
                calendar.set(year, month, dayOfMonth);
                // Formato: yyyy-MM-dd
                String fecha = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth);
                etFechaRuta.setText(fecha);
            },
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH)
        );
        datePickerDialog.show();
    }
    
    private void crearRuta() {
        String nombreRuta = etNombreRuta.getText().toString().trim();
        String fechaRuta = etFechaRuta.getText().toString().trim();
        
        if (nombreRuta.isEmpty() || fechaRuta.isEmpty()) {
            Toast.makeText(this, "Complete todos los campos", Toast.LENGTH_SHORT).show();
            return;
        }
        
        RutaRequest request = new RutaRequest();
        request.setNombreRuta(nombreRuta);
        request.setFechaRuta(fechaRuta);
        
        apiService.createRuta(request, new ApiServiceHelper.CreateRutaCallback() {
            @Override
            public void onSuccess(com.erp.prestamos.models.Ruta ruta) {
                runOnUiThread(() -> {
                    Toast.makeText(CreateRutaActivity.this, "Ruta creada correctamente", Toast.LENGTH_SHORT).show();
                    finish();
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    Toast.makeText(CreateRutaActivity.this, "Error: " + error, Toast.LENGTH_SHORT).show();
                });
            }
        });
    }
}

