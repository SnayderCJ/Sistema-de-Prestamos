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
import com.erp.prestamos.adapters.PagoAdapter;
import com.erp.prestamos.models.*;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class PagosActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private PagoAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_pagos);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadPagos();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(() -> {
            loadPagos();
        });
        
        recyclerView = findViewById(R.id.recyclerViewPagos);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new PagoAdapter(new ArrayList<>());
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreatePagoActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadPagos() {
        apiService.getPagos(new ApiServiceHelper.PagosCallback() {
            @Override
            public void onSuccess(List<Pago> pagos) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    adapter.updatePagos(pagos);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        PagosActivity.this,
                        "Error al cargar pagos: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_pagos, menu);
        return true;
    }
    
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        if (item.getItemId() == R.id.action_refresh) {
            loadPagos();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }
}

