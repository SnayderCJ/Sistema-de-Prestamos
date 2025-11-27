<?php
/**
 * Utilidades para Respuestas HTTP
 */

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendError($message, $statusCode = 400, $errors = []) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendPaginatedResponse($data, $total, $page = 1, $perPage = 20) {
    $totalPages = ceil($total / $perPage);
    
    sendResponse([
        'items' => $data,
        'pagination' => [
            'total' => $total,
            'page' => (int)$page,
            'per_page' => (int)$perPage,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);
}


