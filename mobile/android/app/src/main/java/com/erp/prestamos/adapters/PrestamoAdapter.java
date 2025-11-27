package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Prestamo;
import java.util.ArrayList;
import java.util.List;

public class PrestamoAdapter extends RecyclerView.Adapter<PrestamoAdapter.PrestamoViewHolder> {
    private List<Prestamo> prestamos;
    private OnPrestamoClickListener listener;
    
    public interface OnPrestamoClickListener {
        void onPrestamoClick(Prestamo prestamo);
    }
    
    public PrestamoAdapter(List<Prestamo> prestamos, OnPrestamoClickListener listener) {
        this.prestamos = prestamos != null ? prestamos : new ArrayList<>();
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public PrestamoViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_prestamo, parent, false);
        return new PrestamoViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull PrestamoViewHolder holder, int position) {
        Prestamo prestamo = prestamos.get(position);
        holder.bind(prestamo);
    }
    
    @Override
    public int getItemCount() {
        return prestamos.size();
    }
    
    public void updatePrestamos(List<Prestamo> newPrestamos) {
        this.prestamos = newPrestamos != null ? newPrestamos : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class PrestamoViewHolder extends RecyclerView.ViewHolder {
        private TextView tvNumero, tvCliente, tvMonto, tvCuota, tvEstado;
        
        PrestamoViewHolder(@NonNull View itemView) {
            super(itemView);
            tvNumero = itemView.findViewById(R.id.tvNumero);
            tvCliente = itemView.findViewById(R.id.tvCliente);
            tvMonto = itemView.findViewById(R.id.tvMonto);
            tvCuota = itemView.findViewById(R.id.tvCuota);
            tvEstado = itemView.findViewById(R.id.tvEstado);
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onPrestamoClick(prestamos.get(getAdapterPosition()));
                }
            });
        }
        
        void bind(Prestamo prestamo) {
            tvNumero.setText(prestamo.getNumeroPrestamo());
            tvCliente.setText((prestamo.getClienteNombre() != null ? prestamo.getClienteNombre() : "") + 
                            " " + (prestamo.getClienteApellido() != null ? prestamo.getClienteApellido() : ""));
            tvMonto.setText(String.format("RD$ %.2f", prestamo.getMontoAprobado()));
            tvCuota.setText(String.format("RD$ %.2f", prestamo.getCuotaMensual()));
            tvEstado.setText(prestamo.getEstado());
        }
    }
}

