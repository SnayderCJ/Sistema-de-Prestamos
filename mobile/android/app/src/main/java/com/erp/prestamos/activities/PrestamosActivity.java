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
import com.erp.prestamos.adapters.PrestamoAdapter;
import com.erp.prestamos.models.Prestamo;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class PrestamosActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private PrestamoAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_prestamos);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadPrestamos();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(() -> {
            loadPrestamos();
        });
        
        recyclerView = findViewById(R.id.recyclerViewPrestamos);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new PrestamoAdapter(new ArrayList<>(), prestamo -> {
            Intent intent = new Intent(this, PrestamoDetailActivity.class);
            intent.putExtra("prestamo_id", prestamo.getId());
            startActivity(intent);
        });
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreatePrestamoActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadPrestamos() {
        apiService.getPrestamos(new ApiServiceHelper.PrestamosCallback() {
            @Override
            public void onSuccess(List<Prestamo> prestamos) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    if (prestamos != null) {
                        adapter.updatePrestamos(prestamos);
                    }
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        PrestamosActivity.this,
                        "Error al cargar préstamos: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_prestamos, menu);
        return true;
    }
    
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        if (item.getItemId() == R.id.action_refresh) {
            loadPrestamos();
            return true;
        } else if (item.getItemId() == R.id.action_calculator) {
            Intent intent = new Intent(this, CalculadoraPrestamoActivity.class);
            startActivity(intent);
            return true;
        }
        return super.onOptionsItemSelected(item);
    }
}

