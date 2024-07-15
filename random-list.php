<?php

// API名称和客户端IP
$API_name = 'qiqi ACG API';
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']; // 兼容未设置HTTP_X_FORWARDED_FOR的情况
$start_time = microtime(true); // 开始时间记录

// 直接从$_GET中获取参数，无需手动解析
$type = isset($_GET['type']) ? $_GET['type'] : 'webp';
$return_json = isset($_GET['return']) && $_GET['return'] === 'json';

// MIME类型映射表
$mimeTypes = [
    'webp' => 'image/webp',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
];

// 设置MIME类型
if ($return_json) {
    header('Content-Type: application/json');
} else {
    // 根据$type设置正确的MIME类型
    $type = strtolower($type); // 确保$type是小写的
    $mimeType = isset($mimeTypes[$type]) ? $mimeTypes[$type] : 'image/jpeg'; // 默认为JPEG
    header('Content-Type: ' . $mimeType);
}

// 读取图片链接的文件名
$filename = ($type === 'webp') ? 'img-webp.txt' : 'img.txt';

// 检查文件是否存在
if (!file_exists($filename)) {
    // 如果文件不存在，显示错误信息
    die(json_encode(["error" => "图片链接文件不存在"], JSON_UNESCAPED_SLASHES));
}

// 读取文件内容
$file_content = file_get_contents($filename);

// 将文件内容分割成数组
$img_links = explode(PHP_EOL, $file_content);

// 过滤空链接
$img_links = array_filter($img_links, function($link) {
    return trim($link) !== '';
});

// 检查数组是否为空
if (empty($img_links)) {
    // 如果没有图片链接，显示错误信息
    die(json_encode(["error" => "没有找到图片链接"], JSON_UNESCAPED_SLASHES));
}

// 从数组中随机选择一个图片链接
$img_link = $img_links[array_rand($img_links)];

// 使用curl进行流式传输
function streamRemoteImage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // .txt中储存的链接域名与网站使用的域名不相同时设为false以绕过SSL验证
    curl_exec($ch);
    curl_close($ch);
}

if (!$return_json) {
    streamRemoteImage($img_link);
}

// 根据return参数决定返回方式
if ($return_json) {
    // 构建完整的图片URL
    $full_image_url = $img_link;
    
    // 使用getimagesize获取图片尺寸
    list($width, $height) = getimagesize($img_path);
    
    // 计算处理过程所用时长，单位：秒
    $process_time = microtime(true) - $start_time;
    
    // 返回标准化的JSON数据，包括图片的宽度和高度
    echo json_encode([
        'API_name' => $API_name, 
        'imgurl' => $full_image_url,
        'width' => $width,
        'height' => $height,
        'client_ip' => $client_ip,
        'process' => $process_time
    ], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
} else {
    // 使用file_get_contents读取远程图片并输出
    $img_data = file_get_contents($img_link);
    if ($img_data === false) {
        die(json_encode(["error" => "无法读取图片"], JSON_UNESCAPED_SLASHES));
    }
    echo $img_data;
}
?>