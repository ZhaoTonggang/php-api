<?php
$msg = urldecode($_REQUEST['msg']);      //获取视频链接
 
if (is_numeric($msg)) {
    $video_id = $msg;
} else {
    preg_match('/https?:\/\/[^\s]+/', $msg, $video_url);
    $video_url = $video_url[0];
 
    $redirected_url = get_redirected_url($video_url);
    preg_match('/(\d+)/', $redirected_url, $matches);
    $video_id = $matches[1];
    // echo $video_id;
}
 
function get_redirected_url($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $redirected_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return $redirected_url;
}
 
 
 
$headers = [
    'User-Agent: Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Mobile Safari/537.36',
    'Referer: https://www.douyin.com/?is_from_mobile_home=1&recommend=1'
];
 
 
$url = "https://www.iesdouyin.com/share/video/$video_id/";
 
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 
$response = curl_exec($ch);
curl_close($ch);
 
 
preg_match('/_ROUTER_DATA\s*=\s*(\{.*?\});/', $response, $matches);
$data = $matches[1];
 
// 解析 JSON 数据
$jsonData = json_decode($data, true);
 
// 获取视频信息
$itemList = $jsonData['loaderData']['video_(id)/page']['videoInfoRes']['item_list'][0];
$nickname = $itemList['author']['nickname'];
$title = $itemList['desc'];
$awemeId = $itemList['aweme_id'];
$video = $itemList['video']['play_addr']['uri'];
$videoUrl = $video !== null ? (strpos($video, 'mp3') === false ? 'https://www.douyin.com/aweme/v1/play/?video_id=' . $video : $video) : null;
$cover = $itemList['video']['cover']['url_list'][0];
$images = $itemList['images'] ?? null;
 
$output = [
    'msg' =>empty($nickname)?'解析失败！':'解析成功！️',
    'name' => $nickname,
    'title' => $title,
    // 'aweme_id' => $awemeId,
    'video' => $videoUrl,
    'cover' => $cover,
    'images' => array_map(function($image) {
        return $image['url_list'][0];
    }, is_array($images) ? $images : []),
    'type' =>empty($images)?'视频':'图集',
    'tips' => '' 
];
 
header('Content-Type: application/json');
echo json_encode($output,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
 
?>