<?php
// Load environment variables from .env file
function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    return true;
}

// Load .env file
loadEnv();

// Database configuration
class Database {
    
    // database connection variables (with fallback to defaults)
    private $host;
    private $database;
    private $username;
    private $password;
    public $conn;
    
    public function __construct() {
        // Load from environment variables with fallback to hardcoded defaults
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->database = getenv('DB_NAME') ?: 'user_management_system';
        $this->username = getenv('DB_USER') ?: 'dhruv';
        $this->password = getenv('DB_PASS') ?: 'dhruv123';
    }
    
    // get database connection
    public function getConnection() {
        
        $this->conn = null;
        
        try {
            // create new PDO connection
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password
            );
            
            // set character set to utf8
            $this->conn->exec("set names utf8");
            
            // set error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            // show error message if connection fails
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>