<?php
// 网站配置文件
$config = [
    'site_title' => '工具网',
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'toolweb'
];

// 检查是否已配置
function is_configured() {
    $config_file = __DIR__ . '/config_data.php';
    if (!file_exists($config_file)) {
        return false;
    }
    
    $config = load_config();
    if (empty($config)) {
        return false;
    }
    
    try {
        $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
        return !$conn->connect_error;
    } catch (Exception $e) {
        return false;
    }
}

// 保存配置
function save_config($data) {
    $config_file = __DIR__ . '/config_data.php';
    $content = "<?php\nreturn " . var_export($data, true) . ";\n";
    return file_put_contents($config_file, $content);
}

// 加载配置
function load_config() {
    $config_file = __DIR__ . '/config_data.php';
    if (file_exists($config_file)) {
        return include $config_file;
    }
    return [];
} 