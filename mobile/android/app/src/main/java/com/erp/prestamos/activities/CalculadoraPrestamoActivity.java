package com.erp.prestamos.activities;

import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.widget.EditText;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.utils.PrestamoCalculator;

public class CalculadoraPrestamoActivity extends AppCompatActivity {
    private EditText etMonto, etTasa, etPlazo;
    private TextView tvCuotaMensual, tvMontoTotal, tvInteresTotal, tvTasaAnual;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_calculadora_prestamo);
        
        initViews();
        setupTextWatchers();
    }
    
    private void initViews() {
        etMonto = findViewById(R.id.etMonto);
        etTasa = findViewById(R.id.etTasa);
        etPlazo = findViewById(R.id.etPlazo);
        tvCuotaMensual = findViewById(R.id.tvCuotaMensual);
        tvMontoTotal = findViewById(R.id.tvMontoTotal);
        tvInteresTotal = findViewById(R.id.tvInteresTotal);
        tvTasaAnual = findViewById(R.id.tvTasaAnual);
    }
    
    private void setupTextWatchers() {
        TextWatcher watcher = new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                calcular();
            }
            
            @Override
            public void afterTextChanged(Editable s) {}
        };
        
        etMonto.addTextChangedListener(watcher);
        etTasa.addTextChangedListener(watcher);
        etPlazo.addTextChangedListener(watcher);
    }
    
    private void calcular() {
        try {
            String montoStr = etMonto.getText().toString();
            String tasaStr = etTasa.getText().toString();
            String plazoStr = etPlazo.getText().toString();
            
            if (montoStr.isEmpty() || tasaStr.isEmpty() || plazoStr.isEmpty()) {
                limpiarResultados();
                return;
            }
            
            double monto = Double.parseDouble(montoStr);
            double tasaMensual = Double.parseDouble(tasaStr);
            int plazoMeses = Integer.parseInt(plazoStr);
            
            if (monto <= 0 || tasaMensual < 0 || plazoMeses <= 0) {
                limpiarResultados();
                return;
            }
            
            double cuotaMensual = PrestamoCalculator.calcularCuotaMensual(monto, tasaMensual, plazoMeses);
            double montoTotal = PrestamoCalculator.calcularMontoTotal(cuotaMensual, plazoMeses);
            double interesTotal = PrestamoCalculator.calcularInteresTotal(montoTotal, monto);
            double tasaAnual = PrestamoCalculator.calcularTasaAnual(tasaMensual);
            
            tvCuotaMensual.setText(String.format("RD$ %.2f", cuotaMensual));
            tvMontoTotal.setText(String.format("RD$ %.2f", montoTotal));
            tvInteresTotal.setText(String.format("RD$ %.2f", interesTotal));
            tvTasaAnual.setText(String.format("%.2f%%", tasaAnual));
        } catch (NumberFormatException e) {
            limpiarResultados();
        }
    }
    
    private void limpiarResultados() {
        tvCuotaMensual.setText("RD$ 0.00");
        tvMontoTotal.setText("RD$ 0.00");
        tvInteresTotal.setText("RD$ 0.00");
        tvTasaAnual.setText("0.00%");
    }
}

