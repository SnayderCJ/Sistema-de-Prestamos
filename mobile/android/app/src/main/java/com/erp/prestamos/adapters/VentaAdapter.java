package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Venta;
import java.util.ArrayList;
import java.util.List;

public class VentaAdapter extends RecyclerView.Adapter<VentaAdapter.VentaViewHolder> {
    private List<Venta> ventas;
    private OnVentaClickListener listener;
    
    public interface OnVentaClickListener {
        void onVentaClick(Venta venta);
    }
    
    public VentaAdapter(List<Venta> ventas, OnVentaClickListener listener) {
        this.ventas = ventas != null ? ventas : new ArrayList<>();
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public VentaViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_venta, parent, false);
        return new VentaViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull VentaViewHolder holder, int position) {
        Venta venta = ventas.get(position);
        holder.bind(venta);
    }
    
    @Override
    public int getItemCount() {
        return ventas.size();
    }
    
    public void updateVentas(List<Venta> newVentas) {
        this.ventas = newVentas != null ? newVentas : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class VentaViewHolder extends RecyclerView.ViewHolder {
        private TextView tvNumero, tvCliente, tvFecha, tvMonto, tvEstado;
        
        VentaViewHolder(@NonNull View itemView) {
            super(itemView);
            tvNumero = itemView.findViewById(R.id.tvNumero);
            tvCliente = itemView.findViewById(R.id.tvCliente);
            tvFecha = itemView.findViewById(R.id.tvFecha);
            tvMonto = itemView.findViewById(R.id.tvMonto);
            tvEstado = itemView.findViewById(R.id.tvEstado);
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onVentaClick(ventas.get(getAdapterPosition()));
                }
            });
        }
        
        void bind(Venta venta) {
            tvNumero.setText(venta.getNumeroFactura());
            tvCliente.setText(venta.getClienteNombre() != null ? venta.getClienteNombre() : "");
            tvFecha.setText(venta.getFecha() != null ? venta.getFecha().substring(0, Math.min(10, venta.getFecha().length())) : "");
            tvMonto.setText(String.format("RD$ %.2f", venta.getMontoTotal()));
            tvEstado.setText(venta.getEstado() != null ? venta.getEstado() : "");
        }
    }
}

