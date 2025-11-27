import Foundation
import SwiftUI

struct ReciboExporter {
    
    /// Genera el contenido HTML de un recibo de pago
    static func generarReciboHTML(_ pago: Pago) -> String {
        let fecha = pago.fechaPago ?? ""
        
        var html = """
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; }
                .subtitle { font-size: 14px; color: #666; }
                .section { margin: 20px 0; }
                .label { font-weight: bold; }
                .value { margin-left: 10px; }
                .total { font-size: 18px; font-weight: bold; margin-top: 20px; padding-top: 20px; border-top: 2px solid #000; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">RECIBO DE PAGO</div>
                <div class="subtitle">Sistema de Préstamos</div>
            </div>
            <div class="section">
                <div><span class="label">Recibo N°:</span> <span class="value">\(pago.numeroRecibo ?? "-")</span></div>
                <div><span class="label">Fecha:</span> <span class="value">\(fecha)</span></div>
            </div>
            <div class="section">
                <div><span class="label">Cliente:</span> <span class="value">\(pago.clienteNombre ?? "") \(pago.clienteApellido ?? "")</span></div>
                <div><span class="label">Cédula:</span> <span class="value">\(pago.clienteCedula ?? "-")</span></div>
            </div>
            <div class="section">
                <div><span class="label">Préstamo:</span> <span class="value">\(pago.numeroPrestamo ?? "-")</span></div>
                <div><span class="label">Monto Pagado:</span> <span class="value">RD$ \(String(format: "%.2f", pago.monto ?? 0))</span></div>
                <div><span class="label">Método de Pago:</span> <span class="value">\(pago.metodoPago ?? "-")</span></div>
            </div>
            <div class="total">
                TOTAL: RD$ \(String(format: "%.2f", pago.monto ?? 0))
            </div>
            <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #666;">
                Este es un recibo generado electrónicamente
            </div>
        </body>
        </html>
        """
        
        return html
    }
    
    /// Comparte el recibo
    static func compartirRecibo(_ pago: Pago) -> [Any] {
        let html = generarReciboHTML(pago)
        let subject = "Recibo de Pago - \(pago.numeroRecibo ?? "")"
        return [subject, html]
    }
}

extension DateFormatter {
    static let recibo: DateFormatter = {
        let formatter = DateFormatter()
        formatter.dateFormat = "dd/MM/yyyy HH:mm"
        return formatter
    }()
}

