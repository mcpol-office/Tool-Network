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

// 添加工具
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $icon = $_POST['icon'] ?? '🛠️';
    $url = $_POST['url'] ?? '';
    
    if ($name && $url) {
        // 检查icon字段是否存在，如果不存在则先添加
        $check_icon = $conn->query("SHOW COLUMNS FROM tools LIKE 'icon'");
        if ($check_icon->num_rows == 0) {
            $conn->query("ALTER TABLE tools ADD COLUMN icon VARCHAR(255) DEFAULT '🛠️' AFTER description");
        } else {
            // 如果字段存在但长度不够，则修改字段长度
            $alter_icon = $conn->query("ALTER TABLE tools MODIFY COLUMN icon VARCHAR(255) DEFAULT '🛠️'");
        }
        
        // 检查status字段是否存在，如果不存在则添加
        $check_status = $conn->query("SHOW COLUMNS FROM tools LIKE 'status'");
        if ($check_status->num_rows == 0) {
            $conn->query("ALTER TABLE tools ADD COLUMN status TINYINT(1) DEFAULT 1 AFTER url");
        }
        
        $stmt = $conn->prepare('INSERT INTO tools (name, description, icon, url, status) VALUES (?, ?, ?, ?, 1)');
        if ($stmt) {
            $stmt->bind_param('ssss', $name, $desc, $icon, $url);
            $result = $stmt->execute();
            if ($result) {
                $msg = '工具添加成功！';
            } else {
                $error = '工具添加失败：' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'SQL预处理失败：' . $conn->error;
        }
    } else {
        $error = '请填写工具名称和链接';
    }
}
// 删除工具
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $conn->query('DELETE FROM tools WHERE id=' . $id);
    // 重新整理ID
    $conn->query('ALTER TABLE tools AUTO_INCREMENT = 1');
    $conn->query('SET @rank = 0');
    $conn->query('UPDATE tools SET id = (@rank := @rank + 1) ORDER BY id');
    $conn->query('ALTER TABLE tools AUTO_INCREMENT = (SELECT COUNT(*) + 1 FROM tools)');
}

// 切换显示/隐藏状态
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query('UPDATE tools SET status = NOT status WHERE id = ' . $id);
}

// 编辑工具
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_tool = $conn->query('SELECT * FROM tools WHERE id=' . $edit_id)->fetch_assoc();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $icon = $_POST['icon'] ?? '🛠️';
    $url = $_POST['url'] ?? '';
    if ($name && $url) {
        $stmt = $conn->prepare('UPDATE tools SET name=?, description=?, icon=?, url=? WHERE id=?');
        if ($stmt) {
            $stmt->bind_param('ssssi', $name, $desc, $icon, $url, $edit_id);
            $result = $stmt->execute();
            if ($result) {
                $msg = '工具编辑成功！';
                header('Location: tools.php');
                exit;
            } else {
                $error = '工具编辑失败：' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'SQL预处理失败：' . $conn->error;
        }
    } else {
        $error = '请填写工具名称和链接';
    }
}

$tools = $conn->query('SELECT * FROM tools');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工具管理</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .tools-container {
            max-width: 1000px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .tools-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .tools-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .tools-content {
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
        .tools-table {
            margin-top: 30px;
            overflow-x: auto;
        }
        .tools-table table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tools-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        .tools-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .tools-table tr:hover {
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
        .section-title {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
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
    </style>
</head>
<body>
    <div class="tools-container">
        <div class="tools-header">
            <h1>🛠️ 工具管理</h1>
        </div>
        
        <div class="tools-content">
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
            
            <?php if(isset($edit_tool)): ?>
            <form method="post">
                <input type="hidden" name="edit_id" value="<?= $edit_tool['id'] ?>">
                <div class="form-group">
                    <label>工具名称</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($edit_tool['name']) ?>">
                </div>
                <div class="form-group">
                    <label>工具描述</label>
                    <input type="text" name="desc" value="<?= htmlspecialchars($edit_tool['description']) ?>">
                </div>
                <div class="form-group">
                    <label>工具图标</label>
                    <input type="text" name="icon" value="<?= htmlspecialchars($edit_tool['icon']) ?>">
                    <small>支持emoji表情或图片URL，如：🛠️ 📊 🎨 🔧 💻 或 https://example.com/icon.png</small>
                </div>
                <div class="form-group">
                    <label>工具链接</label>
                    <input type="text" name="url" required value="<?= htmlspecialchars($edit_tool['url']) ?>">
                </div>
                <button type="submit" class="submit-btn">💾 保存修改</button>
                <div style="margin-top:10px;">
                    <a href="tools.php" class="submit-btn" style="background:#ccc;color:#333;">取消</a>
                </div>
            </form>
            <?php else: ?>
            <form method="post">
                <input type="hidden" name="add" value="1">
                <div class="form-group">
                    <label>工具名称</label>
                    <input type="text" name="name" required placeholder="请输入工具名称">
                </div>
                <div class="form-group">
                    <label>工具描述</label>
                    <input type="text" name="desc" placeholder="请输入工具描述">
                </div>
                <div class="form-group">
                    <label>工具图标</label>
                    <input type="text" name="icon" placeholder="🛠️" value="🛠️">
                    <small>支持emoji表情或图片URL，如：🛠️ 📊 🎨 🔧 💻 或 https://example.com/icon.png</small>
                </div>
                <div class="form-group">
                    <label>工具链接</label>
                    <input type="text" name="url" required placeholder="请输入工具链接URL">
                </div>
                <button type="submit" class="submit-btn">➕ 添加工具</button>
            </form>
            <?php endif; ?>
            <h2>工具列表</h2>
            <div class="tools-table">
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
                        <?php while($row = $tools->fetch_assoc()): ?>
                        <?php if(isset($edit_tool) && $edit_tool['id'] == $row['id']) continue; ?>
                        <?php $status = isset($row['status']) ? $row['status'] : 1; ?>
                        <tr class="<?= $status ? '' : 'hidden-row' ?>">
                            <td><?= $row['id'] ?></td>
                            <td class="icon-cell">
                                <?php 
                                $icon = $row['icon'] ?? '🛠️';
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
    </div>
</body>
</html> 