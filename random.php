<?php

// 流式传输开关
$streaming_enabled = true; // true为启用并默认使用流式传输，false则默认使用302

// API名称和客户端IP
$API_name = 'qiqi ACG API';
$client_ip = $_SERVER['REMOTE_ADDR'];
$start_time = microtime(true); // 开始时间记录
$redirect = isset($_GET['redirect']) && $_GET['redirect'] == 302;

// MIME类型映射表
$mimeTypes = [
    'webp' => 'image/webp',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'bmp' => 'image/bmp', 
];

// 验证并设置图片类型
$type = isset($_GET['type']) ? strtolower($_GET['type']) : 'webp';
$return_json = isset($_GET['return']) && $_GET['return'] === 'json';

// 设置MIME类型
if ($return_json) {
    header('Content-Type: application/json');
} else {
    $mimeType = isset($mimeTypes[$type]) ? $mimeTypes[$type] : 'image/jpeg';
    header('Content-Type: ' . $mimeType);
}

// 检查目录是否存在且为目录
$imageDirectory = ($type === 'img') ? 'img/' : 'img-webp/';
if (!is_dir($imageDirectory)) {
    die(json_encode(["error" => "图片目录不存在"], JSON_UNESCAPED_SLASHES));
}

// 获取所有图片的数组
if ($type === 'img') {
    $img_array = array_filter(scandir($imageDirectory), function($file) use ($imageDirectory) {
        return is_file($imageDirectory . $file) && !in_array(pathinfo($file, PATHINFO_EXTENSION), ['webp']); // 排除webp文件
    });
} else {
    $img_array = array_filter(scandir($imageDirectory), function($file) use ($imageDirectory) {
        return is_file($imageDirectory . $file) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['webp']);
    });
}

// 检查数组是否为空，即目录中是否有图片
if (empty($img_array)) {
    die(json_encode(["error" => "没有找到图片"], JSON_UNESCAPED_SLASHES));
}

// 从数组中随机选择一个图片路径
$random_img = $img_array[array_rand($img_array)];
$img_path = $imageDirectory . $random_img;

// 安全性检查：确保路径位于预期目录下，防止路径遍历攻击
if (!preg_match('/^' . preg_quote($imageDirectory, '/') . '.*\.(webp|jpg|jpeg|png|bmp)$/', $img_path)) { // 添加BMP文件支持
    die(json_encode(["error" => "图片路径不安全"], JSON_UNESCAPED_SLASHES));
}

// 构建基础URL，不设置此项json可能返回错误地址
function buildFullImageUrl($imageDirectory, $random_img) {
    $base_url = "https://dav-dmapi.hybgzs.com/" . $imageDirectory;
    return $base_url . $random_img;
}

// 根据return参数决定返回方式
if ($return_json) {
    $full_image_url = buildFullImageUrl($imageDirectory, $random_img);

    // 直接获取图片尺寸
    list($width, $height) = getimagesize($img_path);

    $process_time = microtime(true) - $start_time;

    echo json_encode([
        'API_name' => $API_name, 
        'imgurl' => $full_image_url,
        'width' => $width,
        'height' => $height,
        'client_ip' => $client_ip,
        'process' => $process_time
    ], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

} elseif ($redirect || !$streaming_enabled) {
    $full_image_url = buildFullImageUrl($imageDirectory, $random_img);
    header("Location: " . $full_image_url, true, 302);
    exit;

} else {
    // 再次检查路径安全性
    if (!preg_match('/^' . preg_quote($imageDirectory, '/') . '.*\.(webp|jpg|jpeg|png|bmp)$/', $img_path)) { // 添加BMP文件支持
        die(json_encode(["error" => "图片路径不安全"], JSON_UNESCAPED_SLASHES));
    }
    readfile($img_path);
}
?>