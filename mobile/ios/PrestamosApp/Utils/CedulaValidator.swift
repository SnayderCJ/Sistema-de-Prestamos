import Foundation

struct CedulaValidator {
    
    /// Valida formato de cédula dominicana
    static func validarFormato(_ cedula: String) -> Bool {
        let cleaned = cedula.replacingOccurrences(of: "[\\s-]", with: "", options: .regularExpression)
        
        guard cleaned.count == 11 else { return false }
        guard cleaned.allSatisfy({ $0.isNumber }) else { return false }
        
        return true
    }
    
    /// Valida cédula usando algoritmo de verificación
    static func validarCedula(_ cedula: String) -> Bool {
        guard validarFormato(cedula) else { return false }
        
        let cleaned = cedula.replacingOccurrences(of: "[\\s-]", with: "", options: .regularExpression)
        let multiplicadores = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2]
        
        var suma = 0
        for i in 0..<10 {
            let digito = Int(String(cleaned[cleaned.index(cleaned.startIndex, offsetBy: i)])) ?? 0
            let producto = digito * multiplicadores[i]
            suma += producto > 9 ? producto - 9 : producto
        }
        
        let digitoVerificador = Int(String(cleaned[cleaned.index(cleaned.startIndex, offsetBy: 10)])) ?? 0
        let residuo = suma % 10
        let verificadorEsperado = residuo == 0 ? 0 : 10 - residuo
        
        return digitoVerificador == verificadorEsperado
    }
    
    /// Formatea cédula con guiones (000-0000000-0)
    static func formatearCedula(_ cedula: String) -> String {
        let cleaned = cedula.replacingOccurrences(of: "[\\s-]", with: "", options: .regularExpression)
        
        guard cleaned.count == 11 else { return cedula }
        
        let index1 = cleaned.index(cleaned.startIndex, offsetBy: 3)
        let index2 = cleaned.index(cleaned.startIndex, offsetBy: 10)
        
        return "\(cleaned[..<index1])-\(cleaned[index1..<index2])-\(cleaned[index2...])"
    }
}

