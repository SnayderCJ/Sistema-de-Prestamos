<?php
/**
 * Configuración de WhatsApp Business API
 * 
 * INSTRUCCIONES:
 * 1. Crear una cuenta en Facebook Developers
 * 2. Crear una aplicación de tipo "Business"
 * 3. Agregar el producto "WhatsApp"
 * 4. Obtener el Access Token y Phone Number ID
 * 5. Configurar el webhook
 * 
 * Para configurar en el sistema:
 * - Ir a Configuración > Sistema
 * - Configurar los siguientes valores:
 *   - whatsapp_api_url: https://graph.facebook.com/v18.0
 *   - whatsapp_api_token: [Tu Access Token]
 *   - whatsapp_phone_number_id: [Tu Phone Number ID]
 *   - whatsapp_webhook_token: [Token para verificar webhook]
 */

// Ejemplo de configuración (NO incluir en producción)
define('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0');
define('WHATSAPP_API_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
define('WHATSAPP_PHONE_NUMBER_ID', 'TU_PHONE_NUMBER_ID_AQUI');
define('WHATSAPP_WEBHOOK_TOKEN', 'mi_token_secreto');

