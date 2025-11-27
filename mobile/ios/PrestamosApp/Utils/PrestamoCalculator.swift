import Foundation

struct PrestamoCalculator {
    
    /// Calcula la cuota mensual de un préstamo
    static func calcularCuotaMensual(monto: Double, tasaMensual: Double, plazoMeses: Int) -> Double {
        guard tasaMensual > 0 else {
            return monto / Double(plazoMeses)
        }
        
        let tasaDecimal = tasaMensual / 100.0
        let factor = pow(1 + tasaDecimal, Double(plazoMeses))
        return monto * (tasaDecimal * factor) / (factor - 1)
    }
    
    /// Calcula el monto total a pagar
    static func calcularMontoTotal(cuotaMensual: Double, plazoMeses: Int) -> Double {
        return cuotaMensual * Double(plazoMeses)
    }
    
    /// Calcula el interés total
    static func calcularInteresTotal(montoTotal: Double, montoPrestamo: Double) -> Double {
        return montoTotal - montoPrestamo
    }
    
    /// Calcula la tasa efectiva anual
    static func calcularTasaAnual(tasaMensual: Double) -> Double {
        return tasaMensual * 12
    }
}

