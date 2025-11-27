package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Compra;
import java.util.ArrayList;
import java.util.List;

public class CompraAdapter extends RecyclerView.Adapter<CompraAdapter.CompraViewHolder> {
    private List<Compra> compras;
    private OnCompraClickListener listener;
    
    public interface OnCompraClickListener {
        void onCompraClick(Compra compra);
    }
    
    public CompraAdapter(List<Compra> compras, OnCompraClickListener listener) {
        this.compras = compras != null ? compras : new ArrayList<>();
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public CompraViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_compra, parent, false);
        return new CompraViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull CompraViewHolder holder, int position) {
        Compra compra = compras.get(position);
        holder.bind(compra);
    }
    
    @Override
    public int getItemCount() {
        return compras.size();
    }
    
    public void updateCompras(List<Compra> newCompras) {
        this.compras = newCompras != null ? newCompras : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class CompraViewHolder extends RecyclerView.ViewHolder {
        private TextView tvNumero, tvProveedor, tvFecha, tvMonto, tvEstado;
        
        CompraViewHolder(@NonNull View itemView) {
            super(itemView);
            tvNumero = itemView.findViewById(R.id.tvNumero);
            tvProveedor = itemView.findViewById(R.id.tvProveedor);
            tvFecha = itemView.findViewById(R.id.tvFecha);
            tvMonto = itemView.findViewById(R.id.tvMonto);
            tvEstado = itemView.findViewById(R.id.tvEstado);
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onCompraClick(compras.get(getAdapterPosition()));
                }
            });
        }
        
        void bind(Compra compra) {
            tvNumero.setText(compra.getNumeroFactura());
            tvProveedor.setText(compra.getProveedorNombre() != null ? compra.getProveedorNombre() : "");
            tvFecha.setText(compra.getFecha() != null ? compra.getFecha().substring(0, Math.min(10, compra.getFecha().length())) : "");
            tvMonto.setText(String.format("RD$ %.2f", compra.getMontoTotal()));
            tvEstado.setText(compra.getEstado() != null ? compra.getEstado() : "");
        }
    }
}

