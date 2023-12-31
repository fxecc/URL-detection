<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urlList = explode("\n", $_POST['urls']);
    $urlList = array_map('trim', $urlList);
    $urlList = array_filter($urlList);

    $responses = [];

    $concurrentLimit = 5; // 控制并发请求数量

    $chunks = array_chunk($urlList, $concurrentLimit);

    foreach ($chunks as $chunk) {
        $promises = [];
        $mh = curl_multi_init();

        foreach ($chunk as $url) {
            $url = addHttpProtocol($url);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            curl_multi_add_handle($mh, $ch);

            $promises[$url] = $ch;
        }

        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        foreach ($promises as $url => $ch) {
            $result = curl_multi_getcontent($ch);
            $info = curl_getinfo($ch);

            $title = '';
            if ($info['http_code'] === 200) {
                preg_match('/<title>(.*?)<\/title>/', $result, $matches);
                if (isset($matches[1])) {
                    $title = $matches[1];
                }
            }

            $responses[] = [
                'url' => $url,
                'title' => $title,
                'status' => $info['http_code'] === 200 ? '正常' : '失败',
            ];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
    }

    echo json_encode($responses);
    exit;
}

function addHttpProtocol($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = 'http://' . $url;
    }
    return $url;
}
?>
