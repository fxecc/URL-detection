<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'];

    // 添加默认的http协议前缀
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = 'http://' . $url;
    }

    // 使用cURL请求URL并获取网站标题
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($handle);
    $info = curl_getinfo($handle);
    $title = '';
    if ($info['http_code'] === 200) {
        preg_match('/<title>(.*?)<\/title>/', $result, $matches);
        if (isset($matches[1])) {
            $title = $matches[1];
        }
    }
    curl_close($handle);

    // 如果http协议检测失败，则尝试https协议
    if ($info['http_code'] !== 200) {
        $httpsUrl = str_replace('http://', 'https://', $url);

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $httpsUrl);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($handle);
        $info = curl_getinfo($handle);
        if ($info['http_code'] === 200) {
            preg_match('/<title>(.*?)<\/title>/', $result, $matches);
            if (isset($matches[1])) {
                $title = $matches[1];
            }
        }
        curl_close($handle);
    }

    // 返回检测结果
    $response = [
        'success' => true,
        'url' => $url,
        'title' => $title,
        'status' => $info['http_code'] === 200 ? '正常' : '失败',
    ];
    echo json_encode($response);
    exit;
}
?>