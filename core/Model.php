<?php

class Model {
    protected $db;

    public function __construct() {
        if (file_exists(CONFIG_PATH . '/database.php')) {
            $this->db = Database::getInstance();
        }
    }
}
