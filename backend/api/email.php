<?php
/**
 * Email API endpoint
 * This can be used to send emails directly from the frontend
 * Updated for localhost testing environment and new data structure
 */

// Include helper functions and Response class
// Assuming they are already included in your setup

// Configuration for localhost testing
define('EMAIL_TO', 'dev@prpl.com.au'); // Change this to your testing email
define('EMAIL_FROM_NAME', 'Website Contact Form');
define('EMAIL_FROM', 'no-reply@localhost');
define('EMAIL_REPLY_TO', ''); // If empty, will use EMAIL_FROM

// For localhost testing with SMTP (requires PHPMailer)
define('USE_SMTP', true); // Set to true to use SMTP instead of mail()
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 1025); // Common port for MailHog
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

/**
 * Enhanced email sending function with SMTP support for localhost
 * Extends the existing send_email function
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @param string $from_name Sender name
 * @param string $from_email Sender email
 * @return bool True on success, false on failure
 */
function send_email_enhanced($to, $subject, $body, $from_name = null, $from_email = null) {
    // If SMTP is not enabled, use the original send_email function
    if (!defined('USE_SMTP') || !USE_SMTP) {
        return send_email($to, $subject, $body, $from_name, $from_email);
    }

    // Use SMTP with PHPMailer if available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // If PHPMailer is not available, log warning and fall back to regular mail
        error_log('PHPMailer not found. Falling back to regular mail() function.');
        return send_email($to, $subject, $body, $from_name, $from_email);
    }

    try {
        // Include PHPMailer if using Composer
        // If not using Composer, ensure these files are included elsewhere
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Set default values if not provided
        $from_name = $from_name ?: EMAIL_FROM_NAME;
        $from_email = $from_email ?: EMAIL_FROM;
        $reply_to = EMAIL_REPLY_TO ?: $from_email;
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = !empty(SMTP_USERNAME);
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = SMTP_PORT;
        
        // For debugging (helpful for localhost testing)
        $mail->SMTPDebug = 0; // Set to 2 for verbose debugging
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($reply_to);
        
        // Content
        $mail->isHTML(false); // Set to true if you want to send HTML emails
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Parse and format the order data from a JSON request
 * Updated for the new data structure
 *
 * @param array $orderData The order data from the request
 * @return string Formatted email body
 */
function format_order_email($orderData) {
    // Extract data
    $customer = $orderData['customer'] ?? [];
    $items = $orderData['items'] ?? [];
    
    // Build email body
    $email = "New quotation request details:\n\n";
    
    // Customer information
    $email .= "Customer Information:\n";
    $email .= "Name: " . ($customer['name'] ?? 'N/A') . "\n";
    $email .= "Company: " . ($customer['company'] ?? 'N/A') . "\n";
    $email .= "Email: " . ($customer['email'] ?? 'N/A') . "\n";
    $email .= "Phone: " . ($customer['phone'] ?? 'N/A') . "\n";
    
    // Add delivery information - ensure this is included
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
        $email .= ($item['productName'] ?? 'Unknown Product') . "\n";
        
        if (isset($item['isSpecialOrder']) && $item['isSpecialOrder']) {
            $email .= "(Special Order)\n";
        }
        
        $email .= "Specifications:\n";
        
        // Format selections
        if (isset($item['selections']) && is_array($item['selections'])) {
            foreach ($item['selections'] as $key => $value) {
                $email .= "- " . $key . ": " . $value . "\n";
            }
        } else {
            $email .= "- No specifications provided\n";
        }
    }
    
    // Add timestamp
    $email .= "\nTimestamp: " . date('Y-m-d H:i:s') . "\n";
    
    return $email;
}

// Handle the API request
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'POST':
        // Get input data
        $data = get_json_input();
       
        // Validate required fields
        if (isset($data['orderData'])) {
            // Processing an order submission
            $orderData = $data['orderData'];
            
            // Basic validation of order data
            if (!isset($orderData['items']) || !is_array($orderData['items']) || empty($orderData['items'])) {
                Response::json_error('Order must contain at least one item', 400);
            }
            
            if (!isset($orderData['customer']) || !is_array($orderData['customer'])) {
                Response::json_error('Customer information is required', 400);
            }
            
            // Format the email body from order data
            $email_body = format_order_email($orderData);
            
            // Set email details
            $to = EMAIL_TO;
            $subject = "Quotation Request from " . 
                (isset($orderData['customer']['name']) ? $orderData['customer']['name'] : 'Website User') . 
                " at " . 
                (isset($orderData['customer']['company']) ? $orderData['customer']['company'] : 'Unknown Company');
            
            // Send the email
            $sent = send_email_enhanced($to, $subject, $email_body);
            
        } else {
            // Standard email submission
            // Validate required fields
            $required_fields = ['to', 'subject', 'body'];
            $missing = validate_required_fields($data, $required_fields);
           
            if (!empty($missing)) {
                Response::json_error(format_missing_fields_error($missing), 400);
            }
           
            // Validate email format
            if (!validate_email($data['to'])) {
                Response::json_error('Please enter a valid recipient email address', 400);
            }
           
            // Only allow emails to predefined address for security
            if ($data['to'] !== EMAIL_TO) {
                Response::json_error('Invalid recipient email address', 400);
            }
           
            // Clean input data
            $data = clean_input($data);
           
            // Send email using enhanced function
            $sent = send_email_enhanced(
                $data['to'],
                $data['subject'],
                $data['body'],
                $data['from_name'] ?? null,
                $data['from_email'] ?? null
            );
        }
        
        if (!$sent) {
            Response::json_error('Failed to send email', 500);
        }
       
        Response::json_success(['message' => 'Email sent successfully']);
        break;
       
    case 'OPTIONS':
        // Handle preflight requests for CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit;
       
    default:
        Response::json_error('Method not allowed', 405);
        break;
}