<?php
require_once __DIR__ . '/../includes/Database.php';
/**
 * Order model - updated to handle new data structure
 */
class Order {
    private $db;
   
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Database();
    }
   
/**
 * Create a new order
 *
 * @param array $order_data Order data
 * @return string|false Order ID or false on failure
 */
public function createOrder($order_data) {
    try {
        $customer = $order_data['customer'] ?? [];
       
        if (empty($customer['name']) || empty($customer['company']) ||
            empty($customer['email']) || empty($customer['phone'])) {
            error_log("Order creation failed: Missing required customer information");
            return false;
        }
       
        // Extract delivery information properly
        $needs_delivery = isset($customer['needsDelivery']) ? (bool)$customer['needsDelivery'] : false;
        $delivery_address = ($needs_delivery && isset($customer['deliveryAddress'])) ? 
            $customer['deliveryAddress'] : null;
        
        // Log for debugging
        error_log("Delivery info - needs_delivery: " . ($needs_delivery ? 'true' : 'false') . 
                  ", delivery_address: " . ($delivery_address ?? 'null'));
        
        // Serialize order details for storage
        $order_details = json_encode($order_data);
       
        $data = [
            'id' => generate_id('ORD-'),
            'customer_name' => $customer['name'],
            'customer_company' => $customer['company'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'],
            'order_details' => $order_details,
            'needs_delivery' => $needs_delivery ? 1 : 0, // Convert boolean to 1/0 for database
            'delivery_address' => $delivery_address
        ];
        
        // Log insertion data
        error_log("Inserting order with data: " . json_encode($data));
       
        // Insert into database
        return $this->db->insert('orders', $data);
    } catch (Exception $e) {
        error_log("Error creating order: " . $e->getMessage());
        return false;
    }
}
   
    /**
     * Get order by ID
     *
     * @param string $id Order ID
     * @return array|false Order data or false if not found
     */
    public function getOrderById($id) {
        try {
            $order = $this->db->fetchRow("SELECT * FROM orders WHERE id = ?", [$id]);
           
            if ($order) {
                $order['order_details'] = json_decode($order['order_details'], true);
            }
           
            return $order;
        } catch (Exception $e) {
            error_log("Error fetching order by ID: " . $e->getMessage());
            return false;
        }
    }
   
    /**
     * Get all orders
     *
     * @return array Array of orders
     */
    public function getAllOrders() {
        try {
            $orders = $this->db->fetchAll("SELECT * FROM orders ORDER BY created_at DESC");
           
            foreach ($orders as &$order) {
                $order['order_details'] = json_decode($order['order_details'], true);
            }
           
            return $orders;
        } catch (Exception $e) {
            error_log("Error fetching all orders: " . $e->getMessage());
            return [];
        }
    }
   
    /**
     * Format order data for API response
     *
     * @param array $order Raw order data
     * @return array Formatted order data
     */
    public function formatOrderData($order) {
        // Extract order details
        $orderDetails = $order['order_details'] ?? [];
        
        // Prepare customer data from both sources
        $customer = [
            'name' => $order['customer_name'],
            'company' => $order['customer_company'],
            'email' => $order['customer_email'],
            'phone' => $order['customer_phone'],
            'needsDelivery' => (bool)($order['needs_delivery'] ?? 0),
            'deliveryAddress' => $order['delivery_address'] ?? null
        ];
        
        // Combine with any additional customer data from order_details
        if (isset($orderDetails['customer'])) {
            $customer = array_merge($customer, $orderDetails['customer']);
        }
        
        return [
            'orderId' => $order['id'],
            'customer' => $customer,
            'items' => $orderDetails['items'] ?? [],
            'timestamp' => $order['created_at'],
            'estimatedResponse' => '24 hours'
        ];
    }
   
    /**
     * Send order confirmation email
     *
     * @param array $order_data Order data
     * @return bool True on success, false on failure
     */
    public function sendOrderEmail($order_data) {
        try {
            $customer = $order_data['customer'] ?? [];
            $to = defined('EMAIL_TO') ? EMAIL_TO : 'dev@prpl.com.au';
            $subject = "Quotation Request from {$customer['name']} at {$customer['company']}";
            
            // Use custom formatter or fall back to global function
            $body = method_exists($this, 'formatOrderEmail') 
                ? $this->formatOrderEmail($order_data) 
                : (function_exists('format_order_email') ? format_order_email($order_data) : $this->defaultFormatOrderEmail($order_data));
            
            // Use custom email sender or fall back to global function
            $result = function_exists('send_email') 
                ? send_email($to, $subject, $body)
                : $this->defaultSendEmail($to, $subject, $body);
                
            return $result;
        } catch (Exception $e) {
            error_log("Error sending order email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Default email formatter in case the global function isn't available
     * 
     * @param array $orderData Order data
     * @return string Formatted email body
     */
    private function defaultFormatOrderEmail($orderData) {
        // Extract data
        $customer = $orderData['customer'] ?? [];
        $items = $orderData['items'] ?? [];
        $timestamp = date('Y-m-d H:i:s');
        
        if (isset($orderData['timestamp'])) {
            if (is_string($orderData['timestamp'])) {
                $timestamp = $orderData['timestamp'];
            } else {
                $timestamp = date('Y-m-d H:i:s', $orderData['timestamp']);
            }
        }
        
        // Start building email body
        $email = "New quotation request details:\n\n";
        
        // Customer information
        $email .= "Customer Information:\n";
        $email .= "Name: " . ($customer['name'] ?? 'N/A') . "\n";
        $email .= "Company: " . ($customer['company'] ?? 'N/A') . "\n";
        $email .= "Email: " . ($customer['email'] ?? 'N/A') . "\n";
        $email .= "Phone: " . ($customer['phone'] ?? 'N/A') . "\n";
        
        // Add delivery details if present
        if (isset($customer['needsDelivery']) && $customer['needsDelivery']) {
            $email .= "Needs Delivery: Yes\n";
            $email .= "Delivery Address: " . ($customer['deliveryAddress'] ?? 'Not provided') . "\n";
        } else {
            $email .= "Needs Delivery: No\n";
        }
        
        $email .= "\nOrder Details:\n";
        
        // Format each item
        foreach ($items as $index => $item) {
            $email .= "\nItem " . ($index + 1) . ": ";
            $email .= ($item['productName'] ?? 'Unknown Product');
            $email .= " (ID: " . ($item['productId'] ?? 'N/A') . ")\n";
            
            if (isset($item['isSpecialOrder']) && $item['isSpecialOrder']) {
                $email .= "(Special Order)\n";
            }
            
            $email .= "Specifications:\n";
            
            // Format selections - handle both old and new format
            if (isset($item['selections'])) {
                if (is_array($item['selections'])) {
                    // New format - key-value object
                    foreach ($item['selections'] as $key => $value) {
                        $email .= "- " . $key . ": " . $value . "\n";
                    }
                } else {
                    // Old format or unexpected value
                    $email .= "- " . $item['selections'] . "\n";
                }
            } else {
                $email .= "- No specifications provided\n";
            }
        }
        
        // Add timestamp
        $email .= "\nTimestamp: " . $timestamp . "\n";
        
        return $email;
    }
    
    /**
     * Default email sender in case the global function isn't available
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body
     * @return bool True if email was sent successfully
     */
    private function defaultSendEmail($to, $subject, $body) {
        // Simple implementation using mail()
        $headers = "From: Website Notification <no-reply@prpl.com.au>\r\n";
        $headers .= "Reply-To: dev@prpl.com.au\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Try to send the email
        $sent = mail($to, $subject, $body, $headers);
        
        if (!$sent) {
            error_log("Failed to send email: " . (error_get_last()['message'] ?? 'Unknown error'));
        }
        
        return $sent;
    }
}