<?php
/**
 * Database Configuration
 * Konfigurasi koneksi database MySQL
 */

class Database {
    // Database configuration
    private $host = '127.0.0.1';
    private $dbname = 'web_app_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    // Connection instance
    private $pdo;
    
    /**
     * Create database connection
     * @return PDO
     */
    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                // Log error (in production, don't show details)
                error_log("Database connection error: " . $e->getMessage());
                die("Database connection failed. Please check configuration.");
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $pdo = $this->connect();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database info
     * @return array
     */
    public function getInfo() {
        try {
            $pdo = $this->connect();
            $stmt = $pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            
            return [
                'host' => $this->host,
                'database' => $this->dbname,
                'version' => $result['version'],
                'charset' => $this->charset
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Close connection
     */
    public function disconnect() {
        $this->pdo = null;
    }
}

// Create global database instance
$database = new Database();
$pdo = $database->connect();

/**
 * Helper function to get database connection
 * @return PDO
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Execute prepared statement with parameters
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
}

/**
 * Get single record
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function getRecord($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Get multiple records
 * @param string $sql
 * @param array $params
 * @return array
 */
function getRecords($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insert record and return last insert ID
 * @param string $sql
 * @param array $params
 * @return int
 */
function insertRecord($sql, $params = []) {
    executeQuery($sql, $params);
    return getDB()->lastInsertId();
}

/**
 * Update/Delete record and return affected rows
 * @param string $sql
 * @param array $params
 * @return int
 */
function updateRecord($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Update record error: " . $e->getMessage());
        return 0;
    }
}

?>