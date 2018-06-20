<?php
    require_once('sdk.php');
    $sdk = new SDK();
    header('Access-Control-Allow-Origin: https://milpro.ml');
    $db = new mysqli("localhost", "id5272570_hotstagemod", "hsm1983", "id5272570_main");
    date_default_timezone_set('Europe/Minsk');
    if(!isset($_GET['text'])) {
        $out = $sdk->DBGet('chat', 'user, time, message');
        for($i = 0; $i < count($out['time']); $i++) {
            $out['time'][$i] = date('j-n-y G:i', $out['time'][$i]);
        }
        echo json_encode(array('response' => $out));
    } else {
        $res = $db->query("INSERT INTO `chat` VALUES ( '{$_GET['user']}', ".time().", '{$_GET['text']}', '')");
        echo $res?'tra':'fales: '.$db->error.':::'."INSERT INTO `chat` VALUES ( `{$_GET['user']}`, ".time().", `{$_GET['text']}`, ``)";
        $sdk->VKNotify('Пользователь '.$_GET['user'].' написал в чат сообщение с текстом: '."\n".$_GET['text']);
    }
?>