<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUBG辅助 - 云更新管理</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #1a1a2e; color: #fff; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        header { text-align: center; padding: 30px 0; }
        header h1 { color: #4cc9f0; font-size: 2.2em; margin-bottom: 10px; }
        .section { background: #16213e; border-radius: 10px; padding: 25px; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #8be9fd; font-size: 14px; }
        input, textarea, select { 
            width: 100%; padding: 10px; background: #0f3460; 
            border: 1px solid #4cc9f0; border-radius: 5px; color: #fff; 
            font-size: 14px;
        }
        textarea { height: 80px; resize: vertical; }
        .file-input { 
            padding: 25px; background: #0f3460; border: 2px dashed #4cc9f0; 
            text-align: center; cursor: pointer; margin-top: 5px;
        }
        .file-input:hover { background: #1a4066; }
        .btn { 
            background: linear-gradient(45deg, #4cc9f0, #4361ee); color: white; 
            border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; 
            font-weight: bold; font-size: 14px; display: inline-block;
        }
        .btn:hover { opacity: 0.9; }
        .btn-delete { background: #f72585; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 14px; }
        .success { background: #4ade80; color: #000; }
        .error { background: #f72585; color: #fff; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #2d3748; }
        th { background: #0f3460; font-weight: bold; }
        .version-badge { 
            background: #4361ee; padding: 3px 8px; border-radius: 10px; 
            font-size: 11px; display: inline-block;
        }
        .latest-badge { background: #4ade80; color: #000; }
        .json-view { 
            background: #0f3460; padding: 15px; border-radius: 5px; 
            font-family: monospace; font-size: 12px; overflow-x: auto;
            margin-top: 15px;
        }
        .api-url { 
            background: #0f3460; padding: 10px; border-radius: 5px; 
            font-family: monospace; font-size: 13px; word-break: break-all;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎮 PUBG辅助 - 云更新管理</h1>
            <p>上传新版本SH文件，客户端自动检测更新</p>
        </header>
        
        <div class="section">
            <h2>📤 上传新版本</h2>
            <div id="status" class="status" style="display: none;"></div>
            
            <form id="uploadForm">
                <div class="form-group">
                    <label>程序</label>
                    <select name="program" id="programSelect">
                        <option value="PUBG_AIMBOT">PUBG辅助程序</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>版本号</label>
                    <input type="text" name="version" value="<?php echo date('Y.m.d.Hi'); ?>" required>
                    <small style="color: #aaa;">格式: 年.月.日.时分 或 1.2.3</small>
                </div>
                
                <div class="form-group">
                    <label>更新说明</label>
                    <textarea name="description" placeholder="描述本次更新的内容..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>上传SH文件</label>
                    <div class="file-input" onclick="document.getElementById('file').click()">
                        📁 点击选择或拖放 PUBG辅助.sh 文件
                        <div style="font-size: 12px; color: #aaa; margin-top: 5px;">最大50MB，仅支持.sh文件</div>
                    </div>
                    <input type="file" id="file" name="file" accept=".sh" style="display:none;" required>
                    <div id="fileName" style="margin-top: 10px; color: #8be9fd;"></div>
                </div>
                
                <button type="submit" class="btn">🚀 发布新版本</button>
            </form>
        </div>
        
        <div class="section">
            <h2>📋 版本列表</h2>
            <div id="loading">加载中...</div>
            <div id="versionList"></div>
        </div>
        
        <div class="section">
            <h2>🔗 API接口</h2>
            <div class="api-url">
                <strong>检查更新:</strong><br>
                GET <?php echo SITE_URL; ?>/api.php?check=1&program=PUBG_AIMBOT&current=1.0.0
            </div>
            
            <div class="json-view" id="apiResponse">
                // API响应示例将显示在这里
            </div>
            
            <button class="btn btn-small" onclick="testAPI()">测试API</button>
        </div>
    </div>
    
    <script>
        let versionsData = [];
        
        // 文件选择处理
        document.getElementById('file').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                document.getElementById('fileName').innerHTML = 
                    `✅ 已选择: ${file.name} (${formatSize(file.size)})`;
            }
        });
        
        // 文件拖放
        const dropArea = document.querySelector('.file-input');
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.style.background = '#1a4066';
        });
        
        dropArea.addEventListener('dragleave', () => {
            dropArea.style.background = '#0f3460';
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.style.background = '#0f3460';
            
            const file = e.dataTransfer.files[0];
            if (file) {
                document.getElementById('file').files = e.dataTransfer.files;
                document.getElementById('fileName').innerHTML = 
                    `✅ 已选择: ${file.name} (${formatSize(file.size)})`;
            }
        });
        
        // 表单提交
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('program', document.querySelector('[name="program"]').value);
            formData.append('version', document.querySelector('[name="version"]').value);
            formData.append('description', document.querySelector('[name="description"]').value);
            formData.append('file', document.getElementById('file').files[0]);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                showStatus(result.success ? 'success' : 'error', 
                          result.message || result.error);
                
                if (result.success) {
                    // 清空表单
                    document.getElementById('uploadForm').reset();
                    document.getElementById('fileName').innerHTML = '';
                    // 重新加载列表
                    loadVersions();
                }
            } catch (error) {
                showStatus('error', '上传失败: ' + error.message);
            }
        });
        
        // 加载版本列表
        async function loadVersions() {
            try {
                const response = await fetch('api.php?list=1');
                const data = await response.json();
                
                if (data.success && data.versions) {
                    versionsData = data.versions;
                    renderVersionList();
                }
            } catch (error) {
                console.error('加载失败:', error);
            }
        }
        
        // 渲染版本列表
        function renderVersionList() {
            const container = document.getElementById('versionList');
            
            if (versionsData.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #aaa;">暂无版本</p>';
                return;
            }
            
            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>版本号</th>
                            <th>文件名</th>
                            <th>大小</th>
                            <th>上传时间</th>
                            <th>下载次数</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            versionsData.forEach(version => {
                html += `
                    <tr>
                        <td>
                            <span class="version-badge ${version.is_latest ? 'latest-badge' : ''}">
                                ${version.version} ${version.is_latest ? '最新' : ''}
                            </span>
                        </td>
                        <td>${version.file_name}</td>
                        <td>${formatSize(version.file_size)}</td>
                        <td>${new Date(version.upload_time).toLocaleString()}</td>
                        <td>${version.download_count}</td>
                        <td>
                            <button class="btn btn-small" onclick="downloadVersion(${version.id})">下载</button>
                            ${version.is_latest ? '' : 
                                `<button class="btn btn-small" onclick="setLatest(${version.id})" style="margin-left:5px;">设为最新</button>`}
                            <button class="btn btn-small btn-delete" onclick="deleteVersion(${version.id})" style="margin-left:5px;">删除</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
            document.getElementById('loading').style.display = 'none';
        }
        
        // 操作函数
        async function downloadVersion(id) {
            window.open(`api.php?download=${id}`, '_blank');
        }
        
        async function setLatest(id) {
            if (confirm('设为最新版本后，客户端将收到更新通知。确定吗？')) {
                try {
                    const response = await fetch(`api.php?set_latest=${id}`);
                    const result = await response.json();
                    
                    showStatus(result.success ? 'success' : 'error', result.message || result.error);
                    if (result.success) loadVersions();
                } catch (error) {
                    showStatus('error', '操作失败');
                }
            }
        }
        
        async function deleteVersion(id) {
            if (confirm('确定要删除这个版本吗？此操作不可恢复！')) {
                try {
                    const response = await fetch(`api.php?delete=${id}`);
                    const result = await response.json();
                    
                    showStatus(result.success ? 'success' : 'error', result.message || result.error);
                    if (result.success) loadVersions();
                } catch (error) {
                    showStatus('error', '删除失败');
                }
            }
        }
        
        // 测试API
        async function testAPI() {
            const apiUrl = `api.php?check=1&program=PUBG_AIMBOT&current=1.0.0`;
            
            try {
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                document.getElementById('apiResponse').innerHTML = 
                    `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                document.getElementById('apiResponse').innerHTML = 
                    `<pre style="color: #f72585;">请求失败: ${error.message}</pre>`;
            }
        }
        
        // 工具函数
        function showStatus(type, message) {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status ${type}`;
            status.style.display = 'block';
            
            setTimeout(() => {
                status.style.display = 'none';
            }, 5000);
        }
        
        function formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 页面加载
        document.addEventListener('DOMContentLoaded', () => {
            loadVersions();
            testAPI();
        });
    </script>
</body>
</html>