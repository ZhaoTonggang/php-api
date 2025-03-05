<?php
/*
名称：微视无水印解析脚本
源码作者：赵彤刚
测试环境：PHP 8.4
源码版本：v1.2.5
开源协议：Apache 2.0
最后更新：2024年8月6日
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
preg_match('/^https:\/\/video\.weishi\.qq\.com\/[a-zA-Z0-9]+$/', urldecode($_POST['url']), $video_url);
$video_url = $video_url[0];
// 伪造请求头
$uavalue = "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36";
$headers = [
    "Content-Type: text/html;charset=utf-8",
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
            'max_redirects' => 5,
            'header' => $headers,
        ],
    ]);
    $response = file_get_contents($url, false, $context);
    foreach ($http_response_header as $header) {
        if (strpos($header, 'Location:') === 0) {
            return trim(substr($header, 9));
        }
    }
    return $url;
}
// 获取最终地址并取得视频ID
preg_match('/id=([a-zA-Z0-9]+)/',get_redirected_url($video_url),$video_id);
// 请求并筛选数据
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => $headers,
    ]
]);
$response = file_get_contents('https://m.weishi.qq.com/vise/share/index.html?id='.$video_id[1], false, $context);
preg_match('/window\.Vise\.initState\s*=\s*(\{.*?\});/', $response, $matches);
$matches=json_decode($matches[1],true)['feedsList'][0];
// 构造输出
$outData = [
    "userName" => $matches['poster']['nick'],
    "avatar" => $matches['poster']['avatar'],
    "title" => $matches['feedDesc'],
    "images" => $matches['images'],
    "videoSpecUrls" => $matches['videoSpecUrls'],
    "videoUrl"=>$matches['videoUrl']
];
// 输出
echo json_encode($outData);
exit;
