<?php
session_start();
if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../inc/config.php';
require_once '../inc/db.php';

// 获取统计数据
try {
    $conn = get_db();
    $tools_count = $conn->query('SELECT COUNT(*) as count FROM tools')->fetch_assoc()['count'];
    $admin_user = $_SESSION['admin_user'] ?? '管理员';
    $conn->close();
} catch (Exception $e) {
    $tools_count = 0;
    $admin_user = $_SESSION['admin_user'] ?? '管理员';
}

// 获取网站配置
$config = load_config();
$site_title = $config['site_title'] ?? '工具网';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?= htmlspecialchars($site_title) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: '微软雅黑', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            padding: 48px 0 28px 0;
            background: white;
            border-radius: 18px;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(102,126,234,0.10);
        }
        
        .dashboard-icon {
            font-size: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dashboard-title {
            font-size: 2.6rem;
            font-weight: 900;
            background: linear-gradient(90deg, #667eea, #764ba2 80%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
            text-shadow: 1px 1px 8px rgba(80,80,80,0.10);
            display: flex;
            align-items: center;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 300;
            margin: 0;
        }
        
        .user-info {
            text-align: right;
        }
        
        .welcome-text {
            color: #666;
            margin-bottom: 5px;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 16px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .menu-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .menu-title {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .menu-desc {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .quick-actions h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .action-btn.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .user-info {
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div class="user-info">
                <div class="welcome-text">欢迎回来，<?= htmlspecialchars($admin_user) ?></div>
                <a href="logout.php" class="logout-btn">🚪 退出登录</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🛠️</div>
                <div class="stat-number"><?= $tools_count ?></div>
                <div class="stat-label">工具总数</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🌐</div>
                <div class="stat-number"><?= htmlspecialchars($site_title) ?></div>
                <div class="stat-label">网站标题</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-number"><?= $admin_user ?></div>
                <div class="stat-label">当前用户</div>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="plugin_manage.php" class="menu-card">
                <div class="menu-icon">🛠️</div>
                <div class="menu-title">插件管理</div>
                <div class="menu-desc">管理和编辑所有功能插件</div>
            </a>
            
            <a href="settings.php" class="menu-card">
                <div class="menu-icon">⚙️</div>
                <div class="menu-title">网站设置</div>
                <div class="menu-desc">修改网站标题、数据库配置等基本设置</div>
            </a>
        </div>
        
        <div class="quick-actions">
            <h2>🚀 快速操作</h2>
            <div class="action-buttons">
                <a href="tools.php" class="action-btn">
                    ➕ 添加工具
                </a>
                <a href="settings.php" class="action-btn">
                    ⚙️ 修改设置
                </a>
                <a href="../index.php" class="action-btn secondary">
                    🏠 查看首页
                </a>
                <a href="logout.php" class="action-btn danger">
                    🚪 退出登录
                </a>
            </div>
        </div>
    </div>
</body>
</html> 