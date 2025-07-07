<?php
session_start();
if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once '../inc/config.php';
require_once '../inc/db.php';

$conn = get_db();
$msg = '';
$error = '';

// 插件表名为 plugins
// 如无表则自动创建
$conn->query("CREATE TABLE IF NOT EXISTS plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(255) DEFAULT '🧩',
    url VARCHAR(255) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 添加插件
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $icon = $_POST['icon'] ?? '🧩';
    $url = $_POST['url'] ?? '';
    if ($name && $url) {
        $stmt = $conn->prepare('INSERT INTO plugins (name, description, icon, url, status) VALUES (?, ?, ?, ?, 1)');
        if ($stmt) {
            $stmt->bind_param('ssss', $name, $desc, $icon, $url);
            $result = $stmt->execute();
            if ($result) {
                $msg = '插件添加成功！';
            } else {
                $error = '插件添加失败：' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'SQL预处理失败：' . $conn->error;
        }
    } else {
        $error = '请填写插件名称和链接';
    }
}
// 删除插件
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $conn->query('DELETE FROM plugins WHERE id=' . $id);
}
// 切换显示/隐藏状态
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query('UPDATE plugins SET status = NOT status WHERE id = ' . $id);
}
// 编辑插件
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_plugin = $conn->query('SELECT * FROM plugins WHERE id=' . $edit_id)->fetch_assoc();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $icon = $_POST['icon'] ?? '🧩';
    $url = $_POST['url'] ?? '';
    if ($name && $url) {
        $stmt = $conn->prepare('UPDATE plugins SET name=?, description=?, icon=?, url=? WHERE id=?');
        if ($stmt) {
            $stmt->bind_param('ssssi', $name, $desc, $icon, $url, $edit_id);
            $result = $stmt->execute();
            if ($result) {
                $msg = '插件编辑成功！';
                header('Location: plugin_manage.php');
                exit;
            } else {
                $error = '插件编辑失败：' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'SQL预处理失败：' . $conn->error;
        }
    } else {
        $error = '请填写插件名称和链接';
    }
}
$plugins = $conn->query('SELECT * FROM plugins');

