<?php
session_start();
require_once __DIR__ . '/inc/config.php';

// 检查是否已配置
if (!is_configured()) {
    header('Location: install.php');
    exit;
}

// 加载配置
$config = load_config();
$site_title = $config['site_title'] ?? '工具网';
$custom_header = $config['custom_header'] ?? '';

require_once __DIR__ . '/inc/db.php';
$conn = get_db();
$sections = [];
$res = $conn->query("SELECT * FROM sections ORDER BY id ASC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $sections[$row['id']] = $row['name'];
    }
}
$section_id = isset($_GET['section']) ? intval($_GET['section']) : 0;
if ($section_id > 0) {
    $tools = $conn->query("SELECT * FROM tools WHERE status = 1 AND section_id = $section_id ORDER BY id ASC");
} else {
    $tools = $conn->query("SELECT * FROM tools WHERE status = 1 ORDER BY id ASC");
}
if ($tools === false) {
    echo "<div style='color:red;text-align:center;'>工具数据查询失败：" . $conn->error . "</div>";
    $tools = null;
}
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title) ?></title>
    <link rel="stylesheet" href="assets/style.css">
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: transparent;
            padding: 40px 0 20px 0;
            text-align: center;
        }
        
        .site-title {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            gap: 12px;
        }
        
        .site-icon {
            font-size: 2.5rem;
        }
        
        .site-name {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .site-desc {
            color: #888;
            font-size: 1.1rem;
            margin-bottom: 24px;
        }
        
        .search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto 30px;
        }
        
        .search-box {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background: white;
        }
        
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .nav-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tool-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-decoration: none;
            color: #333;
            display: block;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .tool-icon {
            font-size: 48px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .tool-name {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .tool-desc {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            text-align: center;
        }
        
        .builtin-tools {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .builtin-tools h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .builtin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .builtin-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s ease;
            text-decoration: none;
            color: #333;
        }
        
        .builtin-card:hover {
            transform: translateY(-3px);
            background: #e9ecef;
        }
        
        .builtin-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .builtin-name {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .builtin-desc {
            color: #666;
            font-size: 12px;
        }
        
        .footer {
            text-align: center;
            color: #333;
            padding: 20px;
            opacity: 0.95;
        }
        .footer a {
            color: #333 !important;
            text-decoration: none !important;
            font-weight: 500;
        }
        
        .no-tools {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .no-tools-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-tools-text {
            color: #666;
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .add-tools-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s ease;
        }
        
        .add-tools-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .footer a.no-underline {
            color: inherit !important;
            text-decoration: none !important;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 28px;
            }
            
            .tools-grid {
                grid-template-columns: 1fr;
            }
            
            .builtin-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
    <?php if ($custom_header) echo $custom_header; ?>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="site-title">
                <span class="site-icon">🚀</span>
                <span class="site-name"><?= htmlspecialchars($site_title) ?></span>
            </div>
            <div class="site-desc">发现实用工具，提升工作效率</div>
            <div class="search-container">
                <input type="text" class="search-box" id="searchBox" placeholder="搜索工具... (Ctrl+K)">
                <div class="search-icon">🔍</div>
            </div>
        </div>
        
        <div class="section-nav" style="margin-bottom:30px;display:flex;gap:12px;flex-wrap:wrap;">
            <a href="index.php" class="section-btn" style="padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;<?= $section_id==0?'font-weight:bold;box-shadow:0 2px 8px #764ba233;':'' ?>">全部</a>
            <?php foreach($sections as $sid=>$sname): ?>
                <a href="index.php?section=<?= $sid ?>" class="section-btn" style="padding:8px 18px;border-radius:8px;background:#f8f9fa;color:#667eea;text-decoration:none;<?= $section_id==$sid?'font-weight:bold;box-shadow:0 2px 8px #667eea33;':'' ?>"><?= htmlspecialchars($sname) ?></a>
            <?php endforeach; ?>
        </div>
        
        <?php if ($tools && $tools->num_rows > 0): ?>
            <div class="tools-grid" id="toolsGrid">
                <?php while($row = $tools->fetch_assoc()): ?>
                    <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" class="tool-card" data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>" data-desc="<?= htmlspecialchars(strtolower($row['description'])) ?>">
                        <div class="tool-icon">
                            <?php 
                            $icon = $row['icon'] ?? '🛠️';
                            if (filter_var($icon, FILTER_VALIDATE_URL)) {
                                echo '<img src="' . htmlspecialchars($icon) . '" alt="图标" style="width: 48px; height: 48px; object-fit: contain; background: #fff; border-radius: 8px; display: block; margin: 0 auto;">';
                            } else {
                                echo htmlspecialchars($icon);
                            }
                            ?>
                        </div>
                        <div class="tool-name"><?= htmlspecialchars($row['name']) ?></div>
                        <div class="tool-desc"><?= htmlspecialchars($row['description']) ?></div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php elseif ($is_admin): ?>
            <div class="no-tools">
                <div class="no-tools-icon">📦</div>
                <div class="no-tools-text">暂无自定义工具</div>
                <a href="admin/login.php" class="add-tools-btn">➕ 添加工具</a>
            </div>
        <?php endif; ?>
        
        <?php
        $footer_notices = $footer_notices ?? ($config['footer_notices'] ?? []);
        $footer_separator = $footer_separator ?? ($config['footer_separator'] ?? '|');
        $first_notice = '<a href="https://github.com/mcpol-office/Tool-Network" class="no-underline" target="_blank" rel="noopener">由Tool-Network驱动</a>';
        $output = [$first_notice];
        if ($footer_notices && is_array($footer_notices) && count($footer_notices) > 0) {
            foreach ($footer_notices as $i => $item) {
                if (($item['type'] ?? 'text') === 'link' && !empty($item['url'])) {
                    $output[] = '<a href="'.htmlspecialchars($item['url']).'" target="_blank" rel="noopener">'.htmlspecialchars($item['content']).'</a>';
                } else {
                    $output[] = htmlspecialchars($item['content'] ?? '');
                }
            }
        }
        echo '<div class="footer">'.implode(' '.htmlspecialchars($footer_separator).' ', $output).'</div>';
        ?>
    </div>
    
    <script>
        // Ctrl+K 搜索功能
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('searchBox').focus();
            }
        });
        
        // 搜索功能
        const searchBox = document.getElementById('searchBox');
        const toolsGrid = document.getElementById('toolsGrid');
        
        if (searchBox && toolsGrid) {
            searchBox.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const toolCards = toolsGrid.querySelectorAll('.tool-card');
                
                toolCards.forEach(card => {
                    const name = card.getAttribute('data-name');
                    const desc = card.getAttribute('data-desc');
                    
                    if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html> 