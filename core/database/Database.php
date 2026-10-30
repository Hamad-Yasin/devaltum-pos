<?php

class Database {
    private static $instance = null;
    private $pdo;
    private $connectionError = null;

    private function __construct() {
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
            // Installation required
            return;
        }

        $portPart = defined('DB_PORT') && !empty(DB_PORT) ? ';port=' . DB_PORT : '';
        $dsn = 'mysql:host=' . DB_HOST . $portPart . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            $tzOffset = date('P');
            if (!empty($tzOffset)) {
                $this->pdo->exec("SET time_zone = '$tzOffset'");
            }
        } catch (\PDOException $e) {
            $this->connectionError = $e->getMessage();
            $this->pdo = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function hasConnection() {
        return $this->pdo instanceof PDO;
    }

    public function getConnectionError() {
        return $this->connectionError;
    }
    
    public function query($sql, $params = []) {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    public function execute($sql, $params = []) {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId() {
        return $this->pdo ? $this->pdo->lastInsertId() : null;
    }
}
