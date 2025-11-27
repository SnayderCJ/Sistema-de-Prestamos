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
import com.erp.prestamos.adapters.ClienteAdapter;
import com.erp.prestamos.models.*;
import com.erp.prestamos.services.ApiServiceHelper;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.List;

public class ClientesActivity extends AppCompatActivity {
    private RecyclerView recyclerView;
    private ClienteAdapter adapter;
    private ApiServiceHelper apiService;
    private FloatingActionButton fabAdd;
    private SwipeRefreshLayout swipeRefresh;
    private List<Cliente> allClientes = new ArrayList<>();
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_clientes);
        
        initViews();
        apiService = ApiServiceHelper.getInstance(this);
        loadClientes();
    }
    
    private void initViews() {
        swipeRefresh = findViewById(R.id.swipeRefresh);
        swipeRefresh.setOnRefreshListener(() -> {
            loadClientes();
        });
        
        recyclerView = findViewById(R.id.recyclerViewClientes);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new ClienteAdapter(new ArrayList<>(), cliente -> {
            Intent intent = new Intent(this, ClienteDetailActivity.class);
            intent.putExtra("cliente_id", cliente.getId());
            startActivity(intent);
        });
        recyclerView.setAdapter(adapter);
        
        fabAdd = findViewById(R.id.fabAdd);
        fabAdd.setOnClickListener(v -> {
            Intent intent = new Intent(this, CreateClienteActivity.class);
            startActivity(intent);
        });
    }
    
    private void loadClientes() {
        apiService.getClientes(new ApiServiceHelper.ClientesCallback() {
            @Override
            public void onSuccess(List<Cliente> clientes) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    allClientes = clientes;
                    adapter.updateClientes(clientes);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    android.widget.Toast.makeText(
                        ClientesActivity.this,
                        "Error al cargar clientes: " + error,
                        android.widget.Toast.LENGTH_SHORT
                    ).show();
                });
            }
        });
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_clientes, menu);
        
        MenuItem searchItem = menu.findItem(R.id.action_search);
        SearchView searchView = (SearchView) searchItem.getActionView();
        
        searchView.setOnQueryTextListener(new SearchView.OnQueryTextListener() {
            @Override
            public boolean onQueryTextSubmit(String query) {
                filterClientes(query);
                return false;
            }
            
            @Override
            public boolean onQueryTextChange(String newText) {
                filterClientes(newText);
                return false;
            }
        });
        
        return true;
    }
    
    private void filterClientes(String query) {
        if (query == null || query.isEmpty()) {
            adapter.updateClientes(allClientes);
            return;
        }
        
        List<Cliente> filtered = new ArrayList<>();
        String queryLower = query.toLowerCase();
        for (Cliente cliente : allClientes) {
            if ((cliente.getNombre() != null && cliente.getNombre().toLowerCase().contains(queryLower)) ||
                (cliente.getCedula() != null && cliente.getCedula().contains(query)) ||
                (cliente.getApellido() != null && cliente.getApellido().toLowerCase().contains(queryLower))) {
                filtered.add(cliente);
            }
        }
        adapter.updateClientes(filtered);
    }
}

