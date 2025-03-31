<?php
// Database configuration file
class DatabaseConfig {
    private static $host = DB_HOST;
    private static $username = DB_USER;
    private static $password = DB_PASSWORD;
    private static $database = DB_NAME;
    private static $instance = null;
    
    // Get database connection
    public static function getConnection() {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    'mysql:host=' . self::$host . ';dbname=' . self::$database . ';charset=utf8',
                    self::$username,
                    self::$password,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                die('Database connection error: ' . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
}

/**
 * Database schema for reference:
 * 
 * -- From purple_engineering.sql:
 * -- products table structure
 * CREATE TABLE `products` (
 *  `id` int(11) NOT NULL AUTO_INCREMENT,
 *  `name` varchar(100) NOT NULL,
 *  `product_type` varchar(100) DEFAULT NULL,
 *  `flange_size` varchar(50) NOT NULL,
 *  `flange_type` varchar(50) NOT NULL,
 *  `material` varchar(100) NOT NULL,
 *  `operating_pressure` varchar(50) NOT NULL,
 *  `operating_temperature` varchar(50) NOT NULL,
 *  `screen_size` varchar(50) NOT NULL,
 *  `image_url` varchar(255) DEFAULT 'https://via.placeholder.com/50',
 *  `created_by` int(11) DEFAULT NULL,
 *  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 *  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
 *  PRIMARY KEY (`id`),
 *  KEY `created_by` (`created_by`)
 * );
 * 
 * -- users table structure
 * CREATE TABLE `users` (
 *  `id` int(11) NOT NULL AUTO_INCREMENT,
 *  `username` varchar(50) NOT NULL,
 *  `password` varchar(255) NOT NULL,
 *  `email` varchar(100) NOT NULL,
 *  `role` enum('admin','user') DEFAULT 'user',
 *  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 *  `last_login` timestamp NULL DEFAULT NULL,
 *  PRIMARY KEY (`id`),
 *  UNIQUE KEY `username` (`username`),
 *  UNIQUE KEY `email` (`email`)
 * );
 * 
 * -- We need to add an orders table (not in original schema)
 * CREATE TABLE IF NOT EXISTS `orders` (
 *  `id` int(11) NOT NULL AUTO_INCREMENT,
 *  `customer_name` varchar(100) NOT NULL,
 *  `customer_company` varchar(100) NOT NULL,
 *  `customer_email` varchar(100) NOT NULL,
 *  `customer_phone` varchar(50) NOT NULL,
 *  `order_details` text NOT NULL,
 *  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 *  PRIMARY KEY (`id`)
 * );
 */