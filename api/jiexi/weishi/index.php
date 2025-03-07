<?php
/*
名称：微视无水印解析脚本
源码作者：赵彤刚
测试环境：PHP 8.4
源码版本：v1.2.6
开源协议：Apache 2.0
最后更新：2025年3月6日
*/

// 拦截非POST请求
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: https://blog.heheda.top');
    exit;
}
// 响应头
header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("X-Powered-By: PHP/" . PHP_VERSION);
header("Content-language: zh");
// 判断是否输入
if (isset($_POST['url'])) {
    if (preg_match('/https:\/\/video\.weishi\.qq\.com\/[a-zA-Z0-9]+/', urldecode($_POST['url']), $video_url)) {
        $video_url = $video_url[0];
    } else {
        echo json_encode(['msg' => 'URL格式不正确！']);
        exit;
    }
} else {
    echo json_encode(['msg' => '没有输入URL！']);
    exit;
}
// 伪造请求头
$uavalue = "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        // 启用重定向
        'follow_location' => 1,
        // 最大重定向次数
        'max_redirects' => 5,
        'header' => [
            "Content-Type: text/html; charset=utf-8",
            "User-Agent: " . $uavalue,
            "Accept-language: zh-CN,zh;q=0.9,de;q=0.8,ug;q=0.7",
            "Referer: " . $video_url
        ]
    ]
]);
// 获取数据
function get_redirected_url($url)
{
    global $uavalue, $context;
    ini_set("user_agent", $uavalue);
    while (true) {
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            // 处理错误
            echo json_encode(['msg' => '获取数据失败！算法可能已失效，请联系管理员更新！']);
            exit;
        }
        $location = null;
        foreach ($http_response_header as $header) {
            if (stripos($header, 'location:') === 0) {
                $location = trim(substr($header, 9));
                break;
            }
        }
        if ($location === null) {
            // 没有重定向，拼接参数重新访问
            preg_match('/id=([a-zA-Z0-9]+)/', $url, $video_id);
            return file_get_contents('https://m.weishi.qq.com/vise/share/index.html?id=' . $video_id[1], false, $context);
        }
        // 更新 URL 
        $url = $location;
    }
}
// 筛选数据
preg_match('/window\.Vise\.initState\s*=\s*(\{.*?\});/', get_redirected_url($video_url), $matches);
$matches = json_decode($matches[1], true)['feedsList'][0];
// 构造输出
$outData = [
    "userName" => $matches['poster']['nick'],
    "avatar" => $matches['poster']['avatar'],
    "title" => $matches['feedDesc'],
    "images" => $matches['images'],
    "videoSpecUrls" => $matches['videoSpecUrls'],
    "videoUrl" => $matches['videoUrl']
];
// 输出
echo json_encode($outData);
exit;
