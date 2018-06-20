<?php
class SDK {
    var $MySQL_login = ""; //your database logn
    var $MySQL_password = ""; //password 
    var $MySQL_default_database = ""; //database name
    var $access_token = ''; //VK access_token
    
    function VKNotify($aboutWhat) {
        $toWho = '';
        $toWho_data = json_decode(file_get_contents('subscribers.json'), true);
        for($i = 0; $i < count($i); $i++){
            $toWho .= $toWho_data[$i] . (isset($toWho_data[$i+1])?',':'');
        }
        $this->requestByPost('https://api.vk.com/method/messages.send', 'v=5.0&access_token='.$this->access_token.'&user_ids='.$toWho.'&message='.$aboutWhat);
    }
    function item_publish($item_desc) {
        $number = file_get_contents('news.global');
		$newsFile = fopen('news/'.($number - 1).'.json', 'w');
		fwrite($newsFile, json_encode(
			array(
				'title' => $item_desc['title'],
				'author' => $item_desc['author'],
				'pic' => $item_desc['pic'],
				'time' => $item_desc['time'],
				'views' => $item_desc['views'],
				'preview_text' => $item_desc['preview_text'],
				'all_text' => $item_desc['all_text'],
				'gallery' => $item_desc['gallery']
				)
			));
		fclose($newsFile);
		file_put_contents('news.global', $number - 1);
    }
    function DBAppend($table, $data) {
        $db = new mysqli("localhost", $this->MySQL_login, $this->MySQL_password, $this->MySQL_default_database);
        return $db->query("INSERT INTO {$table} VALUES ( {$data} )");
    }
    function DBGet($table, $whatToGet = '*', $somethingElse = '') {
        $db = new mysqli("localhost", $this->MySQL_login, $this->MySQL_password, $this->MySQL_default_database);
        $res = $db->query("SELECT {$whatToGet} FROM `{$table}` {$somethingElse}");
        if($res === false) {
            echo '<b>Oops!</b> Its look like somebody overused our chat and our database died. Try again in a hour. MySQL error: '.$db->error;
        }
        if(strcmp($whatToGet, '*') != 0) {
            $whatToGet = explode(', ', $whatToGet);
        }
        $out = array();
        while($arr = $res->fetch_assoc()) {
            for($i = 0; $i < count($whatToGet); $i++) {
                $out[$whatToGet[$i]][] = $arr[$whatToGet[$i]];
            }
        }
        return $out;
    }
    function wikify($data) {
	    $wiki = array(
		    '[[' => '<b>',
		    ']]' => '</b>',
		    '{{' => '<h1>',
		    '}}' => '</h1>',
		    '@-' => '<del>',
		    '-@' => '</del>',
		    '"(' => '<blockquote>',
		    ')"' => '</blockquote>',
		    '<<' => '<p style="text-align: center;">',
		    '>>' => '</p>'
		);
		$sc = array_keys($wiki);
		for($i = 0; $i < count($wiki); $i++) {
			$data = str_replace($sc[$i], $wiki[$sc[$i]], $data);
		}
		return $data;
	}
	function echoLBUData($cookie) {
	    echo '<form action="index.php" method="get">
			Логин: <br>
			<input class="login" type="text" name="login" value="'.$cookie['login'].'"></input><br>
			Пароль: <br>
			<input class="login" type="password" name="password"></input><br>
			<input class="login" type="submit" value="Войти"></input>
		</form>
		<i style="color: #f00">Неверный логин или пароль!</i><br>
		<a class="center" href="dontpanic.html">Забыли пароль?</a><br>
		<a class="center" href="registerForm.php">Регистрация</a>';
	}
	function echoLBLData($cookie) {
	    echo '<img class="profilePic" src="'.$cookie['prof_pic'].'">
			Приветствуем, '.$cookie['login'].'!<br>
			<a href="index.php?act=unlogin">Выйти</a><br>' .
			(isset($cookie['admin'])?'<a href="index.php?activity=editor">Опубликовать новость</a>':'').'
			<a href="index.php?activity=settings">Настройки</a>';
	}
	function validLogin($login, $password) {
		$resp = $this->DBGet('users', 'password, admin, prof_pic', "WHERE login='{$login}'");
		if(strcmp($password, $resp['password'][0]) == 0) {
			return array('logged' => true, 'admin' => $resp['admin'][0]==1,'prof_pic' => $resp['prof_pic']);
		} else {
			return array('logged' => false);
		}
	}
	function logged($cookie) {
	    if(isset($cookie['logged']) and isset($cookie['login']) and isset($cookie['pass'])) {
	        if(strcmp($cookie['logged'], 'yes') == 0 and $this->validLogin($cookie['login'], $cookie['pass'])['logged']) {
	            return true;
	        } else {
	            return false;
	        }
	    } elseif (isset($cookie['login']) and isset($cookie['pass'])) {
	        if($this->validLogin($cookie['login'], $cookie['pass'])['logged']) {
	            return true;
	        } else {
	            return false;
	        }
	    }
	}
	function echoLBData() {
	    echo '<form action="index.php" method="get">
			Логин: <br>
			<input class="login" type="text" name="login"></input><br>
			Пароль: <br>
			<input class="login" type="password" name="password"></input><br>
			<input class="login" type="submit" value="Войти"></input>
		</form>
		<a class="center" href="dontpanic.html">Забыли пароль?</a><br>
		<a class="center" href="registerForm.php">Регистрация</a>';
	}
	function isAdmin($login) {
		$resp = $this->DBGet('users', 'admin', "WHERE login='{$login}'");
		if($resp['admin'][0] == 1) {
			return true;
		} else {
			return false;
		}
	}
	function generateToken($length) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$numChars = strlen($chars);
		$string = '';
		for ($i = 0; $i < $length; $i++) {
			$string .= substr($chars, rand(1, $numChars) - 1, 1);
		}
		return $string;
	}
	function sendToFile($which, $what) {
		$writing = fopen($which, 'w');
		fwrite($writing, $what);
		fclose($writing);
	}
	function logMess($which, $what) {
		$fp = fopen($which,"a");
		fwrite($fp, '
	' . '[' . date('d m Y H:i:s') . '] ' . $what);
		fclose($fp);
	}
	function requestByGet($url) { 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_URL, $url);
		$response = curl_exec($curl);
		curl_close($curl);
		$this->logMess('requestLog.txt', $url.'
	'.$response);
		return $response;
	}
	function requestByPost($url, $text) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $text);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
	function dec2roman ($number) { 
	    $number = floor($number); 
	    if($number < 0) { 
	        $linje = "-"; 
	        $number = abs($number); 
	    } 
	    $romanNumbers = array(1000, 500, 100, 50, 10, 5, 1); 
	    $romanLettersToNumbers = array("M" => 1000, "D" => 500, "C" => 100, "L" => 50, "X" => 10, "V" => 5, "I" => 1); 
	    $romanLetters = array_keys($romanLettersToNumbers); 
	    while ($number) { 
	        for($pos = 0; $pos <= 6; $pos++) { 
	            $dividend = $number / $romanNumbers[$pos]; 
	            if($dividend >= 1) { 
	                $linje .= str_repeat($romanLetters[$pos], floor($dividend)); 
	                $number -= floor($dividend) * $romanNumbers[$pos]; 
	            } 
	        } 
	    } 
	    $numberOfChanges = 1; 
	    while($numberOfChanges) { 
	        $numberOfChanges = 0; 
	        for($start = 0; $start < strlen($linje); $start++) { 
	            $chunk = substr($linje, $start, 1); 
	            if($chunk == $oldChunk && $chunk != "M") { 
	                $appearance++; 
	            } else { 
	                $oldChunk = $chunk; 
	                $appearance = 1; 
	            } 
	            if($appearance == 4) { 
	                $firstLetter = substr($linje, $start - 4, 1); 
	                $letter = $chunk; 
	                $sum = $firstNumber + $letterNumber * 4; 
	                $pos = $this->array_search($letter, $romanLetters); 
	                if($romanLetters[$pos - 1] == $firstLetter) { 
	                    $oldString = $firstLetter . str_repeat($letter, 4); 
	                    $newString = $letter . $romanLetters[$pos - 2]; 
	                } else { 
	                    $oldString = str_repeat($letter, 4); 
	                    $newString = $letter . $romanLetters[$pos - 1]; 
	                } 
	                $numberOfChanges++; 
	                $linje = str_replace($oldString, $newString, $linje); 
	            } 
	        } 
	    } 
	    return $linje; 
	} 
	function array_search($searchString, $array) { 
	    foreach ($array as $content) { 
	        if($content == $searchString) { 
	            return $pos; 
	        } 
	        $pos++; 
	    } 
	}
}
?>