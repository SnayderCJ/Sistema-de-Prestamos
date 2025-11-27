package com.erp.prestamos.activities;

import android.app.DatePickerDialog;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.VentaRequest;
import com.erp.prestamos.services.ApiServiceHelper;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.Locale;

public class CreateVentaActivity extends AppCompatActivity {
    private Spinner spinnerCliente, spinnerMetodoPago;
    private EditText etFecha, etDescuento;
    private TextView tvTotal;
    private Button btnGuardar, btnAgregarItem;
    private ApiServiceHelper apiService;
    private Calendar calendar;
    private SimpleDateFormat dateFormat;
    private List<VentaRequest.VentaItem> items;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_venta);
        
        getSupportActionBar().setTitle("Nueva Venta");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        calendar = Calendar.getInstance();
        dateFormat = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());
        items = new ArrayList<>();
        apiService = ApiServiceHelper.getInstance(this);
        
        initViews();
        loadClientes();
        setupMetodoPago();
    }
    
    private void initViews() {
        spinnerCliente = findViewById(R.id.spinnerCliente);
        etFecha = findViewById(R.id.etFecha);
        etFecha.setText(dateFormat.format(calendar.getTime()));
        etFecha.setOnClickListener(v -> showDatePicker());
        spinnerMetodoPago = findViewById(R.id.spinnerMetodoPago);
        etDescuento = findViewById(R.id.etDescuento);
        tvTotal = findViewById(R.id.tvTotal);
        btnGuardar = findViewById(R.id.btnGuardar);
        btnAgregarItem = findViewById(R.id.btnAgregarItem);
        
        btnGuardar.setOnClickListener(v -> guardarVenta());
        btnAgregarItem.setOnClickListener(v -> {
            // Abrir diálogo para agregar artículo
            Toast.makeText(this, "Funcionalidad de agregar artículo próximamente", Toast.LENGTH_SHORT).show();
        });
    }
    
    private void showDatePicker() {
        DatePickerDialog datePicker = new DatePickerDialog(
            this,
            (view, year, month, dayOfMonth) -> {
                calendar.set(year, month, dayOfMonth);
                etFecha.setText(dateFormat.format(calendar.getTime()));
            },
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH)
        );
        datePicker.show();
    }
    
    private void loadClientes() {
        apiService.getClientes(new ApiServiceHelper.ClientesCallback() {
            @Override
            public void onSuccess(List<com.erp.prestamos.models.Cliente> clientes) {
                runOnUiThread(() -> {
                    List<String> nombres = new ArrayList<>();
                    nombres.add("Seleccionar cliente...");
                    for (com.erp.prestamos.models.Cliente c : clientes) {
                        nombres.add(c.getNombre() + " " + c.getApellido());
                    }
                    ArrayAdapter<String> adapter = new ArrayAdapter<>(
                        CreateVentaActivity.this,
                        android.R.layout.simple_spinner_item,
                        nombres
                    );
                    adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    spinnerCliente.setAdapter(adapter);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    Toast.makeText(CreateVentaActivity.this, "Error al cargar clientes", Toast.LENGTH_SHORT).show();
                });
            }
        });
    }
    
    private void setupMetodoPago() {
        ArrayAdapter<CharSequence> adapter = ArrayAdapter.createFromResource(
            this,
            R.array.metodos_pago,
            android.R.layout.simple_spinner_item
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerMetodoPago.setAdapter(adapter);
    }
    
    private void guardarVenta() {
        if (spinnerCliente.getSelectedItemPosition() == 0) {
            Toast.makeText(this, "Seleccione un cliente", Toast.LENGTH_SHORT).show();
            return;
        }
        
        // Implementar guardar venta
        Toast.makeText(this, "Guardando venta...", Toast.LENGTH_SHORT).show();
    }
    
    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}

