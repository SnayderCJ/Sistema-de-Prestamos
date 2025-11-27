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
import com.erp.prestamos.adapters.CompraAdapter;
import com.erp.prestamos.models.Compra;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class ComprasActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private CompraAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    private SearchView searchView;
    private List<Compra> comprasList;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_compras);
        
        getSupportActionBar().setTitle("Compras");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadCompras();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(this::loadCompras);
        
        recyclerView = findViewById(R.id.recyclerViewCompras);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        comprasList = new ArrayList<>();
        adapter = new CompraAdapter(comprasList, compra -> {
            Intent intent = new Intent(this, CompraDetailActivity.class);
            intent.putExtra("compra_id", compra.getId());
            startActivity(intent);
        });
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreateCompraActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadCompras() {
        apiService.getCompras(new ApiServiceHelper.ComprasCallback() {
            @Override
            public void onSuccess(List<Compra> compras) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    if (compras != null) {
                        comprasList.clear();
                        comprasList.addAll(compras);
                        adapter.notifyDataSetChanged();
                    }
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        ComprasActivity.this,
                        "Error al cargar compras: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_compras, menu);
        
        MenuItem searchItem = menu.findItem(R.id.action_search);
        searchView = (SearchView) searchItem.getActionView();
        searchView.setOnQueryTextListener(new SearchView.OnQueryTextListener() {
            @Override
            public boolean onQueryTextSubmit(String query) {
                filterCompras(query);
                return false;
            }
            
            @Override
            public boolean onQueryTextChange(String newText) {
                filterCompras(newText);
                return false;
            }
        });
        
        return true;
    }
    
    private void filterCompras(String query) {
        List<Compra> filtered = new ArrayList<>();
        if (query.isEmpty()) {
            filtered.addAll(comprasList);
        } else {
            for (Compra compra : comprasList) {
                if (compra.getNumeroFactura().toLowerCase().contains(query.toLowerCase()) ||
                    (compra.getProveedorNombre() != null && compra.getProveedorNombre().toLowerCase().contains(query.toLowerCase()))) {
                    filtered.add(compra);
                }
            }
        }
        adapter.updateCompras(filtered);
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
        loadCompras();
    }
}