// 1. 读取已启用插件配置
$header_file = '../inc/header_include.php';
$enabled_plugins = [];
if (file_exists($header_file)) {
    $content = file_get_contents($header_file);
    foreach ([
        'Cloudflare Turnstile 验证' => 'turnstile',
        'Bing每日壁纸背景' => 'bingbg',
        '粉色樱花雪特效' => 'sakura',
        '音乐播放器' => 'music',
        '鼠标点击特效' => 'clickfx',
    ] as $title => $key) {
        if (strpos($content, $key.'-plugin-start') !== false) {
            $enabled_plugins[$key] = true;
        }
    }
}
// 2. 处理保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plugin_header'])) {
    $plugin_map = [
        'turnstile' => [
            'title' => 'Cloudflare Turnstile 验证',
            'code' => '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>',
        ],
        'bingbg' => [
            'title' => 'Bing每日壁纸背景',
            'code' => '<style>body{background-image:url(https://bing.img.run/rand_uhd.php);background-size:cover;background-attachment:fixed;}</style>',
        ],
        'sakura' => [
            'title' => '粉色樱花雪特效',
            'code' => '<script src="https://player.xfyun.club/js/yinghua.js"></script>',
        ],
        'music' => [
            'title' => '音乐播放器',
            'code' => '<div id="xf-MusicPlayer" data-cdnName="https://player.xfyun.club/js"  data-themeColor="xf-orange" data-fadeOutAutoplay data-memory="1" data-random="true"></div>\n<script src="https://player.xfyun.club/js/xf-MusicPlayer/js/xf-MusicPlayer.min.js"></script>',
        ],
        'clickfx' => [
            'title' => '鼠标点击特效',
            'code' => '<script>function clickEffect() { let balls = []; let longPressed = false; let longPress; let multiplier = 0; let width, height; let origin; let normal; let ctx; const colours = ["#F73859", "#14FFEC", "#00E0FF", "#FF99FE", "#FAF15D"]; const canvas = document.createElement("canvas"); document.body.appendChild(canvas); canvas.setAttribute("style", "width: 100%; height: 100%; top: 0; left: 0; z-index: 99999; position: fixed; pointer-events: none;"); const pointer = document.createElement("span"); pointer.classList.add("pointer"); document.body.appendChild(pointer); if (canvas.getContext && window.addEventListener) { ctx = canvas.getContext("2d"); updateSize(); window.addEventListener("resize", updateSize, false); loop(); window.addEventListener("mousedown", function(e) { pushBalls(randBetween(10, 20), e.clientX, e.clientY); document.body.classList.add("is-pressed"); longPress = setTimeout(function(){ document.body.classList.add("is-longpress"); longPressed = true; }, 500); }, false); window.addEventListener("mouseup", function(e) { clearInterval(longPress); if (longPressed == true) { document.body.classList.remove("is-longpress"); pushBalls(randBetween(50 + Math.ceil(multiplier), 100 + Math.ceil(multiplier)), e.clientX, e.clientY); longPressed = false; } document.body.classList.remove("is-pressed"); }, false); window.addEventListener("mousemove", function(e) { let x = e.clientX; let y = e.clientY; pointer.style.top = y + "px"; pointer.style.left = x + "px"; }, false); } else { console.log("canvas or addEventListener is unsupported!"); } function updateSize() { canvas.width = window.innerWidth * 2; canvas.height = window.innerHeight * 2; canvas.style.width = window.innerWidth + 'px'; canvas.style.height = window.innerHeight + 'px'; ctx.scale(2, 2); width = (canvas.width = window.innerWidth); height = (canvas.height = window.innerHeight); origin = { x: width / 2, y: height / 2 }; normal = { x: width / 2, y: height / 2 }; } class Ball { constructor(x = origin.x, y = origin.y) { this.x = x; this.y = y; this.angle = Math.PI * 2 * Math.random(); if (longPressed == true) { this.multiplier = randBetween(14 + multiplier, 15 + multiplier); } else { this.multiplier = randBetween(6, 12); } this.vx = (this.multiplier + Math.random() * 0.5) * Math.cos(this.angle); this.vy = (this.multiplier + Math.random() * 0.5) * Math.sin(this.angle); this.r = randBetween(8, 12) + 3 * Math.random(); this.color = colours[Math.floor(Math.random() * colours.length)]; } update() { this.x += this.vx - normal.x; this.y += this.vy - normal.y; normal.x = -2 / window.innerWidth * Math.sin(this.angle); normal.y = -2 / window.innerHeight * Math.cos(this.angle); this.r -= 0.3; this.vx *= 0.9; this.vy *= 0.9; } } function pushBalls(count = 1, x = origin.x, y = origin.y) { for (let i = 0; i < count; i++) { balls.push(new Ball(x, y)); } } function randBetween(min, max) { return Math.floor(Math.random() * max) + min; } function loop() { ctx.fillStyle = "rgba(255, 255, 255, 0)"; ctx.clearRect(0, 0, canvas.width, canvas.height); for (let i = 0; i < balls.length; i++) { let b = balls[i]; if (b.r < 0) continue; ctx.fillStyle = b.color; ctx.beginPath(); ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2, false); ctx.fill(); b.update(); } if (longPressed == true) { multiplier += 0.2; } else if (!longPressed && multiplier >= 0) { multiplier -= 0.4; } removeBall(); requestAnimationFrame(loop); } function removeBall() { for (let i = 0; i < balls.length; i++) { let b = balls[i]; if (b.x + b.r < 0 || b.x - b.r > width || b.y + b.r < 0 || b.y - b.r > height || b.r < 0) { balls.splice(i, 1); } } } } clickEffect();</script>',
        ],
    ];
    $selected = $_POST['plugin_enable'] ?? [];
    $header_code = "<!-- 自动生成的功能插件代码，请勿手动编辑 -->\n";
    foreach ($selected as $key) {
        if (isset($plugin_map[$key])) {
            $header_code .= "<!-- {$key}-plugin-start -->\n" . $plugin_map[$key]['code'] . "\n<!-- {$key}-plugin-end -->\n";
        }
    }
    file_put_contents($header_file, $header_code);
    $enabled_plugins = array_flip($selected);
    $msg = '插件配置已保存，已自动写入header_include.php！';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插件管理</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { background: #f3f6fa; }
        .plugins-container {
            max-width: 1000px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .plugins-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .plugins-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .plugins-content {
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
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
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
        .plugins-table {
            margin-top: 30px;
            overflow-x: auto;
        }
        .plugins-table table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .plugins-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        .plugins-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .plugins-table tr:hover {
            background: #f8f9fa;
        }
        .icon-cell {
            text-align: center;
            font-size: 24px;
        }
        .url-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .delete-btn {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .delete-btn:hover {
            color: #c82333;
        }
        .status-cell {
            text-align: center;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.hidden {
            background: #f8d7da;
            color: #721c24;
        }
        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .toggle-btn {
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            font-size: 14px;
        }
        .toggle-btn.hide {
            color: #ffc107;
        }
        .toggle-btn.show {
            color: #28a745;
        }
        .toggle-btn:hover {
            opacity: 0.8;
        }
        .hidden-row {
            opacity: 0.6;
            background: #f8f9fa;
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
    <div class="plugins-container">
        <div class="plugins-header">
            <h1>🧩 插件管理</h1>
        </div>
        <div class="plugins-content">
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
            <?php if(isset($edit_plugin)): ?>
            <form method="post">
                <input type="hidden" name="edit_id" value="<?= $edit_plugin['id'] ?>">
                <div class="form-group">
                    <label>插件名称</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($edit_plugin['name']) ?>">
                </div>
                <div class="form-group">
                    <label>插件描述</label>
                    <input type="text" name="desc" value="<?= htmlspecialchars($edit_plugin['description']) ?>">
                </div>
                <div class="form-group">
                    <label>插件图标</label>
                    <input type="text" name="icon" value="<?= htmlspecialchars($edit_plugin['icon']) ?>">
                    <small>支持emoji表情或图片URL，如：🧩 🛠️ 📦 或 https://example.com/icon.png</small>
                </div>
                <div class="form-group">
                    <label>插件链接</label>
                    <input type="text" name="url" required value="<?= htmlspecialchars($edit_plugin['url']) ?>">
                </div>
                <button type="submit" class="submit-btn">💾 保存修改</button>
                <div style="margin-top:10px;">
                    <a href="plugin_manage.php" class="submit-btn" style="background:#ccc;color:#333;">取消</a>
                </div>
            </form>
            <?php else: ?>
            <form method="post">
                <input type="hidden" name="add" value="1">
                <div class="form-group">
                    <label>插件名称</label>
                    <input type="text" name="name" required placeholder="请输入插件名称">
                </div>
                <div class="form-group">
                    <label>插件描述</label>
                    <input type="text" name="desc" placeholder="请输入插件描述">
                </div>
                <div class="form-group">
                    <label>插件图标</label>
                    <input type="text" name="icon" placeholder="🧩" value="🧩">
                    <small>支持emoji表情或图片URL，如：🧩 🛠️ 📦 或 https://example.com/icon.png</small>
                </div>
                <div class="form-group">
                    <label>插件链接</label>
                    <input type="text" name="url" required placeholder="请输入插件链接URL">
                </div>
                <button type="submit" class="submit-btn">➕ 添加插件</button>
            </form>
            <?php endif; ?>
            <h2>插件列表</h2>
            <div class="plugins-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>图标</th>
                            <th>名称</th>
                            <th>描述</th>
                            <th>URL</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $plugins->fetch_assoc()): ?>
                        <?php $status = isset($row['status']) ? $row['status'] : 1; ?>
                        <tr class="<?= $status ? '' : 'hidden-row' ?>">
                            <td><?= $row['id'] ?></td>
                            <td class="icon-cell">
                                <?php 
                                $icon = $row['icon'] ?? '🧩';
                                if (filter_var($icon, FILTER_VALIDATE_URL)) {
                                    echo '<img src="' . htmlspecialchars($icon) . '" alt="图标" style="width: 24px; height: 24px; object-fit: contain; background: #fff; border-radius: 4px; display: block; margin: 0 auto;">';
                                } else {
                                    echo htmlspecialchars($icon);
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="url-cell"><?= htmlspecialchars($row['url']) ?></td>
                            <td class="status-cell">
                                <span class="status-badge <?= $status ? 'active' : 'hidden' ?>">
                                    <?= $status ? '✅ 显示' : '❌ 隐藏' ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <a href="?toggle=<?= $row['id'] ?>" class="toggle-btn <?= $status ? 'hide' : 'show' ?>">
                                    <?= $status ? '👁️ 隐藏' : '👁️ 显示' ?>
                                </a>
                                <a href="?edit=<?= $row['id'] ?>" class="toggle-btn" style="color:#007bff;">✏️ 编辑</a>
                                <a href="?del=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('确定删除？')">🗑️ 删除</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="back-link">
            <a href="dashboard.php">← 返回后台首页</a>
        </div>
        <div style="margin-top:48px;">
            <h2 style="color:#667eea;font-size:22px;margin-bottom:18px;">功能插件代码块</h2>
            <form method="post">
            <div style="display:flex;flex-wrap:wrap;gap:24px;">
                <?php
                $plugin_blocks = [
                    [
                        'key'   => 'turnstile',
                        'title' => 'Cloudflare Turnstile 验证',
                        'desc'  => '为页面添加Cloudflare人机验证，防止恶意访问。',
                        'code'  => '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>',
                    ],
                    [
                        'key'   => 'bingbg',
                        'title' => 'Bing每日壁纸背景',
                        'desc'  => '自动设置页面背景为Bing每日壁纸。',
                        'code'  => '<style>body{background-image:url(https://bing.img.run/rand_uhd.php);background-size:cover;background-attachment:fixed;}</style>',
                    ],
                    [
                        'key'   => 'sakura',
                        'title' => '粉色樱花雪特效',
                        'desc'  => '为页面添加粉色樱花雪花飘落特效。',
                        'code'  => '<script src="https://player.xfyun.club/js/yinghua.js"></script>',
                    ],
                    [
                        'key'   => 'music',
                        'title' => '音乐播放器',
                        'desc'  => '为页面添加悬浮音乐播放器。',
                        'code'  => '<div id="xf-MusicPlayer" data-cdnName="https://player.xfyun.club/js"  data-themeColor="xf-orange" data-fadeOutAutoplay data-memory="1" data-random="true"></div>\n<script src="https://player.xfyun.club/js/xf-MusicPlayer/js/xf-MusicPlayer.min.js"></script>',
                    ],
                    [
                        'key'   => 'clickfx',
                        'title' => '鼠标点击特效',
                        'desc'  => '为页面添加五彩粒子点击特效，提升交互体验。',
                        'code'  => '<script>function clickEffect() { let balls = []; let longPressed = false; let longPress; let multiplier = 0; let width, height; let origin; let normal; let ctx; const colours = [\"#F73859\", \"#14FFEC\", \"#00E0FF\", \"#FF99FE\", \"#FAF15D\"]; const canvas = document.createElement(\"canvas\"); document.body.appendChild(canvas); canvas.setAttribute(\"style\", \"width: 100%; height: 100%; top: 0; left: 0; z-index: 99999; position: fixed; pointer-events: none;\"); const pointer = document.createElement(\"span\"); pointer.classList.add(\"pointer\"); document.body.appendChild(pointer); if (canvas.getContext && window.addEventListener) { ctx = canvas.getContext(\"2d\"); updateSize(); window.addEventListener(\"resize\", updateSize, false); loop(); window.addEventListener(\"mousedown\", function(e) { pushBalls(randBetween(10, 20), e.clientX, e.clientY); document.body.classList.add(\"is-pressed\"); longPress = setTimeout(function(){ document.body.classList.add(\"is-longpress\"); longPressed = true; }, 500); }, false); window.addEventListener(\"mouseup\", function(e) { clearInterval(longPress); if (longPressed == true) { document.body.classList.remove(\"is-longpress\"); pushBalls(randBetween(50 + Math.ceil(multiplier), 100 + Math.ceil(multiplier)), e.clientX, e.clientY); longPressed = false; } document.body.classList.remove(\"is-pressed\"); }, false); window.addEventListener(\"mousemove\", function(e) { let x = e.clientX; let y = e.clientY; pointer.style.top = y + \"px\"; pointer.style.left = x + \"px\"; }, false); } else { console.log(\"canvas or addEventListener is unsupported!\"); } function updateSize() { canvas.width = window.innerWidth * 2; canvas.height = window.innerHeight * 2; canvas.style.width = window.innerWidth + 'px'; canvas.style.height = window.innerHeight + 'px'; ctx.scale(2, 2); width = (canvas.width = window.innerWidth); height = (canvas.height = window.innerHeight); origin = { x: width / 2, y: height / 2 }; normal = { x: width / 2, y: height / 2 }; } class Ball { constructor(x = origin.x, y = origin.y) { this.x = x; this.y = y; this.angle = Math.PI * 2 * Math.random(); if (longPressed == true) { this.multiplier = randBetween(14 + multiplier, 15 + multiplier); } else { this.multiplier = randBetween(6, 12); } this.vx = (this.multiplier + Math.random() * 0.5) * Math.cos(this.angle); this.vy = (this.multiplier + Math.random() * 0.5) * Math.sin(this.angle); this.r = randBetween(8, 12) + 3 * Math.random(); this.color = colours[Math.floor(Math.random() * colours.length)]; } update() { this.x += this.vx - normal.x; this.y += this.vy - normal.y; normal.x = -2 / window.innerWidth * Math.sin(this.angle); normal.y = -2 / window.innerHeight * Math.cos(this.angle); this.r -= 0.3; this.vx *= 0.9; this.vy *= 0.9; } } function pushBalls(count = 1, x = origin.x, y = origin.y) { for (let i = 0; i < count; i++) { balls.push(new Ball(x, y)); } } function randBetween(min, max) { return Math.floor(Math.random() * max) + min; } function loop() { ctx.fillStyle = \"rgba(255, 255, 255, 0)\"; ctx.clearRect(0, 0, canvas.width, canvas.height); for (let i = 0; i < balls.length; i++) { let b = balls[i]; if (b.r < 0) continue; ctx.fillStyle = b.color; ctx.beginPath(); ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2, false); ctx.fill(); b.update(); } if (longPressed == true) { multiplier += 0.2; } else if (!longPressed && multiplier >= 0) { multiplier -= 0.4; } removeBall(); requestAnimationFrame(loop); } function removeBall() { for (let i = 0; i < balls.length; i++) { let b = balls[i]; if (b.x + b.r < 0 || b.x - b.r > width || b.y + b.r < 0 || b.y - b.r > height || b.r < 0) { balls.splice(i, 1); } } } } clickEffect();</script>',
                    ],
                ];
                foreach($plugin_blocks as $block): ?>
                <div style="background:#f8f9fa;border-radius:12px;box-shadow:0 2px 8px rgba(102,126,234,0.08);padding:24px 22px;min-width:320px;max-width:380px;flex:1 1 320px;display:flex;flex-direction:column;gap:12px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:16px;font-weight:600;">
                        <input type="checkbox" name="plugin_enable[]" value="<?= $block['key'] ?>" <?= isset($enabled_plugins[$block['key']]) ? 'checked' : '' ?>>
                        <span style="color:#764ba2;">✨ <?= htmlspecialchars($block['title']) ?></span>
                    </label>
                    <div style="color:#666;font-size:15px;"> <?= htmlspecialchars($block['desc']) ?> </div>
                    <pre style="background:#fff;border-radius:8px;padding:12px 10px;font-size:14px;overflow-x:auto;"><?= htmlspecialchars($block['code']) ?></pre>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" name="save_plugin_header" class="submit-btn" style="margin:32px auto 0 auto;max-width:260px;display:block;">💾 保存到全站 header</button>
            </form>
        </div>
    </div>
</body>
</html> 