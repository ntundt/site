<?php
header('Access-Control-Allow-Origin: https://milpro.ml');
require_once('sdk.php');
$sdk = new SDK();
$messages = $sdk->DBGet('chat','user, time, message');
$d = $messages;
$c = count($messages['user']);
$countOfIterations = 0;
while($c == count($d['user'])) {
    usleep(1000000);
    $d = $sdk->DBGet('chat', 'user, time, message');
    $countOfIterations++;
    if($countOfIterations == 20) {
        http_response_code(524);
    }
}
$tm = date('j-n-y G:i', $d['time'][$c]);
echo "{\"response\":{\"user\":\"{$d['user'][$c]}\",\"time\":\"{$tm}\",\"message\":\"{$d['message'][$c]}\"}}";
?>