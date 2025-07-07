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
$section_msg = '';
$section_list = [];
$res = $conn->query("SELECT * FROM sections ORDER BY id ASC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $section_list[$row['id']] = $row['name'];
    }
}

// 添加工具
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $icon = $_POST['icon'] ?? '🛠️';
    $url = $_POST['url'] ?? '';
    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : null;
    
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
        
        $stmt = $conn->prepare('INSERT INTO tools (name, description, icon, url, status, section_id) VALUES (?, ?, ?, ?, 1, ?)');
        if ($stmt) {
            $stmt->bind_param('ssssi', $name, $desc, $icon, $url, $section_id);
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
    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : null;
    if ($name && $url) {
        $stmt = $conn->prepare('UPDATE tools SET name=?, description=?, icon=?, url=?, section_id=? WHERE id=?');
        if ($stmt) {
            $stmt->bind_param('ssssii', $name, $desc, $icon, $url, $section_id, $edit_id);
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

// 分区编辑和删除逻辑
if (isset($_POST['edit_section_id']) && isset($_POST['edit_section_name'])) {
    $edit_section_id = intval($_POST['edit_section_id']);
    $edit_section_name = trim($_POST['edit_section_name']);
    if ($edit_section_id && $edit_section_name) {
        $stmt = $conn->prepare('UPDATE sections SET name=? WHERE id=?');
        $stmt->bind_param('si', $edit_section_name, $edit_section_id);
        $stmt->execute();
        $stmt->close();
        exit('ok');
    }
    exit('error');
}
if (isset($_POST['del_section_id'])) {
    $del_section_id = intval($_POST['del_section_id']);
    if ($del_section_id) {
        // 删除分区前，将该分区下工具section_id设为NULL
        $conn->query('UPDATE tools SET section_id=NULL WHERE section_id=' . $del_section_id);
        $conn->query('DELETE FROM sections WHERE id=' . $del_section_id);
        exit('ok');
    }
    exit('error');
}

// 添加分区
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $section_name = trim($_POST['section_name'] ?? '');
    if ($section_name) {
        // 检查分区名是否已存在
        $check = $conn->prepare('SELECT id FROM sections WHERE name=?');
        $check->bind_param('s', $section_name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $section_msg = '分区已存在！';
        } else {
            $stmt = $conn->prepare('INSERT INTO sections (name) VALUES (?)');
            if ($stmt) {
                $stmt->bind_param('s', $section_name);
                $result = $stmt->execute();
                if ($result) {
                    $section_msg = '分区添加成功！';
                } else {
                    $section_msg = '分区添加失败：' . $stmt->error;
                }
                $stmt->close();
            } else {
                $section_msg = 'SQL预处理失败：' . $conn->error;
            }
        }
        $check->close();
    } else {
        $section_msg = '分区名称不能为空';
    }
    // 添加分区后重定向，避免刷新重复提交
    header('Location: tools.php?section_msg=' . urlencode($section_msg));
    exit;
}

// 页面顶部显示分区添加消息
if (isset($_GET['section_msg'])) {
    $section_msg = $_GET['section_msg'];
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
            font-weight: 700;
            color: #667eea;
            font-size: 16px;
            letter-spacing: 1px;
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
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            background: #f8f9fc;
            color: #333;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
            outline: none;
            appearance: none;
            box-shadow: 0 2px 8px rgba(102,126,234,0.06);
        }
        .form-group select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12);
            background: #fff;
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
            margin-top: 0;
            overflow-x: auto;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 12px rgba(102,126,234,0.08);
        }
        .tools-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 0 0 16px 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(102,126,234,0.06);
        }
        .tools-table th {
            background: #f3f6fa;
            padding: 16px 10px;
            text-align: left;
            font-weight: 700;
            color: #333;
            border-bottom: 2px solid #e9ecef;
            font-size: 15px;
            letter-spacing: 1px;
        }
        .tools-table td {
            padding: 14px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            font-size: 15px;
        }
        .tools-table tr:hover {
            background: #f5f7fb;
            transition: background 0.2s;
        }
        .tools-table tr:last-child td {
            border-bottom: none;
        }
        .tools-table tr.empty-row td {
            background: linear-gradient(90deg, #f3f6fa 0%, #e9ecef 100%);
            color: #aaa;
            font-size: 17px;
            text-align: center;
            border-radius: 0 0 16px 16px;
            padding: 32px 0;
            font-weight: 500;
            letter-spacing: 1px;
        }
        .icon-cell {
            text-align: center;
            font-size: 26px;
        }
        .url-cell {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .delete-btn, .toggle-btn, .action-cell a {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            margin-right: 2px;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .delete-btn {
            color: #fff;
            background: #e74c3c;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .toggle-btn.hide {
            color: #fff;
            background: #f1c40f;
        }
        .toggle-btn.hide:hover {
            background: #f39c12;
        }
        .toggle-btn.show {
            color: #fff;
            background: #27ae60;
        }
        .toggle-btn.show:hover {
            background: #219150;
        }
        .toggle-btn {
            color: #fff;
            background: #3498db;
        }
        .toggle-btn:hover {
            background: #217dbb;
        }
        .action-cell a {
            margin-right: 4px;
        }
        .status-cell {
            text-align: center;
        }
        .status-badge {
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 1px 4px rgba(102,126,234,0.08);
        }
        .status-badge.active {
            background: #eafaf1;
            color: #27ae60;
            border: 1px solid #b7eacb;
        }
        .status-badge.hidden {
            background: #fbeaea;
            color: #e74c3c;
            border: 1px solid #f5b7b7;
        }
        .hidden-row {
            opacity: 0.7;
            background: #f8f9fa;
        }
        .tools-table th:first-child, .tools-table td:first-child {
            border-radius: 0 0 0 12px;
        }
        .tools-table th:last-child, .tools-table td:last-child {
            border-radius: 0 0 12px 0;
        }
        .tools-table img {
            box-shadow: 0 1px 4px rgba(102,126,234,0.08);
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
            color: #fff;
            font-size: 22px;
            margin-bottom: 20px;
            padding: 14px 28px;
            border-radius: 10px 10px 0 0;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 8px rgba(102,126,234,0.08);
            font-weight: 600;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title::before {
            content: '📂';
            font-size: 22px;
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
        .section-manage-box {
            background: #f3f6fa;
            padding: 18px 24px 10px 24px;
            border-radius: 14px;
            margin-bottom: 28px;
            box-shadow: 0 2px 12px rgba(102,126,234,0.08);
        }
        .section-manage-box form {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .section-manage-box input[type="text"] {
            height: 44px;
            font-size: 16px;
            border-radius: 8px;
            padding: 0 16px;
            border: 1.5px solid #ccc;
            background: #fff;
            box-shadow: 0 1px 4px rgba(102,126,234,0.04);
            transition: border-color 0.2s;
        }
        .section-manage-box input[type="text"]:focus {
            border-color: #667eea;
        }
        .section-manage-box .submit-btn {
            height: 44px;
            font-size: 16px;
            padding: 0 28px;
            border-radius: 8px;
            margin: 0;
        }
        .section-manage-title {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-manage-title::before {
            content: '🗂️';
            font-size: 20px;
        }
        .section-badge {
            display: inline-block;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 16px;
            padding: 6px 18px;
            font-size: 15px;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 6px;
            box-shadow: 0 1px 4px rgba(102,126,234,0.08);
            letter-spacing: 1px;
        }
        .section-badge .edit-section, .section-badge .del-section {
            margin-left: 8px;
            padding: 2px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            background: #3498db;
            text-decoration: none;
            transition: background 0.2s;
        }
        .section-badge .edit-section:hover {
            background: #27ae60;
        }
        .section-badge .del-section {
            background: #e74c3c;
        }
        .section-badge .del-section:hover {
            background: #c0392b;
        }
        .no-section-tip {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 32px 0 18px 0;
            padding: 18px 32px;
            background: linear-gradient(90deg, #f3f6fa 0%, #e9ecef 100%);
            color: #888;
            font-size: 18px;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(102,126,234,0.06);
            font-weight: 600;
            letter-spacing: 1px;
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
                <div class="form-group">
                    <label>所属分区</label>
                    <select name="section_id">
                        <option value="">无分区</option>
                        <?php foreach($section_list as $sid=>$sname): ?>
                            <option value="<?= $sid ?>" <?= (isset($edit_tool['section_id']) && $edit_tool['section_id']==$sid)?'selected':'' ?>><?= htmlspecialchars($sname) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                <div class="form-group">
                    <label>所属分区</label>
                    <select name="section_id">
                        <option value="">无分区</option>
                        <?php foreach($section_list as $sid=>$sname): ?>
                            <option value="<?= $sid ?>"><?= htmlspecialchars($sname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">➕ 添加工具</button>
            </form>
            <?php endif; ?>
            <h2>工具列表</h2>
            <?php
            // 先将所有工具按分区分组
            $tools->data_seek(0); // 重置指针
            $tools_by_section = [];
            $tools_no_section = [];
            while($row = $tools->fetch_assoc()) {
                if (isset($row['section_id']) && $row['section_id'] && isset($section_list[$row['section_id']])) {
                    $tools_by_section[$row['section_id']][] = $row;
                } else {
                    $tools_no_section[] = $row;
                }
            }
            ?>
            <?php foreach($section_list as $sid=>$sname): ?>
                <div style="margin-bottom:36px;">
                    <div class="section-title"><?= htmlspecialchars($sname) ?></div>
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
                                <?php if(isset($tools_by_section[$sid])): foreach($tools_by_section[$sid] as $row): ?>
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
                                <?php endforeach; else: ?>
                                <tr class="empty-row"><td colspan="7">暂无工具</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php // 无分区工具单独显示 ?>
            <div style="margin-bottom:36px;">
                <div class="section-title">无分区</div>
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
                            <?php if($tools_no_section): foreach($tools_no_section as $row): ?>
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
                            <?php endforeach; else: ?>
                            <tr class="empty-row"><td colspan="7">暂无工具</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="background:#f8f9fa;padding:18px 24px;border-radius:10px;margin-bottom:24px;">
                <div class="section-manage-box">
                    <div class="section-manage-title">当前分区：</div>
                    <form method="post" style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                        <input type="text" name="section_name" placeholder="新分区名称" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;">
                        <button type="submit" name="add_section" class="submit-btn" style="width:auto;padding:8px 18px;">➕ 添加分区</button>
                        <?php if($section_msg): ?><span style="color:#667eea;font-weight:500;"> <?= htmlspecialchars($section_msg) ?> </span><?php endif; ?>
                    </form>
                    <div style="margin-top:10px;color:#666;">
                        <?php if($section_list): foreach($section_list as $sid=>$sname): ?>
                            <span class="section-badge">
                                <span class="section-name" data-id="<?= $sid ?>"><?= htmlspecialchars($sname) ?></span>
                                <a href="#" class="edit-section" data-id="<?= $sid ?>">编辑</a>
                                <a href="#" class="del-section" data-id="<?= $sid ?>">删除</a>
                            </span>
                        <?php endforeach; else: ?>
                            <div class="no-section-tip"><span>📂 暂无分区</span></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="back-link">
            <a href="dashboard.php">← 返回后台首页</a>
        </div>
    </div>
    <?php if($section_msg): ?>
        <div class="message success" id="section-msg-auto-hide">
            ✅ <?= htmlspecialchars($section_msg) ?>
        </div>
        <script>
        setTimeout(function(){
            window.location.href = 'tools.php';
        }, 1000);
        </script>
    <?php endif; ?>
    <script>
    document.querySelectorAll('.edit-section').forEach(function(btn){
        btn.onclick = function(e){
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var nameSpan = document.querySelector('.section-name[data-id="'+id+'"]');
            var oldName = nameSpan.innerText;
            var newName = prompt('请输入新分区名称', oldName);
            if(newName && newName!==oldName){
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
                xhr.onload = function(){ if(xhr.responseText==='ok') location.reload(); else alert('修改失败'); };
                xhr.send('edit_section_id='+id+'&edit_section_name='+encodeURIComponent(newName));
            }
        };
    });
    document.querySelectorAll('.del-section').forEach(function(btn){
        btn.onclick = function(e){
            e.preventDefault();
            var id = this.getAttribute('data-id');
            if(confirm('确定要删除该分区吗？该分区下的工具不会被删除，但会变为"无分区"。')){
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
                xhr.onload = function(){ if(xhr.responseText==='ok') location.reload(); else alert('删除失败'); };
                xhr.send('del_section_id='+id);
            }
        };
    });
    </script>
</body>
</html> 