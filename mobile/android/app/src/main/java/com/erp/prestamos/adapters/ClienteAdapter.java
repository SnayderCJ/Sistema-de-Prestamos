package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Cliente;
import java.util.ArrayList;
import java.util.List;

public class ClienteAdapter extends RecyclerView.Adapter<ClienteAdapter.ClienteViewHolder> {
    private List<Cliente> clientes;
    private OnClienteClickListener listener;
    
    public interface OnClienteClickListener {
        void onClienteClick(Cliente cliente);
    }
    
    public ClienteAdapter(List<Cliente> clientes, OnClienteClickListener listener) {
        this.clientes = clientes != null ? clientes : new ArrayList<>();
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public ClienteViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_cliente, parent, false);
        return new ClienteViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull ClienteViewHolder holder, int position) {
        Cliente cliente = clientes.get(position);
        holder.bind(cliente);
    }
    
    @Override
    public int getItemCount() {
        return clientes.size();
    }
    
    public void updateClientes(List<Cliente> newClientes) {
        this.clientes = newClientes != null ? newClientes : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class ClienteViewHolder extends RecyclerView.ViewHolder {
        private TextView tvCedula, tvNombre, tvTelefono, tvPrestamos, tvEstado;
        
        ClienteViewHolder(@NonNull View itemView) {
            super(itemView);
            tvCedula = itemView.findViewById(R.id.tvCedula);
            tvNombre = itemView.findViewById(R.id.tvNombre);
            tvTelefono = itemView.findViewById(R.id.tvTelefono);
            tvPrestamos = itemView.findViewById(R.id.tvPrestamos);
            tvEstado = itemView.findViewById(R.id.tvEstado);
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onClienteClick(clientes.get(getAdapterPosition()));
                }
            });
        }
        
        void bind(Cliente cliente) {
            tvCedula.setText(cliente.getCedula());
            tvNombre.setText((cliente.getNombre() != null ? cliente.getNombre() : "") + 
                           " " + (cliente.getApellido() != null ? cliente.getApellido() : ""));
            tvTelefono.setText(cliente.getTelefono() != null ? cliente.getTelefono() : "-");
            tvPrestamos.setText(String.valueOf(cliente.getTotalPrestamos()));
            tvEstado.setText(cliente.getEstadoCredito() != null ? cliente.getEstadoCredito() : "activo");
        }
    }
}

