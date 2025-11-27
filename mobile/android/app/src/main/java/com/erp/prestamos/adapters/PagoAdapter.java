package com.erp.prestamos.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.erp.prestamos.R;
import com.erp.prestamos.models.Pago;
import com.erp.prestamos.utils.ReciboExporter;
import java.util.ArrayList;
import java.util.List;

public class PagoAdapter extends RecyclerView.Adapter<PagoAdapter.PagoViewHolder> {
    private List<Pago> pagos;
    
    public PagoAdapter(List<Pago> pagos) {
        this.pagos = pagos != null ? pagos : new ArrayList<>();
    }
    
    @NonNull
    @Override
    public PagoViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_pago, parent, false);
        return new PagoViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull PagoViewHolder holder, int position) {
        Pago pago = pagos.get(position);
        holder.bind(pago);
    }
    
    @Override
    public int getItemCount() {
        return pagos.size();
    }
    
    public void updatePagos(List<Pago> newPagos) {
        this.pagos = newPagos != null ? newPagos : new ArrayList<>();
        notifyDataSetChanged();
    }
    
    class PagoViewHolder extends RecyclerView.ViewHolder {
        private TextView tvRecibo, tvPrestamo, tvCliente, tvMonto, tvMetodo, tvFecha;
        private ImageButton btnCompartir;
        
        PagoViewHolder(@NonNull View itemView) {
            super(itemView);
            tvRecibo = itemView.findViewById(R.id.tvRecibo);
            tvPrestamo = itemView.findViewById(R.id.tvPrestamo);
            tvCliente = itemView.findViewById(R.id.tvCliente);
            tvMonto = itemView.findViewById(R.id.tvMonto);
            tvMetodo = itemView.findViewById(R.id.tvMetodo);
            tvFecha = itemView.findViewById(R.id.tvFecha);
            btnCompartir = itemView.findViewById(R.id.btnCompartir);
        }
        
        void bind(Pago pago) {
            tvRecibo.setText(pago.getNumeroRecibo() != null ? pago.getNumeroRecibo() : "-");
            tvPrestamo.setText(pago.getNumeroPrestamo() != null ? pago.getNumeroPrestamo() : "-");
            tvCliente.setText((pago.getClienteNombre() != null ? pago.getClienteNombre() : "") + 
                            " " + (pago.getClienteApellido() != null ? pago.getClienteApellido() : ""));
            tvMonto.setText(String.format("RD$ %.2f", pago.getMonto()));
            tvMetodo.setText(pago.getMetodoPago() != null ? pago.getMetodoPago() : "-");
            tvFecha.setText(pago.getFechaPago() != null ? pago.getFechaPago() : "-");
            
            if (btnCompartir != null) {
                btnCompartir.setOnClickListener(v -> {
                    android.content.Intent shareIntent = ReciboExporter.compartirRecibo(pago, itemView.getContext());
                    itemView.getContext().startActivity(shareIntent);
                });
            }
        }
    }
}

