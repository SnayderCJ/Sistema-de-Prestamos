package com.erp.prestamos.utils;

public class PrestamoCalculator {
    
    /**
     * Calcula la cuota mensual de un préstamo
     * @param monto Monto del préstamo
     * @param tasaMensual Tasa de interés mensual (ej: 2.0 para 2%)
     * @param plazoMeses Plazo en meses
     * @return Cuota mensual calculada
     */
    public static double calcularCuotaMensual(double monto, double tasaMensual, int plazoMeses) {
        if (tasaMensual == 0) {
            return monto / plazoMeses;
        }
        
        double tasaDecimal = tasaMensual / 100.0;
        double factor = Math.pow(1 + tasaDecimal, plazoMeses);
        return monto * (tasaDecimal * factor) / (factor - 1);
    }
    
    /**
     * Calcula el monto total a pagar
     * @param cuotaMensual Cuota mensual
     * @param plazoMeses Plazo en meses
     * @return Monto total
     */
    public static double calcularMontoTotal(double cuotaMensual, int plazoMeses) {
        return cuotaMensual * plazoMeses;
    }
    
    /**
     * Calcula el interés total
     * @param montoTotal Monto total a pagar
     * @param montoPrestamo Monto del préstamo
     * @return Interés total
     */
    public static double calcularInteresTotal(double montoTotal, double montoPrestamo) {
        return montoTotal - montoPrestamo;
    }
    
    /**
     * Calcula la tasa efectiva anual
     * @param tasaMensual Tasa mensual
     * @return Tasa efectiva anual
     */
    public static double calcularTasaAnual(double tasaMensual) {
        return tasaMensual * 12;
    }
}

