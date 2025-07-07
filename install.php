<?php
session_start();
require_once 'inc/install.php';
require_once 'inc/db.php';

// 登录校验，未登录跳转
if (empty($_SESSION['admin'])) {
    header('Location: admin/login.php');
    exit;
}

$install_info = get_installation_info();
$msg = '';
$error = '';

// 处理重装请求，增加二次密码验证
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_user = $_SESSION['admin_user'] ?? ($install_info['admin_user'] ?? 'admin');
    if (!$admin_pass) {
        $error = '请填写管理员密码';
    } else {
        $conn = get_db();
        $stmt = $conn->prepare('SELECT password FROM admin WHERE username = ?');
        $stmt->bind_param('s', $admin_user);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($admin_pass, $row['password'])) {
                if (reset_installation()) {
                    $msg = '重装成功！请刷新页面重新配置。';
                    $install_info = get_installation_info();
                } else {
                    $error = '重装失败，请手动删除 inc/config_data.php 文件';
                }
            } else {
                $error = '管理员密码错误，无法重置！';
            }
        } else {
            $error = '管理员账户不存在！';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装状态 - 工具网</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .install-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .install-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .install-content {
            padding: 40px;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }
        .status-card.not-installed {
            border-left-color: #dc3545;
        }
        .status-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .status-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .status-desc {
            color: #666;
            line-height: 1.6;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            color: #333;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .action-btn.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🔧 安装状态检查</h1>
        </div>
        
        <div class="install-content">
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
            
            <?php if($install_info['installed']): ?>
                <div class="status-card">
                    <div class="status-icon">✅</div>
                    <div class="status-title">已安装</div>
                    <div class="status-desc">
                        工具网已成功安装并配置完成。您可以正常使用所有功能。
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">网站标题</div>
                        <div class="info-value"><?= htmlspecialchars($install_info['site_title']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">数据库主机</div>
                        <div class="info-value"><?= htmlspecialchars($install_info['db_host']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">数据库名称</div>
                        <div class="info-value"><?= htmlspecialchars($install_info['db_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">管理员用户</div>
                        <div class="info-value"><?= htmlspecialchars($install_info['admin_user']) ?></div>
                    </div>
                </div>
                
                <div class="warning-box">
                    ⚠️ <strong>注意：</strong> 如果您需要重新安装，请先备份重要数据。
                </div>
                
                <div class="action-buttons">
                    <a href="index.php" class="action-btn primary">
                        🏠 进入首页
                    </a>
                    <a href="admin/login.php" class="action-btn primary">
                        🔐 后台登录
                    </a>
                    <form method="post" style="display: inline;">
                        <input type="password" name="admin_pass" placeholder="请输入管理员密码" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;margin-right:10px;" required>
                        <button type="submit" name="reset" class="action-btn danger" 
                                onclick="return confirm('确定要重新安装吗？这将删除所有配置信息。')">
                            🔄 重新安装
                        </button>
                    </form>
                </div>
                
            <?php else: ?>
                <div class="status-card not-installed">
                    <div class="status-icon">❌</div>
                    <div class="status-title">未安装</div>
                    <div class="status-desc">
                        工具网尚未安装。请点击下方按钮开始安装配置。
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="setup.php" class="action-btn primary">
                        🚀 开始安装
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 