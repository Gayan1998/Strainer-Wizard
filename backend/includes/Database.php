<?php
/**
 * Database class for database operations
 */
class Database {
    private $connection;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->connection = DatabaseConfig::getConnection();
    }
    
    /**
     * Execute a query
     *
     * @param string $query SQL query
     * @param array $params Parameters for the query
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch a single row
     *
     * @param string $query SQL query
     * @param array $params Parameters for the query
     * @return array|false Single row or false if no result
     */
    public function fetchRow($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch all rows
     *
     * @param string $query SQL query
     * @param array $params Parameters for the query
     * @return array Array of rows
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert data into a table
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return string|false Last insert ID or false on failure
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->query($query, array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update data in a table
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where Where clause
     * @param array $params Parameters for the where clause
     * @return int Number of rows affected
     */
    public function update($table, $data, $where, $params = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }
        
        $set_clause = implode(', ', $set);
        $query = "UPDATE {$table} SET {$set_clause} WHERE {$where}";
        
        $stmt = $this->query($query, array_merge(array_values($data), $params));
        return $stmt->rowCount();
    }
    
    /**
     * Delete data from a table
     *
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Parameters for the where clause
     * @return int Number of rows affected
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
}