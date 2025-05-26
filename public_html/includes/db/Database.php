<?php
/**
 * Database Class
 * 
 * Handles database connections and operations
 */

class Database {
    private static $instance = null;
    private $conn;
    private $statement;
    
    /**
     * Constructor - Creates a database connection using PDO
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Connection Error: " . $e->getMessage());
            } else {
                die("Database Connection Error. Please contact the administrator.");
            }
        }
    }
    
    /**
     * Get Database Instance (Singleton pattern)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection object
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return bool True on success, false on failure
     */
    public function query($query, $params = []) {
        try {
            $this->statement = $this->conn->prepare($query);
            return $this->statement->execute($params);
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Query Error: " . $e->getMessage());
            } else {
                return false;
            }
        }
    }
    
    /**
     * Get a single record
     * 
     * @return mixed The fetched row or false
     */
    public function fetch() {
        return $this->statement->fetch();
    }
    
    /**
     * Get all records
     * 
     * @return array The fetched rows
     */
    public function fetchAll() {
        return $this->statement->fetchAll();
    }
    
    /**
     * Get number of rows
     * 
     * @return int Number of rows
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    /**
     * Get last inserted ID
     * 
     * @return string Last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commitTransaction() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollbackTransaction() {
        return $this->conn->rollBack();
    }
}
