<?php
require_once __DIR__ . '/config.php';

function get_db() {
    $config = load_config();
    $host = $config['db_host'] ?? 'localhost';
    $user = $config['db_user'] ?? 'root';
    $pass = $config['db_pass'] ?? '';
    $dbname = $config['db_name'] ?? 'toolweb';
    
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die('数据库连接失败: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
} 