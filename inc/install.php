<?php
require_once 'config.php';

// 检查安装状态
function check_installation() {
    return is_configured();
}

// 获取安装信息
function get_installation_info() {
    if (check_installation()) {
        $config = load_config();
        return [
            'installed' => true,
            'site_title' => $config['site_title'] ?? '工具网',
            'db_host' => $config['db_host'] ?? 'localhost',
            'db_name' => $config['db_name'] ?? 'toolweb',
            'admin_user' => $config['admin_user'] ?? 'admin'
        ];
    }
    return ['installed' => false];
}

// 重装功能（删除配置文件）
function reset_installation() {
    $config_file = __DIR__ . '/config_data.php';
    if (file_exists($config_file)) {
        return unlink($config_file);
    }
    return false;
}
?> 