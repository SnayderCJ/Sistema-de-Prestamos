package com.erp.prestamos.activities;

import android.content.Intent;
import android.os.Bundle;
import android.view.Menu;
import android.view.MenuItem;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;
import com.erp.prestamos.R;
import com.erp.prestamos.adapters.RutaAdapter;
import com.erp.prestamos.models.*;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class RutasActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private RutaAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_rutas);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadRutas();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(() -> {
            loadRutas();
        });
        
        recyclerView = findViewById(R.id.recyclerViewRutas);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new RutaAdapter(new ArrayList<>(), ruta -> {
            Intent intent = new Intent(this, RutaDetailActivity.class);
            intent.putExtra("ruta_id", ruta.getId());
            startActivity(intent);
        });
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreateRutaActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadRutas() {
        apiService.getRutas(new ApiServiceHelper.RutasCallback() {
            @Override
            public void onSuccess(List<Ruta> rutas) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    adapter.updateRutas(rutas);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        RutasActivity.this,
                        "Error al cargar rutas: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_rutas, menu);
        return true;
    }
    
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        if (item.getItemId() == R.id.action_refresh) {
            loadRutas();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }
}

