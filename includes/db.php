<?php
require_once __DIR__ . '/config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;  
    private $stmt;
    private $error;
    
    public function __construct() {
      
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        
        // Set PDO options
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Error: " . $this->error);
            if (DEBUG_MODE) {
                die("Database Connection Failed: " . $this->error);
            } else {
                die("Database error. Please try again later.");
            }
        }
    }
    
   
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }
    
    // Bind values
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    
  
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            if (DEBUG_MODE) {
                throw new Exception("Query failed: " . $e->getMessage());
            }
            return false;
        }
    }
    
    // Get result set
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Get single record
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
  
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    public function commit() {
        return $this->dbh->commit();
    }
    
    public function rollBack() {
        return $this->dbh->rollBack();
    }
    
    // Close connection
    public function close() {
        $this->dbh = null;
    }
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
}