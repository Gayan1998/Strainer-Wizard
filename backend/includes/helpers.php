<?php
/**
 * Helper functions for the API
 */

/**
 * Get JSON input data
 *
 * @return array Decoded JSON data
 */
function get_json_input() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::json_error('Invalid JSON input', 400);
    }
    
    return $data;
}

/**
 * Validate required fields
 *
 * @param array $data Data to validate
 * @param array $required_fields Required fields
 * @return array Missing fields
 */
function validate_required_fields($data, $required_fields) {
    $missing = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

/**
 * Validate email format
 *
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate unique ID
 *
 * @param string $prefix Prefix for the ID
 * @return string Unique ID
 */
function generate_id($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(4));
}

/**
 * Format error message for missing fields
 *
 * @param array $missing Missing fields
 * @return string Error message
 */
function format_missing_fields_error($missing) {
    if (count($missing) === 1) {
        return "The field '{$missing[0]}' is required.";
    } else {
        $last = array_pop($missing);
        $fields = implode("', '", $missing);
        return "The fields '{$fields}' and '{$last}' are required.";
    }
}

/**
 * Send email
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @param string $from_name Sender name
 * @param string $from_email Sender email
 * @return bool True on success, false on failure
 */
function send_email($to, $subject, $body, $from_name = null, $from_email = null) {
    $from_name = $from_name ?: EMAIL_FROM_NAME;
    $from_email = $from_email ?: EMAIL_FROM;
    $reply_to = EMAIL_REPLY_TO ?: $from_email;
    
    $headers = [
        'From' => $from_name . ' <' . $from_email . '>',
        'Reply-To' => $reply_to,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=utf-8',
    ];
    
    $header_string = '';
    foreach ($headers as $key => $value) {
        $header_string .= $key . ': ' . $value . "\r\n";
    }
    
    return mail($to, $subject, $body, $header_string);
}

/**
 * Clean input data
 *
 * @param mixed $data Data to clean
 * @return mixed Cleaned data
 */
function clean_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = clean_input($value);
        }
    } else {
        $data = htmlspecialchars(strip_tags(trim($data)));
    }
    
    return $data;
}

/**
 * Format email body for order
 *
 * @param array $order_data Order data
 * @return string Formatted email body
 */
function format_order_email($order_data) {
    $customer = $order_data['customer'] ?? [];
    $items = $order_data['items'] ?? [];
    
    $body = "New quotation request details:\n\n";
    
    // Customer information
    $body .= "Customer Information:\n";
    $body .= "Name: " . ($customer['name'] ?? 'N/A') . "\n";
    $body .= "Company: " . ($customer['company'] ?? 'N/A') . "\n";
    $body .= "Email: " . ($customer['email'] ?? 'N/A') . "\n";
    $body .= "Phone: " . ($customer['phone'] ?? 'N/A') . "\n\n";
    
    // Order details
    $body .= "Order Details:\n";
    foreach ($items as $index => $item) {
        $body .= "Item " . ($index + 1) . ": " . ($item['productName'] ?? 'Unknown Product') . "\n";
        
        if (!empty($item['isSpecialOrder']) && $item['isSpecialOrder']) {
            $body .= "(Special Order)\n";
        }
        
        $body .= "Specifications:\n";
        
        if (!empty($item['selections']) && is_array($item['selections'])) {
            foreach ($item['selections'] as $key => $value) {
                $body .= "- {$key}: {$value}\n";
            }
        }
        
        $body .= "\n";
    }
    
    $body .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
    return $body;
}