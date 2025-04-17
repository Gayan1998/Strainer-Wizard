<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Include PHPSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            $email .= ($item['productName'] ?? $item['product']['name'] ?? 'Unknown Product');
            $email .= " (ID: " . ($item['productId'] ?? $item['product']['id'] ?? 'N/A') . ")\n";
            
            // Add quantity information here:
            $email .= "Quantity: " . ($item['quantity'] ?? 1) . "\n";
            
            if (isset($item['isSpecialOrder']) && $item['isSpecialOrder']) {
                $email .= "(Special Order)\n";
            }
                
            $email .= "Specifications:\n";
                
            // Format selections - handle both old and new format
            if (isset($item['selections'])) {
                if (is_array($item['selections'])) {
                    // Check if it's an array of objects with stage property
                    if (isset($item['selections'][0]) && is_array($item['selections'][0])) {
                        foreach ($item['selections'] as $selection) {
                            $stageName = $selection['stage'] ?? '';
                            $optionValue = $selection['optionName'] ?? '';
                            
                            // Clean up custom prefix if present
                            if (is_string($optionValue) && strpos($optionValue, 'custom:') === 0) {
                                $optionValue = substr($optionValue, 7);
                            }
                            
                            $email .= "- {$stageName}: {$optionValue}\n";
                        }
                    } else {
                        // Key-value object format
                        foreach ($item['selections'] as $key => $value) {
                            // Clean up custom prefix if present
                            if (is_string($value) && strpos($value, 'custom:') === 0) {
                                $value = substr($value, 7);
                            }
                            
                            $email .= "- {$key}: {$value}\n";
                        }
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

    /**
     * Send order confirmation email with Excel attachment
     *
     * @param array $order_data Order data
     * @param string $attachment_path Path to attachment file
     * @return bool True on success, false on failure
     */
    public function sendOrderEmailWithAttachment($order_data, $attachment_path) {
        try {
            $customer = $order_data['customer'] ?? [];
            $to = defined('EMAIL_TO') ? EMAIL_TO : 'dev@prpl.com.au';
            $subject = "Quotation Request from {$customer['name']} at {$customer['company']}";
            
            // Use the existing formatter
            $body = $this->defaultFormatOrderEmail($order_data);
            
            // Add note about attachment
            $body .= "\n\nPlease see the attached Excel quotation.\n";
            
            // If attachment exists, send with attachment
            if (file_exists($attachment_path)) {
                $filename = basename($attachment_path);
                
                // Boundary for multipart message
                $boundary = md5(time());
                
                // Headers
                $headers = "From: Purple Engineering <dev@prpl.com.au>\r\n";
                $headers .= "Reply-To: dev@prpl.com.au\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
                
                // Email body with attachment
                $message = "--$boundary\r\n";
                $message .= "Content-Type: text/plain; charset=utf-8\r\n";
                $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $message .= $body . "\r\n";
                
                // Attachment
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
                $message .= chunk_split(base64_encode(file_get_contents($attachment_path))) . "\r\n";
                $message .= "--$boundary--";
                
                // Send email
                $sent = mail($to, $subject, $message, $headers);
                
                // Log result
                error_log("Email with attachment sent: " . ($sent ? 'success' : 'failed'));
                
                return $sent;
            } else {
                // If attachment doesn't exist, fall back to regular email
                error_log("Attachment file not found: $attachment_path - falling back to regular email");
                return $this->sendOrderEmail($order_data);
            }
        } catch (Exception $e) {
            error_log("Error sending order email with attachment: " . $e->getMessage());
            return false;
        }
    }

/**
 * Generate Excel quotation with improved styling based on QuotationGenerator class
 * 
 * @param array $orderData Order data
 * @param string $order_id Order ID
 * @return string|false Path to generated Excel file or false on failure
 */
public function generateExcelQuotation($orderData, $order_id) {
    try {
        // Brand color - Purple Engineering
        $brandColor = 'B1A0C7';
        $brandColorDarker = '8E7AAB'; // Darker shade for some elements
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);     // Item #
        $sheet->getColumnDimension('B')->setWidth(15);    // Qty (pcs)
        $sheet->getColumnDimension('C')->setWidth(45);    // Product Description
        $sheet->getColumnDimension('D')->setWidth(18);    // Unit Price (AUD)
        $sheet->getColumnDimension('E')->setWidth(18);    // Total Price (AUD)
        
        // Set default font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        
        // HEADER SECTION
        // Try to add the Purple Engineering banner if available
        $bannerPath = __DIR__ . '/../images/purple_engineering_banner.png';
        if (file_exists($bannerPath)) {
            $sheet->mergeCells('A1:E7');
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Banner');
            $drawing->setDescription('Purple Engineering Banner');
            $drawing->setPath($bannerPath);
            $drawing->setCoordinates('A1');
            $drawing->setResizeProportional(true);
            $drawing->setWidth(750);
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension(1)->setRowHeight(140);
            for ($i = 2; $i <= 7; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(1);
            }
        } else {
            // Fallback header without image
            $sheet->setCellValue('A1', 'Purple Engineering Pty Ltd');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($brandColorDarker));
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $sheet->setCellValue('A2', 'ABN: 79 163 404 644 | Phone: 1300 62 40 20 | Fax: 08-6323 0605');
            $sheet->mergeCells('A2:E2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            $sheet->setCellValue('A4', 'Phone: +61- 1300 62 40 20');
            $sheet->setCellValue('A5', 'Email: info@PRPL.com.au');
            $sheet->setCellValue('E4', 'Providing engineering solutions and equipment for:');
            $sheet->setCellValue('E5', 'Marine Projects');
            $sheet->setCellValue('E6', 'Oil & Gas');
            $sheet->setCellValue('E7', 'Energy');
        }
        
        // Set quotation number using date pattern S + YYMMDD + sequential number
        $date_part = date('ymd');
        $quoteNumber = 'S-' . $date_part . '-S' . substr(str_replace('ORD-', '', $order_id), 0, 3);
        
        $sheet->setCellValue('A8', 'Quote #:');
        $sheet->setCellValue('B8', $quoteNumber);
        $sheet->getStyle('A8:B8')->getFont()->setBold(true);
        
        // CONTACT INFORMATION
        $customer = $orderData['customer'] ?? [];
        $sheet->setCellValue('A9', 'To:');
        $sheet->setCellValue('B9', $customer['company'] ?? 'N/A');
        $sheet->getStyle('A9')->getFont()->setBold(true);
        
        $sheet->setCellValue('A10', 'Att:');
        $sheet->setCellValue('B10', $customer['name'] ?? 'N/A');
        $sheet->getStyle('A10')->getFont()->setBold(true);
        
        $sheet->setCellValue('A11', 'From:');
        $sheet->setCellValue('B11', 'Alex Ocean');
        $sheet->getStyle('A11')->getFont()->setBold(true);
        
        $sheet->setCellValue('A12', 'Date:');
        $sheet->setCellValue('B12', date('d/m/Y'));
        $sheet->getStyle('A12')->getFont()->setBold(true);
        
        // INTRODUCTION TEXT
        $sheet->setCellValue('A13', 'It is with great pleasure for us to supply our quote.');
        $sheet->mergeCells('A13:E13');
        
        $sheet->setCellValue('A14', 'We are hoping to provide an outstanding service with our products and be the best supplier you ever had.');
        $sheet->mergeCells('A14:E14');
        
        // PRODUCTS TABLE
        $rowStart = 16;
        $sheet->setCellValue('A' . $rowStart, 'Item');
        $sheet->setCellValue('B' . $rowStart, 'Qty (pcs)');
        $sheet->setCellValue('C' . $rowStart, 'Product Description');
        $sheet->setCellValue('D' . $rowStart, 'Unit Price');
        $sheet->setCellValue('E' . $rowStart, 'Total Price');
        $sheet->getStyle('A' . $rowStart . ':E' . $rowStart)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowStart . ':E' . $rowStart)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        
        // Apply branded header style
        $headerStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => $brandColor],
            ],
        ];
        $sheet->getStyle('A' . $rowStart . ':E' . $rowStart)->applyFromArray($headerStyle);
        $sheet->getStyle('A' . $rowStart . ':E' . $rowStart)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Items
        $items = $orderData['items'] ?? [];
        $rowCurrent = $rowStart + 1;
        $subtotal = 0;
        
        foreach ($items as $index => $item) {
            $isSpecial = isset($item['isSpecialOrder']) && $item['isSpecialOrder'];
            $productId = $item['productId'] ?? ($item['product']['id'] ?? 'CUSTOM');
            
            // Get quantity from item data
            $quantity = $item['quantity'] ?? 1;
            
            // Fetch product details from database if not a special order
            $productDetails = [];
            $unitPrice = 0;
            
            if (!$isSpecial && $productId !== 'CUSTOM') {
                $productDetails = $this->db->fetchRow("SELECT * FROM products WHERE id = ?", [$productId]) ?? [];
                $unitPrice = $productDetails['price'] ?? 0;
            }
            
            // Product name handling
            $productName = $item['productName'] ?? $item['product']['name'] ?? 'Unknown Product';
            if ($isSpecial) {
                $productName .= ' (SPECIAL ORDER)';
            }
            
            // Build product description
            $description = '';
            
            // Check if product has a description in the database
            if (!empty($productDetails['product_description'])) {
                $description = $productDetails['product_description'];
            } else {
                // Start with product name
                $description = $productName;
                
                // Build specifications section
                $specs = '';
                
                if (isset($item['selections'])) {
                    $selections = $item['selections'];
                    
                    if (is_array($selections)) {
                        // Format 1: Array of objects with stage and optionName
                        if (!empty($selections) && isset($selections[0]) && is_array($selections[0])) {
                            foreach ($selections as $selection) {
                                if (isset($selection['stage']) && isset($selection['optionName'])) {
                                    $optionName = $selection['optionName'];
                                    if (is_string($optionName) && strpos($optionName, 'custom:') === 0) {
                                        $optionName = substr($optionName, 7);
                                    }
                                    $specs .= $selection['stage'] . ': ' . $optionName . "\n";
                                }
                            }
                        } 
                        // Format 2: Associative array of key-value pairs
                        else {
                            foreach ($selections as $key => $value) {
                                if ($value) {  // Check if value is not empty
                                    $displayValue = $value;
                                    if (is_string($value) && strpos($value, 'custom:') === 0) {
                                        $displayValue = substr($value, 7);
                                    }
                                    $specs .= $key . ': ' . $displayValue . "\n";
                                }
                            }
                        }
                    }
                }
                
                // If no specs were generated from selections, try getting from product details
                if (empty(trim($specs))) {
                    if (!empty($productDetails)) {
                        // Format as a single line of specifications
                        $simpleSpecs = '';
                        if (!empty($productDetails['product_type'])) $simpleSpecs .= $productDetails['product_type'];
                        if (!empty($productDetails['material'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $productDetails['material'];
                        if (!empty($productDetails['flange_type'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $productDetails['flange_type'];
                        if (!empty($productDetails['flange_size'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $productDetails['flange_size'];
                        if (!empty($productDetails['operating_pressure'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $productDetails['operating_pressure'];
                        
                        if (!empty($simpleSpecs)) {
                            $specs = $simpleSpecs;
                        }
                    } else if (isset($item['product']) && is_array($item['product'])) {
                        // Format as a single line of specifications from frontend data
                        $simpleSpecs = '';
                        if (!empty($item['product']['type'])) $simpleSpecs .= $item['product']['type'];
                        if (!empty($item['product']['material'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $item['product']['material'];
                        if (!empty($item['product']['connection'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $item['product']['connection'];
                        if (!empty($item['product']['size'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $item['product']['size'];
                        if (!empty($item['product']['pressure'])) $simpleSpecs .= ($simpleSpecs ? ', ' : '') . $item['product']['pressure'];
                        
                        if (!empty($simpleSpecs)) {
                            $specs = $simpleSpecs;
                        }
                    }
                }
                
                // Add specs to description with extra spacing for better display
                $description = "\n" . $productName;
                if (!empty(trim($specs))) {
                    $description .= "\n" . $specs;
                }
                $description .= "\nLead Time: 2-3 weeks\n";
            }
            
            // Calculate total price
            $totalPrice = $unitPrice * $quantity;
            $subtotal += $totalPrice;
            
            // Set values in the spreadsheet
            $sheet->setCellValue('A' . $rowCurrent, ($index + 1));
            $sheet->setCellValue('B' . $rowCurrent, $quantity);
            $sheet->setCellValue('C' . $rowCurrent, $description);
            
            // Add price information if available
            if ($unitPrice > 0) {
                $sheet->setCellValue('D' . $rowCurrent, '$' . number_format($unitPrice, 2));
                $sheet->setCellValue('E' . $rowCurrent, '$' . number_format($totalPrice, 2));
            } else {
                $sheet->setCellValue('D' . $rowCurrent, '');
                $sheet->setCellValue('E' . $rowCurrent, '');
            }
            
            // Format cells
            $sheet->getStyle('C' . $rowCurrent)->getAlignment()->setWrapText(true);
            $sheet->getStyle('D' . $rowCurrent)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
            $sheet->getStyle('E' . $rowCurrent)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
            
            // Apply borders to row
            $sheet->getStyle('A' . $rowCurrent . ':E' . $rowCurrent)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
            ]);
            
            // Apply alternating row colors
            if ($index % 2 == 1) {
                $sheet->getStyle('A' . $rowCurrent . ':E' . $rowCurrent)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F0FA');
            }
            
            // Set row height to properly display the full product description
            // Calculate approximate height based on the number of lines in the description
            $lineCount = substr_count($description, "\n") + 3; // Add 2 extra lines (top and bottom)
            $minHeight = max(24, $lineCount * 15); // 15 pixels per line, minimum 24 pixels
            $sheet->getRowDimension($rowCurrent)->setRowHeight($minHeight);
            
            $rowCurrent++;
        }
        
        // FREIGHT OPTION
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Freight Option');
        
        // Set freight description based on delivery option
        if (isset($orderData['customer']['needsDelivery']) && $orderData['customer']['needsDelivery']) {
            $sheet->setCellValue('C' . $rowCurrent, 'Freight to ' . ($orderData['customer']['deliveryAddress'] ?? 'your address'));
        } else {
            $sheet->setCellValue('C' . $rowCurrent, 'Freight Collection from Welshpool');
        }
        
        $sheet->setCellValue('D' . $rowCurrent, '$0.00');
        $sheet->setCellValue('E' . $rowCurrent, '$0.00');
        $sheet->getStyle('D' . $rowCurrent . ':E' . $rowCurrent)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
        $sheet->getStyle('A' . $rowCurrent . ':E' . $rowCurrent)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);
        $sheet->getStyle('A' . $rowCurrent . ':E' . $rowCurrent)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F5F0FA');
        
        // TOTALS SECTION
        $rowCurrent += 2;
        $sheet->getStyle('D' . $rowCurrent . ':E' . ($rowCurrent + 2))->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        $sheet->setCellValue('D' . $rowCurrent, 'Total (exc GST):');
        if ($subtotal > 0) {
            $sheet->setCellValue('E' . $rowCurrent, '$' . number_format($subtotal, 2));
        } else {
            $sheet->setCellValue('E' . $rowCurrent, '');
        }
        $sheet->getStyle('D' . $rowCurrent . ':E' . $rowCurrent)->getFont()->setBold(true);
        $sheet->getStyle('E' . $rowCurrent)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
        
        $rowCurrent++;
        $sheet->setCellValue('D' . $rowCurrent, 'GST Value:');
        if ($subtotal > 0) {
            $gst = $subtotal * 0.1; // 10% GST
            $sheet->setCellValue('E' . $rowCurrent, '$' . number_format($gst, 2));
        } else {
            $sheet->setCellValue('E' . $rowCurrent, '');
        }
        $sheet->getStyle('D' . $rowCurrent . ':E' . $rowCurrent)->getFont()->setBold(true);
        $sheet->getStyle('E' . $rowCurrent)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
        
        $rowCurrent++;
        $sheet->setCellValue('D' . $rowCurrent, 'Total (inc GST):');
        if ($subtotal > 0) {
            $total = $subtotal * 1.1; // Add 10% GST
            $sheet->setCellValue('E' . $rowCurrent, '$' . number_format($total, 2));
        } else {
            $sheet->setCellValue('E' . $rowCurrent, '');
        }
        $sheet->getStyle('D' . $rowCurrent . ':E' . $rowCurrent)->getFont()->setBold(true);
        $sheet->getStyle('E' . $rowCurrent)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');
        
        // Style totals block
        $sheet->getStyle('D' . ($rowCurrent - 2) . ':E' . $rowCurrent)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EDE8F3');
        $sheet->getStyle('D' . ($rowCurrent - 2) . ':E' . $rowCurrent)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);
        
        // DELIVERY INFO
        $rowCurrent += 2;
        $sheet->setCellValue('A' . $rowCurrent, 'Delivery date:');
        $sheet->setCellValue('C' . $rowCurrent, '2-3 weeks');
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setBold(true);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Delivery location:');
        if (isset($orderData['customer']['needsDelivery']) && $orderData['customer']['needsDelivery']) {
            $sheet->setCellValue('C' . $rowCurrent, $orderData['customer']['deliveryAddress'] ?? 'Your address');
        } else {
            $sheet->setCellValue('C' . $rowCurrent, 'Collection from Welshpool');
        }
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setBold(true);
        
        // TERMS & CONDITIONS
        $rowCurrent += 2;
        $sheet->setCellValue('A' . $rowCurrent, 'Terms & Conditions');
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($brandColorDarker));
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Offer validity:');
        $sheet->setCellValue('C' . $rowCurrent, '30 days.');
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setBold(true);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Payment terms:');
        $sheet->setCellValue('C' . $rowCurrent, 'Full payment with the order.');
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setBold(true);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Prices & delivery times based on receipt of entire scope of work quoted above.');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Prices are valid for above quantities and all material is offered subject to remaining availability.');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'If quantities are revised or scope of achievement is changed, we reserve the right to adjust prices accordingly after previous consultation.');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'Orders once placed cannot be replaced.');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        
        // FOOTER
        $rowCurrent += 2;
        $sheet->setCellValue('A' . $rowCurrent, 'Purple Engineering maintains the ownership of the products sold until full payment of the price. Throughout the reserve of the title period, as the risks are transferred in accordance, the Customer, as custodian, should insure the products against all risks of damage or responsibility. The parties specifically agree that the Products stored with the Customer are deemed as corresponding to the outstanding invoices. The customer is authorized to resell or transform the Products delivered in the normal course of its business. Where appropriate, the customer undertakes to transfer its receivables to Purple Engineering on sub-purchasers up to the amounts owed.');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        $sheet->getStyle('A' . $rowCurrent)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($rowCurrent)->setRowHeight(-1);
        $sheet->getStyle('A' . $rowCurrent . ':E' . $rowCurrent)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F9F7FB');
        
        $rowCurrent += 2;
        $sheet->setCellValue('A' . $rowCurrent, 'Alex Ocean – CEO | Mobile: +61 (0) 412 64 5751 | MIEAust');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        $sheet->getStyle('A' . $rowCurrent)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $rowCurrent++;
        $sheet->setCellValue('A' . $rowCurrent, 'www.PRPL.com.au | info@PRPL.com.au | Phone: +61-1300 62 40 20');
        $sheet->mergeCells('A' . $rowCurrent . ':E' . $rowCurrent);
        $sheet->getStyle('A' . $rowCurrent)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $rowCurrent)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($brandColorDarker));
        
        // Save the Excel file
        $filename = 'quotation_' . $quoteNumber . '.xlsx';
        $filepath = __DIR__ . '/../temp/' . $filename;
        
        // Create temp directory if it doesn't exist
        if (!file_exists(__DIR__ . '/../temp/')) {
            mkdir(__DIR__ . '/../temp/', 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return $filepath;
    } catch (Exception $e) {
        error_log('Error generating Excel quotation: ' . $e->getMessage());
        return false;
    }
}
}