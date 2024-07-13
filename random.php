<?php

// API名称和客户端IP
$API_name = 'qiqi ACG API';
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
$start_time = microtime(true); // 开始时间记录
$redirect = isset($_GET['redirect']) && $_GET['redirect'] == 302;

// 直接从$_GET中获取参数，无需手动解析
$type = isset($_GET['type']) ? $_GET['type'] : 'webp';
$return_json = isset($_GET['return']) && $_GET['return'] === 'json';

// 设置MIME类型
if ($return_json) {
    header('Content-Type: application/json');
} elseif ($type === 'webp') {
    header('Content-Type: image/webp');
} else {
    // 假设其他类型的图片默认为JPEG
    header('Content-Type: image/jpeg');
}

// 检查目录是否存在且为目录
$imageDirectory = ($type === 'webp') ? 'img-webp/' : 'img/';
if (!is_dir($imageDirectory)) {
    // 如果目录不存在或不是目录，显示错误信息
    die(json_encode(["error" => "图片目录不存在"], JSON_UNESCAPED_SLASHES));
}

// 获取所有图片的数组
$img_array = glob($imageDirectory . "*.{webp,jpg,jpeg,png}", GLOB_BRACE);

// 检查数组是否为空，即目录中是否有图片
if (empty($img_array)) {
    // 如果没有图片，显示错误信息
    die(json_encode(["error" => "没有找到图片"], JSON_UNESCAPED_SLASHES));
}

// 从数组中随机选择一个图片路径
$img_path = $img_array[array_rand($img_array)];

// 安全性检查：确保路径位于预期目录下，防止路径遍历攻击
if (!preg_match('/^' . preg_quote($imageDirectory, '/') . '.*\.(webp|jpg|jpeg|png)$/', $img_path)) {
    die(json_encode(["error" => "图片路径不安全"], JSON_UNESCAPED_SLASHES));
}

// 构建基础URL
$base_url = "https://api.hybgzs.com/" . $imageDirectory;

// 根据return参数决定返回方式
if ($return_json) {
    // 构建完整的图片URL
    $full_image_url = $base_url . substr($img_path, strlen($imageDirectory));
    
    // 计算处理过程所用时长，单位：秒
    $process_time = microtime(true) - $start_time;
    
    // 返回标准化的JSON数据
    echo json_encode([
        'API_name' => $API_name, 
        'imgurl' => $full_image_url,
        'client_ip' => $client_ip,
        'process' => $process_time
    ], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

} elseif ($redirect) {
    // 构建完整的图片URL
    $full_image_url = $base_url . substr($img_path, strlen($imageDirectory));
    
    // 发送HTTP 302重定向到图片链接
    header("Location: " . $full_image_url, true, 302);
    exit; // 重定向后结束脚本执行

} else {
    // 直接读取并输出图片内容
    readfile($img_path);
}
?>