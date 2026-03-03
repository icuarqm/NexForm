<?php

class Database {
    // This will hold actual PDO Connection Object
    private ?PDO $pdo = null;

    // This constructor connects automatically when the object is created
    public function __construct() {
        $dsn = "mysql:host=" . getenv("MYSQL_HOST") . ";dbname=" . getenv("MYSQL_DATABASE") . ";charset=utf8mb4";
        
        // Classic Options
        $options = [
            // Throw exceptions to catch clearly
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            // Return data as a clean Array
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
            // Turn off emulation to enforce real SQL Injection protection
            PDO::ATTR_EMULATE_PREPARES   => false,                  
        ];

        try {
            $this->pdo = new PDO($dsn, getenv("MYSQL_USER"), getenv("MYSQL_ROOT_PASSWORD"), $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Getter
    public function connect(): ?PDO {
        return $this->pdo;
    }

}