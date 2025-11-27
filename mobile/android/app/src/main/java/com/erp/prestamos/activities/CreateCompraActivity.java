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
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Locale;

public class CreateCompraActivity extends AppCompatActivity {
    private Spinner spinnerProveedor, spinnerMetodoPago;
    private EditText etFecha, etDescuento;
    private TextView tvTotal;
    private Button btnGuardar, btnAgregarItem;
    private Calendar calendar;
    private SimpleDateFormat dateFormat;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_compra);
        
        getSupportActionBar().setTitle("Nueva Compra");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        calendar = Calendar.getInstance();
        dateFormat = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());
        
        initViews();
        setupProveedores();
        setupMetodoPago();
    }
    
    private void initViews() {
        spinnerProveedor = findViewById(R.id.spinnerProveedor);
        etFecha = findViewById(R.id.etFecha);
        etFecha.setText(dateFormat.format(calendar.getTime()));
        etFecha.setOnClickListener(v -> showDatePicker());
        spinnerMetodoPago = findViewById(R.id.spinnerMetodoPago);
        etDescuento = findViewById(R.id.etDescuento);
        tvTotal = findViewById(R.id.tvTotal);
        btnGuardar = findViewById(R.id.btnGuardar);
        btnAgregarItem = findViewById(R.id.btnAgregarItem);
        
        btnGuardar.setOnClickListener(v -> guardarCompra());
        btnAgregarItem.setOnClickListener(v -> {
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
    
    private void setupProveedores() {
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
            this,
            android.R.layout.simple_spinner_item,
            new String[]{"Seleccionar proveedor...", "Proveedor 1", "Proveedor 2", "Proveedor 3"}
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerProveedor.setAdapter(adapter);
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
    
    private void guardarCompra() {
        if (spinnerProveedor.getSelectedItemPosition() == 0) {
            Toast.makeText(this, "Seleccione un proveedor", Toast.LENGTH_SHORT).show();
            return;
        }
        
        Toast.makeText(this, "Guardando compra...", Toast.LENGTH_SHORT).show();
    }
    
    @Override
    public boolean onSupportNavigateUp() {
        finish();
        return true;
    }
}

