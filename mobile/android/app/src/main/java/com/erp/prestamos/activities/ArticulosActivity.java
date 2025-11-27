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
import com.erp.prestamos.adapters.ArticuloAdapter;
import com.erp.prestamos.models.Articulo;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class ArticulosActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private ArticuloAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    private SearchView searchView;
    private List<Articulo> articulosList;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_articulos);
        
        getSupportActionBar().setTitle("Artículos");
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadArticulos();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(this::loadArticulos);
        
        recyclerView = findViewById(R.id.recyclerViewArticulos);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        articulosList = new ArrayList<>();
        adapter = new ArticuloAdapter(articulosList, articulo -> {
            Intent intent = new Intent(this, ArticuloDetailActivity.class);
            intent.putExtra("articulo_id", articulo.getId());
            startActivity(intent);
        });
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreateArticuloActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadArticulos() {
        apiService.getArticulos(new ApiServiceHelper.ArticulosCallback() {
            @Override
            public void onSuccess(List<Articulo> articulos) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    if (articulos != null) {
                        articulosList.clear();
                        articulosList.addAll(articulos);
                        adapter.notifyDataSetChanged();
                    }
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        ArticulosActivity.this,
                        "Error al cargar artículos: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_articulos, menu);
        
        MenuItem searchItem = menu.findItem(R.id.action_search);
        searchView = (SearchView) searchItem.getActionView();
        searchView.setOnQueryTextListener(new SearchView.OnQueryTextListener() {
            @Override
            public boolean onQueryTextSubmit(String query) {
                filterArticulos(query);
                return false;
            }
            
            @Override
            public boolean onQueryTextChange(String newText) {
                filterArticulos(newText);
                return false;
            }
        });
        
        return true;
    }
    
    private void filterArticulos(String query) {
        List<Articulo> filtered = new ArrayList<>();
        if (query.isEmpty()) {
            filtered.addAll(articulosList);
        } else {
            for (Articulo articulo : articulosList) {
                if (articulo.getNombre().toLowerCase().contains(query.toLowerCase()) ||
                    (articulo.getCodigo() != null && articulo.getCodigo().toLowerCase().contains(query.toLowerCase()))) {
                    filtered.add(articulo);
                }
            }
        }
        adapter.updateArticulos(filtered);
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
        loadArticulos();
    }
}

