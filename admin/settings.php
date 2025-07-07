<?php
session_start();
if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../inc/config.php';
require_once '../inc/db.php';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = $_POST['site_title'] ?? '工具网';
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'toolweb';
    $custom_header = $_POST['custom_header'] ?? '';
    $footer_notices = [];
    if (!empty($_POST['footer_notice_type']) && is_array($_POST['footer_notice_type'])) {
        foreach ($_POST['footer_notice_type'] as $i => $type) {
            $content = trim($_POST['footer_notice_content'][$i] ?? '');
            $url = trim($_POST['footer_notice_url'][$i] ?? '');
            if ($content !== '') {
                $footer_notices[] = [
                    'type' => $type,
                    'content' => $content,
                    'url' => $url
                ];
            }
        }
    }
    $footer_separator = isset($_POST['footer_separator']) ? $_POST['footer_separator'] : '|';
    
    // 测试数据库连接
    $test_conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$test_conn) {
        $error = '数据库连接失败：' . mysqli_connect_error();
    } else {
        // 保存配置
        $config_data = [
            'site_title' => $site_title,
            'db_host' => $db_host,
            'db_user' => $db_user,
            'db_pass' => $db_pass,
            'db_name' => $db_name,
            'custom_header' => $custom_header,
            'footer_notices' => $footer_notices,
            'footer_separator' => $footer_separator
        ];
        
        if (save_config($config_data)) {
            $msg = '配置保存成功！';
        } else {
            $error = '配置保存失败，请检查文件权限';
        }
        mysqli_close($test_conn);
    }
}

// 加载当前配置
$current_config = load_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .settings-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .settings-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .settings-form {
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
        .form-group textarea {
            width: 100%;
            min-height: 80px;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="settings-header">
            <h1>⚙️ 网站设置</h1>
        </div>
        
        <div class="settings-form">
        <form method="post">
        <div class="form-section">
            <h2>📝 网站信息</h2>
            <div class="form-group">
                <label>网站标题</label>
                <input type="text" name="site_title" value="<?= htmlspecialchars($current_config['site_title'] ?? '工具网') ?>" required>
            </div>
        </div>
        
        <div class="form-section">
            <h2>🗄️ 数据库配置</h2>
            <div class="form-group">
                <label>数据库主机</label>
                <input type="text" name="db_host" value="<?= htmlspecialchars($current_config['db_host'] ?? 'localhost') ?>" required>
            </div>
            <div class="form-group">
                <label>数据库用户</label>
                <input type="text" name="db_user" value="<?= htmlspecialchars($current_config['db_user'] ?? 'root') ?>" required>
            </div>
            <div class="form-group">
                <label>数据库密码</label>
                <input type="password" name="db_pass" value="<?= htmlspecialchars($current_config['db_pass'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>数据库名称</label>
                <input type="text" name="db_name" value="<?= htmlspecialchars($current_config['db_name'] ?? 'toolweb') ?>" required>
            </div>
        </div>
        
        <div class="form-section">
            <h2>🌐 自定义Header代码</h2>
            <div class="form-group">
                <label>自定义Header代码（可插入统计、meta、样式等，支持HTML/JS/CSS）</label>
                <textarea name="custom_header" placeholder="&lt;!-- 这里可以写自定义header代码 --&gt;"><?= htmlspecialchars($current_config['custom_header'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-section">
            <h2>📢 底部公告</h2>
            <div id="footer-notice-list">
                <?php
                $footer_notices = $current_config['footer_notices'] ?? [];
                if (!$footer_notices) $footer_notices = [['type'=>'text','content'=>'','url'=>'']];
                foreach ($footer_notices as $i => $item): ?>
                <div class="footer-notice-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;">
                    <select name="footer_notice_type[]">
                        <option value="text" <?= $item['type']==='text'?'selected':''; ?>>文本</option>
                        <option value="link" <?= $item['type']==='link'?'selected':''; ?>>链接</option>
                    </select>
                    <input type="text" name="footer_notice_content[]" value="<?= htmlspecialchars($item['content']) ?>" placeholder="内容" style="width:160px;">
                    <input type="text" name="footer_notice_url[]" value="<?= htmlspecialchars($item['url']??'') ?>" placeholder="链接地址(仅类型为链接)" style="width:180px;">
                    <button type="button" onclick="this.parentNode.remove();">删除</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addFooterNoticeRow();">添加一行</button>
            <div style="margin-top:10px;">
                <label><input type="checkbox" id="footer-more-custom" onclick="document.getElementById('footer-separator-wrap').style.display=this.checked?'block':'none';"> 更多自定义</label>
            </div>
            <div id="footer-separator-wrap" style="display:none;margin-top:8px;">
                <label>分隔符：</label>
                <input type="text" name="footer_separator" value="<?= htmlspecialchars($current_config['footer_separator'] ?? '|') ?>" style="width:60px;">
            </div>
            <script>
            function addFooterNoticeRow() {
                var html = `<div class=\"footer-notice-item\" style=\"margin-bottom:8px;display:flex;gap:8px;align-items:center;\">`+
                    `<select name=\"footer_notice_type[]\"><option value=\"text\">文本</option><option value=\"link\">链接</option></select>`+
                    `<input type=\"text\" name=\"footer_notice_content[]\" placeholder=\"内容\" style=\"width:160px;\">`+
                    `<input type=\"text\" name=\"footer_notice_url[]\" placeholder=\"链接地址(仅类型为链接)\" style=\"width:180px;\">`+
                    `<button type=\"button\" onclick=\"this.parentNode.remove();\">删除</button>`+
                    `</div>`;
                document.getElementById('footer-notice-list').insertAdjacentHTML('beforeend', html);
            }
            // 初始化分隔符显示
            window.addEventListener('DOMContentLoaded',function(){
                var more = document.getElementById('footer-more-custom');
                var wrap = document.getElementById('footer-separator-wrap');
                if ('<?= htmlspecialchars($current_config['footer_separator'] ?? '') ?>' && (('<?= htmlspecialchars($current_config['footer_separator'] ?? '') ?>' !== '|'))) {
                    more.checked = true; wrap.style.display = 'block';
                }
            });
            </script>
        </div>
        
            <button type="submit" class="submit-btn">💾 保存设置</button>
</form>

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

<div class="back-link">
    <a href="dashboard.php">← 返回后台首页</a>
        </div>
    </div>
</body>
</html> 