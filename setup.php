<?php
require_once 'inc/config.php';

// 检查是否已经配置完成，如果已配置则禁止访问
if (is_configured()) {
    header('Location: index.php');
    exit;
}

$msg = '';
$error = '';
$redirect_script = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = $_POST['site_title'] ?? '工具网';
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'toolweb';
    $admin_user = $_POST['admin_user'] ?? 'admin';
    $admin_pass = $_POST['admin_pass'] ?? '';
    
    // 验证管理员密码
    if (strlen($admin_pass) < 6) {
        $error = '管理员密码至少需要6位字符';
    } else {
        // 测试数据库连接
        $test_conn = @mysqli_connect($db_host, $db_user, $db_pass);
        if (!$test_conn) {
            $error = '数据库连接失败：' . mysqli_connect_error();
        } else {
            // 创建数据库
            if (!mysqli_select_db($test_conn, $db_name)) {
                mysqli_query($test_conn, "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARSET utf8mb4");
            }
            mysqli_select_db($test_conn, $db_name);
            
                    // 创建tools表
        $sql = "CREATE TABLE IF NOT EXISTS tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            icon VARCHAR(255) DEFAULT '🛠️',
            url VARCHAR(255) NOT NULL,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            section_id INT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            // 创建sections表
            $sections_sql = "CREATE TABLE IF NOT EXISTS sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            // 创建admin表
            $admin_sql = "CREATE TABLE IF NOT EXISTS admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (mysqli_query($test_conn, $sql) && mysqli_query($test_conn, $sections_sql) && mysqli_query($test_conn, $admin_sql)) {
                // 插入管理员账户
                $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
                $insert_admin = "INSERT INTO admin (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE password = ?";
                $stmt = mysqli_prepare($test_conn, $insert_admin);
                mysqli_stmt_bind_param($stmt, 'sss', $admin_user, $hashed_password, $hashed_password);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // 保存配置
                $config_data = [
                    'site_title' => $site_title,
                    'db_host' => $db_host,
                    'db_user' => $db_user,
                    'db_pass' => $db_pass,
                    'db_name' => $db_name,
                    'admin_user' => $admin_user
                ];
                
                if (save_config($config_data)) {
                    $msg = '配置成功！正在跳转到首页...';
                    $redirect_script = '<script>setTimeout(function(){window.location.href="index.php";}, 2000);</script>';
                } else {
                    $error = '配置保存失败，请检查文件权限';
                }
            } else {
                $error = '数据表创建失败：' . mysqli_error($test_conn);
            }
            mysqli_close($test_conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站初始配置</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .setup-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .setup-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .setup-form {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-section {
            margin-bottom: 35px;
        }
        .form-section h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .progress-bar {
            background: #f0f0f0;
            border-radius: 10px;
            height: 6px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
    <?= $redirect_script ?>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>🚀 工具网初始配置</h1>
            <p>欢迎使用工具网，请完成以下配置以开始使用</p>
        </div>
        
        <div class="setup-form">
            <div class="progress-bar">
                <div class="progress-fill" id="progress"></div>
            </div>
            
            <?php if($msg): ?>
                <div class="message success">
                    ✅ <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="message error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="setupForm">
                <div class="form-section">
                    <h2>📝 网站信息</h2>
                    <div class="form-group">
                        <label>网站标题</label>
                        <input type="text" name="site_title" value="工具网" required placeholder="请输入网站标题">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>👤 管理员账户</h2>
                    <div class="form-group">
                        <label>管理员用户名</label>
                        <input type="text" name="admin_user" value="admin" required placeholder="请输入管理员用户名">
                    </div>
                    <div class="form-group">
                        <label>管理员密码</label>
                        <input type="password" name="admin_pass" required placeholder="请输入管理员密码（至少6位）">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>🗄️ 数据库配置</h2>
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input type="text" name="db_host" value="localhost" required placeholder="数据库服务器地址">
                    </div>
                    <div class="form-group">
                        <label>数据库用户</label>
                        <input type="text" name="db_user" value="root" required placeholder="数据库用户名">
                    </div>
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_pass" placeholder="数据库密码（可选）">
                    </div>
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" value="toolweb" required placeholder="数据库名称">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    💾 保存配置
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // 进度条动画
        const progressBar = document.getElementById('progress');
        const form = document.getElementById('setupForm');
        
        form.addEventListener('submit', function() {
            progressBar.style.width = '100%';
        });
        
        // 表单验证
        form.addEventListener('input', function() {
            const inputs = form.querySelectorAll('input[required]');
            let filled = 0;
            inputs.forEach(input => {
                if (input.value.trim()) filled++;
            });
            progressBar.style.width = (filled / inputs.length * 100) + '%';
        });
    </script>
</body>
</html> 