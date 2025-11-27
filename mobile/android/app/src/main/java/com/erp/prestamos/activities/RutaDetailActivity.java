package com.erp.prestamos.activities;

import android.os.Bundle;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Ruta;
import com.erp.prestamos.services.ApiServiceHelper;

public class RutaDetailActivity extends AppCompatActivity {
    private TextView tvNombre, tvFecha, tvSupervisor, tvCobrador, tvEstado, tvVisitas;
    private ApiServiceHelper apiService;
    private int rutaId;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_ruta_detail);
        
        rutaId = getIntent().getIntExtra("ruta_id", 0);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadRuta();
    }
    
    private void initViews() {
        tvNombre = findViewById(R.id.tvNombre);
        tvFecha = findViewById(R.id.tvFecha);
        tvSupervisor = findViewById(R.id.tvSupervisor);
        tvCobrador = findViewById(R.id.tvCobrador);
        tvEstado = findViewById(R.id.tvEstado);
        tvVisitas = findViewById(R.id.tvVisitas);
    }
    
    private void loadRuta() {
        apiService.getRuta(rutaId, new ApiServiceHelper.RutaCallback() {
            @Override
            public void onSuccess(Ruta ruta) {
                runOnUiThread(() -> mostrarRuta(ruta));
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    tvNombre.setText("Error: " + error);
                });
            }
        });
    }
    
    private void mostrarRuta(Ruta ruta) {
        tvNombre.setText(ruta.getNombreRuta());
        tvFecha.setText(ruta.getFechaRuta());
        tvSupervisor.setText((ruta.getSupervisorNombre() != null ? ruta.getSupervisorNombre() : "") + 
                           " " + (ruta.getSupervisorApellido() != null ? ruta.getSupervisorApellido() : ""));
        tvCobrador.setText((ruta.getCobradorNombre() != null ? ruta.getCobradorNombre() : "") + 
                          " " + (ruta.getCobradorApellido() != null ? ruta.getCobradorApellido() : ""));
        tvEstado.setText(ruta.getEstado());
        tvVisitas.setText(String.valueOf(ruta.getTotalVisitas()));
    }
}

