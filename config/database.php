<?php
// Database configuration
class Database {
    
    // database connection variables
    private $host = "localhost";
    private $database_name = "user_management_system";
    private $username = "dhruv";
    private $password = "dhruv123";
    public $conn;
    
    // get database connection
    public function getConnection() {
        
        $this->conn = null;
        
        try {
            // create new PDO connection
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database_name,
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