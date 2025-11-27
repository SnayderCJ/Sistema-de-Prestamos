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

public class PrestamoVencidoAdapter extends RecyclerView.Adapter<PrestamoVencidoAdapter.PrestamoVencidoViewHolder> {
    private List<Prestamo> prestamos;
    
    public PrestamoVencidoAdapter(List<Prestamo> prestamos) {
        this.prestamos = prestamos != null ? prestamos : new ArrayList<>();
    }
    
    @NonNull
    @Override
    public PrestamoVencidoViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_prestamo_vencido, parent, false);
        return new PrestamoVencidoViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull PrestamoVencidoViewHolder holder, int position) {
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
    
    class PrestamoVencidoViewHolder extends RecyclerView.ViewHolder {
        private TextView tvNumero, tvCliente, tvMonto, tvDiasVencido, tvMora;
        
        PrestamoVencidoViewHolder(@NonNull View itemView) {
            super(itemView);
            tvNumero = itemView.findViewById(R.id.tvNumero);
            tvCliente = itemView.findViewById(R.id.tvCliente);
            tvMonto = itemView.findViewById(R.id.tvMonto);
            tvDiasVencido = itemView.findViewById(R.id.tvDiasVencido);
            tvMora = itemView.findViewById(R.id.tvMora);
        }
        
        void bind(Prestamo prestamo) {
            tvNumero.setText(prestamo.getNumeroPrestamo() != null ? prestamo.getNumeroPrestamo() : "-");
            tvCliente.setText((prestamo.getClienteNombre() != null ? prestamo.getClienteNombre() : "") + 
                            " " + (prestamo.getClienteApellido() != null ? prestamo.getClienteApellido() : ""));
            tvMonto.setText(String.format("RD$ %.2f", prestamo.getMontoAprobado()));
            
            // Calcular días vencido y mora
            int diasVencido = calcularDiasVencido(prestamo);
            double mora = calcularMora(prestamo, diasVencido);
            
            tvDiasVencido.setText(String.valueOf(diasVencido));
            tvMora.setText(String.format("RD$ %.2f", mora));
        }
        
        private int calcularDiasVencido(Prestamo prestamo) {
            try {
                java.util.Date fechaActual = new java.util.Date();
                java.text.SimpleDateFormat sdf = new java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault());
                
                // Calcular fecha de vencimiento basado en fecha de creación y plazo
                String fechaCreacionStr = prestamo.getFechaCreacion();
                if (fechaCreacionStr != null && !fechaCreacionStr.isEmpty() && prestamo.getPlazoMeses() > 0) {
                    try {
                        // Parsear fecha de creación (puede venir en formato yyyy-MM-dd o yyyy-MM-dd HH:mm:ss)
                        String fechaCreacionFormato = fechaCreacionStr.split(" ")[0]; // Tomar solo la parte de fecha
                        java.util.Date fechaCreacion = sdf.parse(fechaCreacionFormato);
                        
                        if (fechaCreacion != null) {
                            java.util.Calendar cal = java.util.Calendar.getInstance();
                            cal.setTime(fechaCreacion);
                            cal.add(java.util.Calendar.MONTH, prestamo.getPlazoMeses());
                            java.util.Date fechaVencimiento = cal.getTime();
                            
                            long diff = fechaActual.getTime() - fechaVencimiento.getTime();
                            int dias = (int) (diff / (1000 * 60 * 60 * 24));
                            return Math.max(0, dias);
                        }
                    } catch (java.text.ParseException e) {
                        // Si no se puede parsear, retornar 0
                    }
                }
            } catch (Exception e) {
                // Si hay error, retornar 0
            }
            return 0;
        }
        
        private double calcularMora(Prestamo prestamo, int diasVencido) {
            if (diasVencido <= 0) {
                return 0.0;
            }
            
            // Calcular mora: generalmente es un porcentaje diario del monto de la cuota
            // Por ejemplo: 1% diario de la cuota mensual
            double cuotaMensual = prestamo.getCuotaMensual();
            double tasaMoraDiaria = 0.01; // 1% diario (ajustar según política de la empresa)
            
            return cuotaMensual * tasaMoraDiaria * diasVencido;
        }
    }
}

