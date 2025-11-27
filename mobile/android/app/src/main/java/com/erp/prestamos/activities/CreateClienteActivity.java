package com.erp.prestamos.activities;

import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.ClienteRequest;
import com.erp.prestamos.services.ApiServiceHelper;
import com.erp.prestamos.utils.CedulaValidator;

public class CreateClienteActivity extends AppCompatActivity {
    private EditText etCedula, etNombre, etApellido, etTelefono, etEmail, etDireccion;
    private TextView tvCedulaError;
    private Button btnCrear;
    private ApiServiceHelper apiService;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_cliente);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
    }
    
    private void initViews() {
        etCedula = findViewById(R.id.etCedula);
        etNombre = findViewById(R.id.etNombre);
        etApellido = findViewById(R.id.etApellido);
        etTelefono = findViewById(R.id.etTelefono);
        etEmail = findViewById(R.id.etEmail);
        etDireccion = findViewById(R.id.etDireccion);
        btnCrear = findViewById(R.id.btnCrear);
        tvCedulaError = findViewById(R.id.tvCedulaError);
        
        // Validación de cédula en tiempo real
        etCedula.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                String cedula = s.toString().trim();
                if (cedula.length() == 11) {
                    if (CedulaValidator.validarCedula(cedula)) {
                        tvCedulaError.setText("✓ Cédula válida");
                        tvCedulaError.setTextColor(getResources().getColor(android.R.color.holo_green_dark));
                        etCedula.setText(CedulaValidator.formatearCedula(cedula));
                    } else {
                        tvCedulaError.setText("✗ Cédula inválida");
                        tvCedulaError.setTextColor(getResources().getColor(android.R.color.holo_red_dark));
                    }
                } else if (cedula.length() > 0) {
                    tvCedulaError.setText("La cédula debe tener 11 dígitos");
                    tvCedulaError.setTextColor(getResources().getColor(android.R.color.holo_orange_dark));
                } else {
                    tvCedulaError.setText("");
                }
            }
            
            @Override
            public void afterTextChanged(Editable s) {}
        });
        
        btnCrear.setOnClickListener(v -> crearCliente());
    }
    
    private void crearCliente() {
        String cedula = etCedula.getText().toString().trim().replaceAll("[\\s-]", "");
        String nombre = etNombre.getText().toString().trim();
        String apellido = etApellido.getText().toString().trim();
        
        if (cedula.isEmpty() || nombre.isEmpty() || apellido.isEmpty()) {
            Toast.makeText(this, "Complete los campos requeridos", Toast.LENGTH_SHORT).show();
            return;
        }
        
        if (!CedulaValidator.validarCedula(cedula)) {
            Toast.makeText(this, "La cédula ingresada no es válida", Toast.LENGTH_SHORT).show();
            return;
        }
        
        ClienteRequest request = new ClienteRequest();
        request.setCedula(cedula);
        request.setNombre(nombre);
        request.setApellido(apellido);
        
        String telefono = etTelefono.getText().toString().trim();
        if (!telefono.isEmpty()) {
            request.setTelefono(telefono);
        }
        
        String email = etEmail.getText().toString().trim();
        if (!email.isEmpty()) {
            request.setEmail(email);
        }
        
        String direccion = etDireccion.getText().toString().trim();
        if (!direccion.isEmpty()) {
            request.setDireccion(direccion);
        }
        
        apiService.createCliente(request, new ApiServiceHelper.CreateClienteCallback() {
            @Override
            public void onSuccess(com.erp.prestamos.models.Cliente cliente) {
                runOnUiThread(() -> {
                    Toast.makeText(CreateClienteActivity.this, "Cliente creado correctamente", Toast.LENGTH_SHORT).show();
                    finish();
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    Toast.makeText(CreateClienteActivity.this, "Error: " + error, Toast.LENGTH_SHORT).show();
                });
            }
        });
    }
}

