<?php
/**
 * Database Configuration
 * Kuliner Restaurant Management System
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kuliner');
define('DB_CHARSET', 'utf8mb4');

function getDB(): mysqli {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die(json_encode([
                'error' => true,
                'message' => 'Koneksi database gagal: ' . $conn->connect_error
            ]));
        }

        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}
