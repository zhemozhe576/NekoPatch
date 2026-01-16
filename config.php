<?php
// config.php - 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sh_updater');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('SITE_URL', 'http://您的域名');

// 创建数据库连接
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            die("数据库连接失败: " . $db->connect_error);
        }
        $db->set_charset("utf8mb4");
    }
    return $db;
}

// 初始化数据库
function initDatabase() {
    $db = getDB();
    
    $db->query("CREATE TABLE IF NOT EXISTS programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_key VARCHAR(100) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        current_version VARCHAR(50) DEFAULT '1.0.0',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->query("CREATE TABLE IF NOT EXISTS versions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_id INT NOT NULL,
        version VARCHAR(50) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        md5_hash VARCHAR(32) NOT NULL,
        upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        download_count INT DEFAULT 0,
        is_latest BOOLEAN DEFAULT 0,
        FOREIGN KEY (program_id) REFERENCES programs(id)
    )");
    
    // 创建默认程序
    $db->query("INSERT IGNORE INTO programs (program_key, name, description) 
               VALUES ('PUBG_AIMBOT', 'PUBG辅助程序', '自动瞄准、绘制、物资显示等辅助功能')");
}

// 文件大小格式化
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>