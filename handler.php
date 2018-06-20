<?php 
    if(!isset($_REQUEST)) {
        return;
    }
    require_once 'sdk.php';
    $sdk = new SDK;
    $parsed = json_decode(file_get_contents('php://input'), true);
    if(strcmp($parsed['secret'], 'ezxeMOutcvauOGoz') == 0) {
        switch($parsed['type']) {
            case 'confirmation':
                echo '2d44e01d';
                break;
            case 'wall_post_new':
                $text = $parsed['object']['text'];
                $text = explode('/', $text);
                $title = $text[1];
                $text_fin = '';
                $preview = explode(' ', $text[2]);
                $preview_fin = '';
                $gallery = '';
                for($i = 0; $i < 30; $i++) {
                    $preview_fin .= (isset($preview[$i])?$preview[$i].' ':'');
                }
                for ($i = 2; $i < count($text); $i++) {
                    $text_fin .= $text[$i] . (isset($text[$i+1])?'/':'');
                }
                for($i = 1; $i < count($parsed['object']['attachments']); $i++) {
                    if(strcmp($parsed['object']['attachments'][$i]['type'], 'photo') == 0) {
                        $gallery .= $parsed['object']['attachments'][$i]['photo']['sizes'][count($parsed['object']['attachments'][0]['photo']['sizes'])-1]['url'] . ';';
                    }
                }
                $sdk->item_publish(
                    array(
                        'title' => $title,
                        'all_text' => $text_fin,
                        'author' => 'CallbackAPI',
                        'views' => 0,
                        'pic' => $parsed['object']['attachments'][0]['photo']['sizes'][count($parsed['object']['attachments'][0]['photo']['sizes'])-1]['url'],
                        'time' => time(),
                        'preview_text' => $preview_fin,
                        'gallery' => $gallery
                        )
                    );
                echo 'ok';
                break;
            case 'message_new':
                echo 'ok';
                if(strcmp($parsed['object']['body'], 'Начать') == 0) {
                    $subs = json_decode(file_get_contents('subscribers.json'), true);
                    $subs[] = $parsed['object']['user_id'];
                    file_put_contents('subscribers.json', json_encode($subs));
                    $sdk->requestByGet('https://api.vk.com/method/messages.send?v=5.0&user_id='.$parsed['object']['user_id'].'&message=Теперь+Вы+подписаны+на+уведомления.&access_token='.$sdk->access_token);
                }
                
                break;
            default:
                echo 'ok';
                break;
        }
    } else {
        return;
    }
?>