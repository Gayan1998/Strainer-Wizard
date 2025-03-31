<?php
require_once __DIR__ . '/../includes/Database.php';

/**
 * Product model - Updated to match the Purple Engineering database schema
 */
class Product {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get all products
     *
     * @return array Array of products
     */
    public function getAllProducts() {
        return $this->db->fetchAll("SELECT * FROM products ORDER BY name");
    }
    
    /**
     * Get product by ID
     *
     * @param int $id Product ID
     * @return array|false Product data or false if not found
     */
    public function getProductById($id) {
        return $this->db->fetchRow("SELECT * FROM products WHERE id = ?", [$id]);
    }
    
    /**
     * Filter products by criteria
     * 
     * This function has been updated to match the Purple Engineering database schema
     *
     * @param array $filters Filter criteria
     * @return array Filtered products
     */
    public function filterProducts($filters) {
        // Map frontend filter names to database column names
        $filterMap = [
            'type' => 'product_type',
            'material' => 'material',
            'connection' => 'flange_type',
            'size' => 'flange_size',
            'pressure' => 'operating_pressure'
        ];
        
        $query = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        foreach ($filterMap as $frontendKey => $dbColumn) {
            if (!empty($filters[$frontendKey])) {
                $query .= " AND {$dbColumn} = ?";
                $params[] = $filters[$frontendKey];
            }
        }
        
        $query .= " ORDER BY name";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Format product data for API response
     * 
     * Maps database column names to frontend property names
     *
     * @param array $product Raw product data
     * @return array Formatted product data
     */
    public function formatProductData($product) {
        // Calculate flow rate based on size and pressure (this would need a proper formula)
        $flowRate = "Calculated based on " . $product['flange_size'] . " and " . $product['operating_pressure'];
        
        // Estimate weight based on material and size (this would need a proper formula)
        $weight = "Estimated based on " . $product['material'] . " and " . $product['flange_size'];
        
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'type' => $product['product_type'],
            'material' => $product['material'],
            'connection' => $product['flange_type'],
            'size' => $product['flange_size'],
            'pressure' => $product['operating_pressure'],
            'description' => 'High-quality strainer by Purple Engineering', // Not in DB schema
            'image' => $product['image_url'],
            'specs' => [
                'screenSize' => $product['screen_size'],
                'temperature' => $product['operating_temperature'],
                'flowRate' => $flowRate,
                'weight' => $weight
            ]
        ];
    }
    
    /**
     * Create a new product
     *
     * @param array $data Product data
     * @return int|false Product ID or false on failure
     */
    public function createProduct($data) {
        $product_data = [
            'name' => $data['name'],
            'product_type' => $data['type'],
            'flange_size' => $data['size'],
            'flange_type' => $data['connection'],
            'material' => $data['material'],
            'operating_pressure' => $data['pressure'],
            'operating_temperature' => $data['specs']['temperature'] ?? '20°C', // Default if not provided
            'screen_size' => $data['specs']['screenSize'] ?? '40 mesh', // Default if not provided
            'image_url' => $data['image'] ?? 'https://via.placeholder.com/50',
            'created_by' => 1 // Default admin user ID
        ];
        
        return $this->db->insert('products', $product_data);
    }
    
    /**
     * Update an existing product
     *
     * @param int $id Product ID
     * @param array $data Product data
     * @return int Number of rows affected
     */
    public function updateProduct($id, $data) {
        $product_data = [];
        
        if (isset($data['name'])) $product_data['name'] = $data['name'];
        if (isset($data['type'])) $product_data['product_type'] = $data['type'];
        if (isset($data['size'])) $product_data['flange_size'] = $data['size'];
        if (isset($data['connection'])) $product_data['flange_type'] = $data['connection'];
        if (isset($data['material'])) $product_data['material'] = $data['material'];
        if (isset($data['pressure'])) $product_data['operating_pressure'] = $data['pressure'];
        if (isset($data['specs']['temperature'])) $product_data['operating_temperature'] = $data['specs']['temperature'];
        if (isset($data['specs']['screenSize'])) $product_data['screen_size'] = $data['specs']['screenSize'];
        if (isset($data['image'])) $product_data['image_url'] = $data['image'];
        
        return $this->db->update('products', $product_data, 'id = ?', [$id]);
    }
    
    /**
     * Delete a product
     *
     * @param int $id Product ID
     * @return int Number of rows affected
     */
    public function deleteProduct($id) {
        return $this->db->delete('products', 'id = ?', [$id]);
    }
}