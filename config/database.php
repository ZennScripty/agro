<?php
/**
 * SAMRIDHI AGRO - Database Configuration
 * 
 * This file contains database connection settings and establishes
 * a secure PDO connection to MySQL.
 * 
 * @package SamridhiAgro
 * @subpackage Config
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Database credentials - NEVER expose these in public view
define('DB_HOST', 'localhost');
define('DB_NAME', 'samridhi_agro');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has empty password
define('DB_CHARSET', 'utf8mb4');

// ============================================
// DATABASE CONNECTION CLASS
// ============================================

class Database {
    /**
     * @var PDO|null The database connection instance
     */
    private static $instance = null;
    
    /**
     * @var PDO The PDO connection object
     */
    private $connection;
    
    /**
     * Private constructor to prevent direct instantiation
     * Establishes PDO connection with secure settings
     */
    private function __construct() {
        try {
            // Build DSN (Data Source Name)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            // PDO options for security and performance
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Fetch associative arrays by default
                PDO::ATTR_EMULATE_PREPARES => false,                  // Use real prepared statements
                PDO::ATTR_PERSISTENT => false,                        // Don't use persistent connections
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",  // Ensure proper charset
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,           // Use buffered queries
            ];
            
            // Create the PDO connection
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log the error but don't expose sensitive details
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Display a user-friendly error message
            die("
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Database Connection Error</title>
                    <style>
                        body { font-family: Arial, sans-serif; background: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                        .error-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
                        h1 { color: #dc3545; margin-bottom: 10px; }
                        p { color: #6c757d; line-height: 1.6; }
                        .icon { font-size: 48px; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class='error-box'>
                        <div class='icon'>🔌</div>
                        <h1>Database Connection Failed</h1>
                        <p>Unable to connect to the database. Please check your configuration and ensure the database service is running.</p>
                        <p style='font-size: 12px; color: #999;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>
                    </div>
                </body>
                </html>
            ");
        }
    }
    
    /**
     * Get the singleton database instance
     * 
     * @return Database The database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the PDO connection object
     * 
     * @return PDO The PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prepare and execute a query with parameters
     * 
     * @param string $sql The SQL query with placeholders
     * @param array $params The parameters to bind
     * @return PDOStatement The prepared statement
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch a single row from the database
     * 
     * @param string $sql The SQL query with placeholders
     * @param array $params The parameters to bind
     * @return array|null The fetched row or null if no results
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows from the database
     * 
     * @param string $sql The SQL query with placeholders
     * @param array $params The parameters to bind
     * @return array All fetched rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string The last inserted ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if a transaction is active
     * 
     * @return bool True if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
    
    /**
     * Quote a string for safe use in SQL queries
     * Use prepared statements instead when possible
     * 
     * @param string $string The string to quote
     * @return string The quoted string
     */
    public function quote($string) {
        return $this->connection->quote($string);
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {}
}

// ============================================
// HELPER FUNCTIONS FOR EASY DATABASE ACCESS
// ============================================

/**
 * Get the database connection instance
 * 
 * @return Database The database instance
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Get the PDO connection object directly
 * 
 * @return PDO The PDO connection
 */
function getPDO() {
    return Database::getInstance()->getConnection();
}

/**
 * Execute a query and return the statement
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters
 * @return PDOStatement The prepared statement
 */
function dbQuery($sql, $params = []) {
    return Database::getInstance()->query($sql, $params);
}

/**
 * Fetch a single row
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters
 * @return array|null The row or null
 */
function dbFetchOne($sql, $params = []) {
    return Database::getInstance()->fetchOne($sql, $params);
}

/**
 * Fetch all rows
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters
 * @return array All rows
 */
function dbFetchAll($sql, $params = []) {
    return Database::getInstance()->fetchAll($sql, $params);
}

/**
 * Get the last inserted ID
 * 
 * @return string The last inserted ID
 */
function dbLastInsertId() {
    return Database::getInstance()->lastInsertId();
}