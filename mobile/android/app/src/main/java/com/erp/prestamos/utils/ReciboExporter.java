package com.erp.prestamos.utils;

import android.content.Context;
import android.content.Intent;
import android.text.Html;
import com.erp.prestamos.models.Pago;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class ReciboExporter {
    
    /**
     * Genera el contenido HTML de un recibo de pago
     */
    public static String generarReciboHTML(Pago pago, Context context) {
        SimpleDateFormat sdf = new SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.getDefault());
        String fecha = pago.getFechaPago() != null ? pago.getFechaPago() : sdf.format(new Date());
        
        StringBuilder html = new StringBuilder();
        html.append("<!DOCTYPE html>");
        html.append("<html><head><meta charset='UTF-8'>");
        html.append("<style>");
        html.append("body { font-family: Arial, sans-serif; padding: 20px; }");
        html.append(".header { text-align: center; margin-bottom: 30px; }");
        html.append(".title { font-size: 24px; font-weight: bold; }");
        html.append(".subtitle { font-size: 14px; color: #666; }");
        html.append(".section { margin: 20px 0; }");
        html.append(".label { font-weight: bold; }");
        html.append(".value { margin-left: 10px; }");
        html.append(".total { font-size: 18px; font-weight: bold; margin-top: 20px; padding-top: 20px; border-top: 2px solid #000; }");
        html.append("</style></head><body>");
        
        html.append("<div class='header'>");
        html.append("<div class='title'>RECIBO DE PAGO</div>");
        html.append("<div class='subtitle'>Sistema de Préstamos</div>");
        html.append("</div>");
        
        html.append("<div class='section'>");
        html.append("<div><span class='label'>Recibo N°:</span> <span class='value'>").append(pago.getNumeroRecibo() != null ? pago.getNumeroRecibo() : "-").append("</span></div>");
        html.append("<div><span class='label'>Fecha:</span> <span class='value'>").append(fecha).append("</span></div>");
        html.append("</div>");
        
        html.append("<div class='section'>");
        html.append("<div><span class='label'>Cliente:</span> <span class='value'>");
        if (pago.getClienteNombre() != null) {
            html.append(pago.getClienteNombre());
            if (pago.getClienteApellido() != null) {
                html.append(" ").append(pago.getClienteApellido());
            }
        } else {
            html.append("-");
        }
        html.append("</span></div>");
        html.append("<div><span class='label'>Cédula:</span> <span class='value'>").append(pago.getClienteCedula() != null ? pago.getClienteCedula() : "-").append("</span></div>");
        html.append("</div>");
        
        html.append("<div class='section'>");
        html.append("<div><span class='label'>Préstamo:</span> <span class='value'>").append(pago.getPrestamoNumero() != null ? pago.getPrestamoNumero() : "-").append("</span></div>");
        html.append("<div><span class='label'>Monto Pagado:</span> <span class='value'>RD$ ").append(String.format(Locale.getDefault(), "%.2f", pago.getMonto())).append("</span></div>");
        html.append("<div><span class='label'>Método de Pago:</span> <span class='value'>").append(pago.getMetodoPago() != null ? pago.getMetodoPago() : "-").append("</span></div>");
        html.append("</div>");
        
        html.append("<div class='total'>");
        html.append("TOTAL: RD$ ").append(String.format(Locale.getDefault(), "%.2f", pago.getMonto()));
        html.append("</div>");
        
        html.append("<div style='margin-top: 40px; text-align: center; font-size: 12px; color: #666;'>");
        html.append("Este es un recibo generado electrónicamente");
        html.append("</div>");
        
        html.append("</body></html>");
        return html.toString();
    }
    
    /**
     * Comparte el recibo como HTML
     */
    public static Intent compartirRecibo(Pago pago, Context context) {
        String html = generarReciboHTML(pago, context);
        Intent intent = new Intent(Intent.ACTION_SEND);
        intent.setType("text/html");
        intent.putExtra(Intent.EXTRA_SUBJECT, "Recibo de Pago - " + (pago.getNumeroRecibo() != null ? pago.getNumeroRecibo() : ""));
        intent.putExtra(Intent.EXTRA_TEXT, Html.fromHtml(html, Html.FROM_HTML_MODE_LEGACY));
        return Intent.createChooser(intent, "Compartir recibo");
    }
}

