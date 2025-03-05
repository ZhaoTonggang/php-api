<?php
/*
名称：快手无水印解析脚本
源码作者：赵彤刚
测试环境：PHP 8.4
源码版本：v1.4.1
开源协议：Apache 2.0
最后更新：2024年11月6日
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
$url = urldecode($_POST['url']);
preg_match('/^https:\/\/www\.kuaishou\.com\/f\/[A-Za-z0-9_-]+$/', $url, $video_url);
$video_url = $video_url[0];
// 伪造请求头
$uavalue = "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36";
$headers = [
    "Content-Type: application/x-www-form-urlencoded",
    "User-Agent: " . $uavalue,
    "Accept-language: zh-CN,zh;q=0.9,de;q=0.8,ug;q=0.7",
    "Referer: " . $video_url
];
// 重定向方法
function get_redirected_url($url)
{
    // 赋予全局变量
    global $headers, $uavalue;
    // 环境配置
    ini_set("user_agent", $uavalue);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            // 启用重定向
            'follow_location' => true,
            // 最大重定向次数
            'max_redirects' => 100,
            'header' => $headers,
        ],
    ]);
    $response = file_get_contents($url, false, $context);
    foreach ($http_response_header as $header) {
        if (strpos($header, 'location:') === 0) {
            return trim(substr($header, 9));
        }
    }
    return $url;
}
// 获取POST输入
$redirected_url = get_redirected_url($video_url);
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => $headers,
    ]
]);
// 请求并筛选数据
$response = file_get_contents($redirected_url, false, $context);
preg_match('/window\.INIT_STATE\s*=\s\{*(.*?)\}<\/script>/s', $response, $matches);
$data = $matches[1];
preg_match('/"userName":"(.*?)"/s', $data, $userName);
preg_match('/"caption":"(.*?)"/s', $data, $caption);
preg_match('/"mainMvUrls":(.*?)]/s', $data, $mainMvUrls);
preg_match('/"coverUrls":(.*?)]/s', $data, $coverUrls);
preg_match('/"webpCoverUrls":(.*?)]/s', $data, $webpCoverUrls);
preg_match('/"audioUrls":(.*?)]/s', $data, $audioUrls);
// 构造输出
$outData = [
    "user_name" => $userName[1],
    "caption" => $caption[1],
    "mainMvUrls" => json_decode($mainMvUrls[1] . ']'),
    "coverUrls" => json_decode($coverUrls[1] . ']'),
    "webpCoverUrls" => json_decode($webpCoverUrls[1] . ']'),
    "audioUrls" => json_decode($audioUrls[1] . ']')
];
// 输出
echo json_encode($outData);
exit;
