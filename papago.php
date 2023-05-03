<?php
    // システムプロパティ取得
    $system_properties = parse_ini_file('messages/system.properties');
    $domain = $system_properties['domain'];
    $host = $_SERVER['HTTP_REFERER'];
    $host_url = parse_url($host)['host'];

    // 自ドメインでない場合は処理を終了
    if (!stristr($host_url, $domain)) {
        exit();
    }

    $client_id = $_GET['client_id'];
    $client_secret = $_GET['client_secret'];
    $source = $_GET['source'];
    $target = $_GET['target'];
    $text = urlencode($_GET['text']);

    // 必要なパラメータがない場合は処理を終了
    if (!($client_id && $client_secret && $source && $target && $text)) {
        exit();
    }

    $postvars = "source=" . $source . "&target=" . $target . "&text=". $text;
    $url = $system_properties['papago_api_url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $postvars);
    $headers = array();
    $headers[] = "X-Naver-Client-Id: ".$client_id;
    $headers[] = "X-Naver-Client-Secret: ".$client_secret;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec ($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    if($status_code == 200) {
        echo $response;
    } else {
        echo "Error 내용:".$response;
    }


?>