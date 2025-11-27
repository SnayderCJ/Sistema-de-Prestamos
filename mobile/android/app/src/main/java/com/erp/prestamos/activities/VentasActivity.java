package com.erp.prestamos.activities;

import android.content.Intent;
import android.os.Bundle;
import android.view.Menu;
import android.view.MenuItem;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.SearchView;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;
import com.erp.prestamos.R;
import com.erp.prestamos.adapters.VentaAdapter;
import com.erp.prestamos.models.Venta;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class VentasActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private VentaAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    private SearchView searchView;
    private List<Venta> ventasList;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_ventas);
        
        getSupportActionBar().setTitle("Ventas");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadVentas();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(this::loadVentas);
        
        recyclerView = findViewById(R.id.recyclerViewVentas);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        ventasList = new ArrayList<>();
        adapter = new VentaAdapter(ventasList, venta -> {
            Intent intent = new Intent(this, VentaDetailActivity.class);
            intent.putExtra("venta_id", venta.getId());
            startActivity(intent);
        });
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreateVentaActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadVentas() {
        apiService.getVentas(new ApiServiceHelper.VentasCallback() {
            @Override
            public void onSuccess(List<Venta> ventas) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    if (ventas != null) {
                        ventasList.clear();
                        ventasList.addAll(ventas);
                        adapter.notifyDataSetChanged();
                    }
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        VentasActivity.this,
                        "Error al cargar ventas: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_ventas, menu);
        
        MenuItem searchItem = menu.findItem(R.id.action_search);
        searchView = (SearchView) searchItem.getActionView();
        searchView.setOnQueryTextListener(new SearchView.OnQueryTextListener() {
            @Override
            public boolean onQueryTextSubmit(String query) {
                filterVentas(query);
                return false;
            }
            
            @Override
            public boolean onQueryTextChange(String newText) {
                filterVentas(newText);
                return false;
            }
        });
        
        return true;
    }
    
    private void filterVentas(String query) {
        List<Venta> filtered = new ArrayList<>();
        if (query.isEmpty()) {
            filtered.addAll(ventasList);
        } else {
            for (Venta venta : ventasList) {
                if (venta.getNumeroFactura().toLowerCase().contains(query.toLowerCase()) ||
                    (venta.getClienteNombre() != null && venta.getClienteNombre().toLowerCase().contains(query.toLowerCase()))) {
                    filtered.add(venta);
                }
            }
        }
        adapter.updateVentas(filtered);
    }
    
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        if (item.getItemId() == android.R.id.home) {
            finish();
            return true;
        }
        return super.onOptionsItemSelected(item);
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        loadVentas();
    }
}

