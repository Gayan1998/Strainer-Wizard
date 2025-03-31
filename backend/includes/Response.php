<?php
/**
 * Response class for handling API responses
 */
class Response {
    /**
     * Send a JSON success response
     *
     * @param mixed $data Data to be sent
     * @param int $status_code HTTP status code
     * @return void
     */
    public static function json_success($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Send a JSON error response
     *
     * @param string $message Error message
     * @param int $status_code HTTP status code
     * @param array $errors Additional error details
     * @return void
     */
    public static function json_error($message, $status_code = 400, $errors = []) {
        http_response_code($status_code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ]);
        exit;
    }
    
    /**
     * Send a 404 Not Found response
     *
     * @return void
     */
    public static function not_found() {
        self::json_error('Resource not found', 404);
    }
    
    /**
     * Send a 403 Forbidden response
     *
     * @return void
     */
    public static function forbidden() {
        self::json_error('Access forbidden', 403);
    }
    
    /**
     * Send a 401 Unauthorized response
     *
     * @return void
     */
    public static function unauthorized() {
        self::json_error('Unauthorized', 401);
    }
}