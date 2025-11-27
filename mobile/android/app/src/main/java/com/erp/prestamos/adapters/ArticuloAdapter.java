package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Articulo;
import java.util.ArrayList;
import java.util.List;

public class ArticuloAdapter extends RecyclerView.Adapter<ArticuloAdapter.ArticuloViewHolder> {
    private List<Articulo> articulos;
    private OnArticuloClickListener listener;
    
    public interface OnArticuloClickListener {
        void onArticuloClick(Articulo articulo);
    }
    
    public ArticuloAdapter(List<Articulo> articulos, OnArticuloClickListener listener) {
        this.articulos = articulos != null ? articulos : new ArrayList<>();
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public ArticuloViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_articulo, parent, false);
        return new ArticuloViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull ArticuloViewHolder holder, int position) {
        Articulo articulo = articulos.get(position);
        holder.bind(articulo);
    }
    
    @Override
    public int getItemCount() {
        return articulos.size();
    }
    
    public void updateArticulos(List<Articulo> newArticulos) {
        this.articulos = newArticulos != null ? newArticulos : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class ArticuloViewHolder extends RecyclerView.ViewHolder {
        private TextView tvCodigo, tvNombre, tvCategoria, tvPrecio, tvStock;
        
        ArticuloViewHolder(@NonNull View itemView) {
            super(itemView);
            tvCodigo = itemView.findViewById(R.id.tvCodigo);
            tvNombre = itemView.findViewById(R.id.tvNombre);
            tvCategoria = itemView.findViewById(R.id.tvCategoria);
            tvPrecio = itemView.findViewById(R.id.tvPrecio);
            tvStock = itemView.findViewById(R.id.tvStock);
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onArticuloClick(articulos.get(getAdapterPosition()));
                }
            });
        }
        
        void bind(Articulo articulo) {
            tvCodigo.setText(articulo.getCodigo() != null ? articulo.getCodigo() : "");
            tvNombre.setText(articulo.getNombre());
            tvCategoria.setText(articulo.getCategoriaNombre() != null ? articulo.getCategoriaNombre() : "");
            tvPrecio.setText(String.format("RD$ %.2f", articulo.getPrecioVenta()));
            tvStock.setText(String.format("Stock: %d", articulo.getStock()));
            
            if (articulo.getStock() <= articulo.getStockMinimo()) {
                tvStock.setTextColor(itemView.getContext().getResources().getColor(android.R.color.holo_red_dark));
            } else {
                tvStock.setTextColor(itemView.getContext().getResources().getColor(android.R.color.black));
            }
        }
    }
}

