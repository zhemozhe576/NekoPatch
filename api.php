<?php
// api.php - API接口
require_once 'config.php';
initDatabase();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$db = getDB();
$response = ['success' => false];

try {
    // 1. 检查更新
    if (isset($_GET['check'])) {
        $programKey = $_GET['program'] ?? 'PUBG_AIMBOT';
        $currentVersion = $_GET['current'] ?? '1.0.0';
        
        // 获取最新版本
        $stmt = $db->prepare("
            SELECT p.*, v.version as latest_version, v.file_name, v.file_path, 
                   v.file_size, v.md5_hash, v.id as version_id
            FROM programs p 
            LEFT JOIN versions v ON v.is_latest = 1 AND v.program_id = p.id
            WHERE p.program_key = ?
        ");
        $stmt->bind_param("s", $programKey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($program = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['program'] = [
                'name' => $program['name'],
                'current_version' => $program['current_version'],
                'latest_version' => $program['latest_version'],
                'has_update' => version_compare($program['latest_version'], $currentVersion) > 0,
                'file_name' => $program['file_name'],
                'file_size' => $program['file_size'],
                'md5_hash' => $program['md5_hash'],
                'download_url' => SITE_URL . '/' . $program['file_path']
            ];
        } else {
            $response['error'] = '程序不存在';
        }
    }
    
    // 2. 下载文件
    elseif (isset($_GET['download'])) {
        $id = intval($_GET['id']);
        
        $stmt = $db->prepare("SELECT file_path, file_name FROM versions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($version = $result->fetch_assoc()) {
            $filePath = $version['file_path'];
            
            if (file_exists($filePath)) {
                // 更新下载计数
                $db->query("UPDATE versions SET download_count = download_count + 1 WHERE id = $id");
                
                // 发送文件
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            }
        }
        
        http_response_code(404);
        $response['error'] = '文件不存在';
    }
    
    // 3. 上传文件（管理员用）
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        $programKey = $_POST['program'] ?? 'PUBG_AIMBOT';
        $version = $_POST['version'] ?? date('Y.m.d');
        $description = $_POST['description'] ?? '';
        
        $file = $_FILES['file'];
        
        // 验证
        if ($file['error'] !== UPLOAD_OK) {
            throw new Exception('文件上传失败');
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('文件太大');
        }
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sh') {
            throw new Exception('只支持.sh文件');
        }
        
        // 生成文件名
        $fileName = 'PUBG_' . time() . '_' . bin2hex(random_bytes(8)) . '.sh';
        $filePath = UPLOAD_DIR . $fileName;
        
        // 创建上传目录
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('文件保存失败');
        }
        
        // 计算MD5
        $md5Hash = md5_file($filePath);
        
        // 获取程序ID
        $stmt = $db->prepare("SELECT id FROM programs WHERE program_key = ?");
        $stmt->bind_param("s", $programKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $program = $result->fetch_assoc();
        
        if (!$program) {
            throw new Exception('程序不存在');
        }
        
        // 重置其他版本为非最新
        $db->query("UPDATE versions SET is_latest = 0 WHERE program_id = " . $program['id']);
        
        // 插入新版本
        $stmt = $db->prepare("
            INSERT INTO versions (program_id, version, file_name, file_path, file_size, md5_hash, is_latest) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("isssis", 
            $program['id'],
            $version,
            $file['name'],
            $filePath,
            $file['size'],
            $md5Hash
        );
        
        if (!$stmt->execute()) {
            throw new Exception('数据库保存失败');
        }
        
        // 更新程序版本
        $db->query("UPDATE programs SET current_version = '$version' WHERE id = " . $program['id']);
        
        $response['success'] = true;
        $response['message'] = '上传成功';
        $response['version_id'] = $stmt->insert_id;
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>