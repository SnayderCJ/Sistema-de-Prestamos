package com.erp.prestamos.utils;

public class CedulaValidator {
    
    /**
     * Valida formato de cédula dominicana
     * @param cedula Cédula a validar
     * @return true si el formato es válido
     */
    public static boolean validarFormato(String cedula) {
        if (cedula == null || cedula.isEmpty()) {
            return false;
        }
        
        // Remover guiones y espacios
        cedula = cedula.replaceAll("[\\s-]", "");
        
        // Debe tener 11 dígitos
        if (cedula.length() != 11) {
            return false;
        }
        
        // Debe contener solo números
        if (!cedula.matches("\\d+")) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida cédula usando algoritmo de verificación
     * @param cedula Cédula a validar
     * @return true si la cédula es válida
     */
    public static boolean validarCedula(String cedula) {
        if (!validarFormato(cedula)) {
            return false;
        }
        
        cedula = cedula.replaceAll("[\\s-]", "");
        
        // Algoritmo de validación de cédula dominicana
        int suma = 0;
        int[] multiplicadores = {1, 2, 1, 2, 1, 2, 1, 2, 1, 2};
        
        for (int i = 0; i < 10; i++) {
            int digito = Character.getNumericValue(cedula.charAt(i));
            int producto = digito * multiplicadores[i];
            suma += (producto > 9) ? producto - 9 : producto;
        }
        
        int digitoVerificador = Character.getNumericValue(cedula.charAt(10));
        int residuo = suma % 10;
        int verificadorEsperado = (residuo == 0) ? 0 : 10 - residuo;
        
        return digitoVerificador == verificadorEsperado;
    }
    
    /**
     * Formatea cédula con guiones (000-0000000-0)
     * @param cedula Cédula sin formato
     * @return Cédula formateada
     */
    public static String formatearCedula(String cedula) {
        if (cedula == null || cedula.isEmpty()) {
            return "";
        }
        
        cedula = cedula.replaceAll("[\\s-]", "");
        
        if (cedula.length() == 11) {
            return cedula.substring(0, 3) + "-" + 
                   cedula.substring(3, 10) + "-" + 
                   cedula.substring(10);
        }
        
        return cedula;
    }
}

