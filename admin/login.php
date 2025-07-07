<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';

$err = '';
$lock_key = 'login_lock_' . ($_POST['user'] ?? '');
$fail_key = 'login_fail_' . ($_POST['user'] ?? '');
$lock_time = 300; // 5分钟

if (isset($_SESSION[$lock_key]) && $_SESSION[$lock_key] > time()) {
    $err = '密码输错次数过多，请5分钟后再试。';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    
    if ($user && $pass) {
        try {
            $conn = get_db();
            $stmt = $conn->prepare('SELECT password FROM admin WHERE username = ?');
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if (password_verify($pass, $row['password'])) {
                    $_SESSION['admin'] = true;
                    $_SESSION['admin_user'] = $user;
                    unset($_SESSION[$fail_key]);
                    unset($_SESSION[$lock_key]);
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $_SESSION[$fail_key] = ($_SESSION[$fail_key] ?? 0) + 1;
                    if ($_SESSION[$fail_key] >= 5) {
                        $_SESSION[$lock_key] = time() + $lock_time;
                        $err = '密码输错次数过多，请5分钟后再试。';
                    } else {
                        $err = '密码错误，连续输错5次将锁定5分钟。';
                    }
                }
            } else {
                $err = '用户不存在';
            }
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $err = '登录失败，请检查数据库连接';
        }
    } else {
        $err = '请输入账号和密码';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .login-form {
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
        .login-btn {
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
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 后台登录</h1>
        </div>
        
        <div class="login-form">
            <?php if($err): ?>
                <div class="error-message">
                    ❌ <?= htmlspecialchars($err) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="user" required placeholder="请输入管理员用户名">
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="pass" required placeholder="请输入管理员密码">
                </div>
                <button type="submit" class="login-btn">
                    🔑 登录
                </button>
            </form>
            
            <div class="back-link">
                <a href="../index.php">← 返回首页</a>
            </div>
        </div>
    </div>
</body>
</html> 