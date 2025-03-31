<?php
require_once __DIR__ . '/../models/Order.php';

// For debugging - log incoming order requests
error_log('Order API request received: ' . file_get_contents('php://input'));

$order = new Order();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        try {
            // Get input data
            $data = get_json_input();
            
            // Validate customer information
            $customer = $data['customer'] ?? [];
            $required_fields = ['name', 'company', 'email', 'phone'];
            $missing = validate_required_fields($customer, $required_fields);
            
            if (!empty($missing)) {
                Response::json_error(format_missing_fields_error($missing), 400);
            }
            
            // Validate email format
            if (!validate_email($customer['email'])) {
                Response::json_error('Please enter a valid email address', 400);
            }
            
            // Validate delivery address if delivery is needed
            if (isset($customer['needsDelivery']) && $customer['needsDelivery'] === true) {
                if (empty($customer['deliveryAddress'])) {
                    Response::json_error('Delivery address is required when delivery is needed', 400);
                }
            }
            
            // Validate items
            if (empty($data['items']) || !is_array($data['items'])) {
                Response::json_error('No items in the order', 400);
            }
            
            // Validate each item has required fields
            foreach ($data['items'] as $index => $item) {
                if (empty($item['productId'])) {
                    Response::json_error("Item #" . ($index + 1) . " is missing a product ID", 400);
                }
                
                if (empty($item['selections']) || !is_array($item['selections'])) {
                    Response::json_error("Item #" . ($index + 1) . " is missing selections", 400);
                }
            }
            
            // Clean input data
            $data = clean_input($data);
            
            // If timestamp is not provided, add it
            if (empty($data['timestamp'])) {
                $data['timestamp'] = date('Y-m-d\TH:i:s.v\Z');
            }
            
            // Add order to database
            $order_id = $order->createOrder($data);
            
            if (!$order_id) {
                throw new Exception('Failed to create order in database');
            }
            
            // Send email notification
            $email_sent = false;
            try {
                $email_sent = $order->sendOrderEmail($data);
            } catch (Exception $e) {
                error_log('Failed to send order email: ' . $e->getMessage());
                // Continue with response even if email fails
            }
            
            // Response for client
            $response = [
                'success' => true,
                'orderId' => $order_id,
                'message' => 'Your quotation request has been successfully submitted',
                'estimatedResponse' => '24 hours',
                'items' => $data['items'],
                'customer' => $data['customer'],
                'timestamp' => $data['timestamp'],
                'emailSent' => $email_sent
            ];
            
            Response::json_success($response);
            
        } catch (Exception $e) {
            error_log('Order API error: ' . $e->getMessage());
            Response::json_error('Failed to process order: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'OPTIONS':
        // Handle preflight CORS requests
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        http_response_code(200);
        exit;
        
    case 'GET':
        // Only for admin use - not needed for the frontend
        Response::forbidden();
        break;
        
    default:
        Response::json_error('Method not allowed', 405);
        break;
}