<?php

// 功能管理
$debug = false; // debug开关
$search_txt = true; // 是否在检索不到img目录时查找img.txt文件用于替代（实验性功能）

// API名称和客户端IP
$API_name = 'qiqi ACG API';
$client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
$start_time = microtime(true); // 开始时间记录

// 直接从$_GET中获取参数，无需手动解析
$type = isset($_GET['type']) ? $_GET['type'] : 'jpg';
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
    header('Content-Type: application/json; charset=utf-8');
} else {
    // 根据$type设置正确的MIME类型
    $type = strtolower($type); // 确保$type是小写的
    $mimeType = isset($mimeTypes[$type]) ? $mimeTypes[$type] : 'image/jpeg'; // 默认为JPEG
    header('Content-Type: ' . $mimeType);
}

// 检查目录是否存在且为目录
$imageDirectory = 'img/';
if (!is_dir($imageDirectory)) {
    // 如果目录不存在或不是目录，检查 img.txt 文件
    if ($search_txt) {
        $imgTxtPath = 'img.txt';
        if (file_exists($imgTxtPath)) {
            // 读取 img.txt 文件内的链接
            $img_path = trim(file_get_contents($imgTxtPath));
            if (empty($img_path)) {
                die(json_encode(["error" => "img.txt 文件内容为空"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        } else {
            die(json_encode(["error" => "图片目录不存在"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    } else {
        die(json_encode(["error" => "图片目录不存在"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
} else {
    // 根据$type获取所有图片的数组
    $img_array = glob($imageDirectory . "*." . $type);

    // 检查数组是否为空，即目录中是否有图片
    if (empty($img_array)) {
        die(json_encode(["error" => "没有找到图片"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    // 从数组中随机选择一个图片路径
    $img_path = $img_array[array_rand($img_array)];
}

// 调试模式下直接输出指定图片
if ($debug) {
    $img_path = './test.jpeg';
    if (!file_exists($img_path)) {
        die(json_encode(["error" => "调试图片不存在"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
} else {
    // 根据$type获取所有图片的数组
    $img_array = glob($imageDirectory . "*." . $type);

    // 检查数组是否为空，即目录中是否有图片
    if (empty($img_array)) {
        die(json_encode(["error" => "没有找到图片"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    // 从数组中随机选择一个图片路径
    $img_path = $img_array[array_rand($img_array)];
}

// 安全性检查：确保路径位于预期目录下，防止路径遍历攻击
if (!$debug) { // 添加条件判断，当$debug为true时跳过路径安全性检查
    if (!preg_match('/^' . preg_quote($imageDirectory, '/') . '.*\.' . preg_quote($type, '/') . '$/', $img_path)) {
        die(json_encode(["error" => "图片路径不安全"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

// 构建基础URL
$base_url = "https://dav-dmapi.hybgzs.com/" . $imageDirectory;

// 根据return参数决定返回方式
if ($return_json) {
    // 构建完整的图片URL
    $full_image_url = $base_url . substr($img_path, strlen($imageDirectory));
    
    // 使用getimagesize获取图片尺寸
    list($width, $height) = getimagesize($img_path);

    // 计算处理过程所用时长，单位：秒
    $process_time = microtime(true) - $start_time;
    
    // 根据debug模式决定返回的JSON内容
    if ($debug) {
        echo json_encode([
            'API_name' => $API_name, 
            'client_ip' => $client_ip,
            'debug' => $debug
        ], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    } else {
        // 返回标准化的JSON数据，包括图片的宽度和高度
        echo json_encode([
            'API_name' => $API_name, 
            'imgurl' => $full_image_url,
            'width' => $width,
            'height' => $height,
            'client_ip' => $client_ip,
            'process' => $process_time
        ], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }
} elseif (isset($_GET['return']) && $_GET['return'] === '302') { // 修改: 使用return参数决定是否进行302跳转
    // 构建完整的图片URL
    $full_image_url = $base_url . substr($img_path, strlen($imageDirectory));
    
    // 发送HTTP 302重定向到图片链接
    header("Location: " . $full_image_url, true, 302);
    exit; // 重定向后结束脚本执行

} else {
    // 动态转换图片格式
    $image = imagecreatefromstring(file_get_contents($img_path));

    switch ($type) {
        case 'webp':
            imagewebp($image);
            break;
        case 'png':
            imagepng($image);
            break;
        case 'jpg':
        case 'jpeg':
        default:
            imagejpeg($image);
            break;
    }

    imagedestroy($image);
}
?>