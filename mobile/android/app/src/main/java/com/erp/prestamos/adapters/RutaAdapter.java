package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Ruta;
import java.util.ArrayList;
import java.util.List;

public class RutaAdapter extends RecyclerView.Adapter<RutaAdapter.RutaViewHolder> {
    private List<Ruta> rutas;
    private OnRutaClickListener listener;
    
    public interface OnRutaClickListener {
        void onRutaClick(Ruta ruta);
    }
    
    public RutaAdapter(List<Ruta> rutas, OnRutaClickListener listener) {
        this.rutas = rutas != null ? rutas : new ArrayList<>();
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public RutaViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_ruta, parent, false);
        return new RutaViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull RutaViewHolder holder, int position) {
        Ruta ruta = rutas.get(position);
        holder.bind(ruta);
    }
    
    @Override
    public int getItemCount() {
        return rutas.size();
    }
    
    public void updateRutas(List<Ruta> newRutas) {
        this.rutas = newRutas != null ? newRutas : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class RutaViewHolder extends RecyclerView.ViewHolder {
        private TextView tvNombre, tvSupervisor, tvCobrador, tvFecha, tvEstado, tvVisitas;
        
        RutaViewHolder(@NonNull View itemView) {
            super(itemView);
            tvNombre = itemView.findViewById(R.id.tvNombre);
            tvSupervisor = itemView.findViewById(R.id.tvSupervisor);
            tvCobrador = itemView.findViewById(R.id.tvCobrador);
            tvFecha = itemView.findViewById(R.id.tvFecha);
            tvEstado = itemView.findViewById(R.id.tvEstado);
            tvVisitas = itemView.findViewById(R.id.tvVisitas);
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onRutaClick(rutas.get(getAdapterPosition()));
                }
            });
        }
        
        void bind(Ruta ruta) {
            tvNombre.setText(ruta.getNombreRuta());
            tvSupervisor.setText((ruta.getSupervisorNombre() != null ? ruta.getSupervisorNombre() : "") + 
                               " " + (ruta.getSupervisorApellido() != null ? ruta.getSupervisorApellido() : ""));
            tvCobrador.setText((ruta.getCobradorNombre() != null ? ruta.getCobradorNombre() : "") + 
                            " " + (ruta.getCobradorApellido() != null ? ruta.getCobradorApellido() : ""));
            tvFecha.setText(ruta.getFechaRuta());
            tvEstado.setText(ruta.getEstado());
            tvVisitas.setText(String.valueOf(ruta.getTotalVisitas()));
        }
    }
}

