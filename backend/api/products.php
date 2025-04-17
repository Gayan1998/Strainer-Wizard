<?php
/**
 * Products API endpoint - Matched to your actual database schema
 */

// Enable error logging
error_log('Products API request received: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);

// Include database connection
require_once __DIR__ . '/../includes/Database.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    http_response_code(405);
    exit;
}

// Extract filter parameters from request
$type = $_GET['type'] ?? null;
$material = $_GET['material'] ?? null;
$connection = $_GET['connection'] ?? null; // This needs mapping
$size = $_GET['size'] ?? null;
$pressure = $_GET['pressure'] ?? null;

// Log filter parameters
error_log('Filter parameters: type=' . $type . ', material=' . $material . 
          ', connection=' . $connection . ', size=' . $size . ', pressure=' . $pressure);

try {
    // Initialize database connection
    $db = new Database();
    
    // Start with a simple query to get all products
    $query = "SELECT * FROM products";
    $params = [];
    $whereAdded = false;
    
    // Add filters based on your actual database column names
    // Using your actual database structure
    if (!empty($type)) {
        $query .= $whereAdded ? " AND " : " WHERE ";
        $query .= "product_type = ?";
        $params[] = $type;
        $whereAdded = true;
    }
    
    if (!empty($material)) {
        $query .= $whereAdded ? " AND " : " WHERE ";
        $query .= "material = ?";
        $params[] = $material;
        $whereAdded = true;
    }
    
    // Map connection type to flange_type
    if (!empty($connection)) {
        $query .= $whereAdded ? " AND " : " WHERE ";
        $query .= "flange_type = ?";
        $params[] = $connection;
        $whereAdded = true;
    }
    
    // Map size to flange_size
    if (!empty($size)) {
        $query .= $whereAdded ? " AND " : " WHERE ";
        $query .= "flange_size = ?";
        $params[] = $size;
        $whereAdded = true;
    }
    
    // Map pressure to operating_pressure
    if (!empty($pressure)) {
        $query .= $whereAdded ? " AND " : " WHERE ";
        $query .= "operating_pressure = ?";
        $params[] = $pressure;
        $whereAdded = true;
    }
    
    // Log the query for debugging
    error_log('Database query: ' . $query . ' with params: ' . json_encode($params));
    
    // Run the query
    $products = $db->fetchAll($query, $params);
    
    // Debug output - log raw results
    error_log('Raw DB results count: ' . count($products));
    
    // Transform results to match frontend expectations
    $transformedProducts = [];
    foreach ($products as $product) {
        // Map database columns to frontend expected format
        $transformedProducts[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'type' => $product['product_type'],
            'material' => $product['material'],
            'connection' => $product['flange_type'],
            'size' => $product['flange_size'],
            'pressure' => $product['operating_pressure'],
            // Use product_description if available, otherwise generate description
            'description' => $product['product_description'] ?? 
                ($product['name'] . ' - ' . $product['product_type']),
            'image' => $product['image_url'] ?? '/api/placeholder/200/200',
            'specs' => [
                'screenSize' => $product['screen_size'] ?? 'Standard',
                'temperature' => $product['operating_temperature'] ?? 'Standard',
                'weight' => 'Varies by model'
            ]
            // Note: Intentionally NOT including price here
        ];
    }
    
    // Return products as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $transformedProducts,
        'count' => count($transformedProducts)
    ]);
    http_response_code(200);
    
} catch (Exception $e) {
    // Log and return error
    error_log('Products API error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while retrieving products',
        'error' => $e->getMessage()
    ]);
    http_response_code(500);
}