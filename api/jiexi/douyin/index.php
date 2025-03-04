<?php
/*
名称：抖音无水印解析脚本
源码作者：赵彤刚
测试环境：PHP 8.4
源码版本：v2.7.0
开源协议：Apache 2.0
最后更新：2025年1月6日
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
// 伪造请求头
$uavalue = "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36";
$headers = [
    "Content-Type: application/x-www-form-urlencoded",
    "User-Agent: " . $uavalue,
    "Accept-language: zh-CN,zh;q=0.9,de;q=0.8,ug;q=0.7",
    "Referer: https://www.douyin.com/?is_from_mobile_home=1&recommend=1"
];
// 获取POST输入
$url = urldecode($_POST['url']);
// 判断是否为数字
if (is_numeric($url)) {
    $video_id = $url;
} else {
    preg_match('/https?:\/\/[^\s]+/', $url, $video_url);
    $video_url = $video_url[0];
    $redirected_url = get_redirected_url($video_url);
    preg_match('/(\d+)/', $redirected_url, $matches);
    $video_id = $matches[1];
}
// 获取数据
$adder = "https://www.iesdouyin.com/share/video/" . $video_id . "/";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => $headers,
    ]
]);
$response = file_get_contents($adder, false, $context);
preg_match('/_ROUTER_DATA\s*=\s*(\{.*?\});/', $response, $matches);
$data = $matches[1];
// 解析JSON数据
$jsonData = json_decode($data, true);
// 筛选信息
$itemList = $jsonData['loaderData']['video_(id)/page']['videoInfoRes']['item_list'][0];
$nickname = $itemList['author'];
$video = $itemList['video']['play_addr']['uri'];
$images = $itemList['images'] ?? null;
// 构造输出
$outData = [
    'code' => empty($nickname) ? 0 : 1,
    'msg' => empty($nickname) ? '解析失败！' : '解析成功！️',
    'name' => $nickname['nickname'],
    'title' => $itemList['desc'],
    'aweme_id' => $itemList['aweme_id'],
    'video' => $video !== null ? (strpos($video, 'mp3') === false ? 'https://www.douyin.com/aweme/v1/play/?video_id=' . $video : $video) : null,
    'cover' => $itemList['video']['cover']['url_list'][0],
    'images' => array_map(function ($image) {
        return $image['url_list'];
    }, is_array($images) ? $images : []),
    'type' => empty($images) ? '视频' : '图集'
];
// 输出
echo json_encode($outData);
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
            'follow_location' => 1,
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
