<?php
/**
 * Validadores para República Dominicana
 */

function validateCedula($cedula) {
    // Remover guiones y espacios
    $cedula = preg_replace('/[-\s]/', '', $cedula);
    
    // Debe tener 11 dígitos
    if (!preg_match('/^\d{11}$/', $cedula)) {
        return false;
    }
    
    // Validar algoritmo de verificación de cédula dominicana
    $sum = 0;
    $weights = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
    
    for ($i = 0; $i < 10; $i++) {
        $digit = (int)$cedula[$i];
        $weight = $weights[$i];
        $product = $digit * $weight;
        
        if ($product >= 10) {
            $product = (int)($product / 10) + ($product % 10);
        }
        
        $sum += $product;
    }
    
    $checkDigit = (10 - ($sum % 10)) % 10;
    
    return $checkDigit == (int)$cedula[10];
}

function validateRNC($rnc) {
    // Remover guiones y espacios
    $rnc = preg_replace('/[-\s]/', '', $rnc);
    
    // RNC puede tener 9 o 11 dígitos
    if (!preg_match('/^\d{9}$|^\d{11}$/', $rnc)) {
        return false;
    }
    
    return true;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Formato dominicano: (809) 555-1234 o 809-555-1234 o 8095551234
    $phone = preg_replace('/[-\s()]/', '', $phone);
    return preg_match('/^(\+1)?(809|829|849)\d{7}$/', $phone);
}

function validateMonto($monto, $min = null, $max = null) {
    if (!is_numeric($monto) || $monto <= 0) {
        return false;
    }
    
    if ($min !== null && $monto < $min) {
        return false;
    }
    
    if ($max !== null && $monto > $max) {
        return false;
    }
    
    return true;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function getRequestBody() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}


