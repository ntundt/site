<?php
	require_once('sdk.php');
	$sdk = new SDK;
	$conf = false;
	$checkEmail = false;
	$fileupl = false;
	$error = 100;
	$ehlo = array('Всегда Ваша,','Всегда на связи,','С уважением,','С наилучшими пожеланиями,');
	
	if(isset($_FILES['FILE']['name'])) { 
		$file_name = $_FILES['FILE']['name'];
		$filetype = substr($file_name, strlen($file_name) - 3);
		if($_FILES['FILE']['size'] != 0 and $_FILES['FILE']['size'] <= 1024000) {
			if(is_uploaded_file($_FILES['FILE']['tmp_name'])) {
				if(move_uploaded_file($_FILES['FILE']['tmp_name'], "photos/" . basename($_FILES['FILE']['name']))) {
					$fileupl = true;
					$filename = 'photos/' . basename($_FILES['FILE']['name']);
				}
			}
		}
	}
	
	if(isset($_GET['login']) and isset($_GET['password'])) {
		$resp = $sdk->validLogin($_GET['login'], $_GET['password']);
		if($resp['logged']) {
			setcookie('login', $_GET['login'], time()+604800);
			setcookie('pass', $_GET['password'], time()+604800);
			setcookie('logged', 'yes', time()+604800);
			setcookie('prof_pic', $resp['prof_pic'][0], time()+604800);
			if($resp['admin']) {
				setcookie('admin', 'yes', time()+604800);
			}
			echo "<!DOCTYPE html><head><script type='text/javascript'>window.onload = function(){document.getElementById('link').click();}</script></head><body><a href=\"index.php\" id=\"link\"></a></body>";
		} else {
			setcookie('logged', 'no', time()-604800);
			setcookie('login','',time()-604800);
			setcookie('pass','',time()-604800);
			setcookie('admin','',time()-604800);
			echo "<!DOCTYPE html><head><script type='text/javascript'>window.onload = function(){document.getElementById('link').click();}</script></head><body><a href=\"index.php?login=".$_GET['login']."&logged=no\" id=\"link\"></a></body>";
		}
	} else if(isset($_POST['act'])) {
		switch($_POST['act']) {
			case 'register':
				if(isset($_POST['login']) and isset($_POST['password']) and isset($_POST['email'])) {
					$token = $sdk->generateToken(15);
					$resp = $sdk->DBAppend('confirm', "'{$token}', '{$_POST['login']}', '{$_POST['password']}', '{$_POST['email']}'");
					if($resp) {
						$i = rand(0, count($ehlo)-1);
						$to	  = $_POST['email'];
						$subject = 'Валидация аккаунта на milpro.ml';
						$message = "Здравствуйте!\r\n\r\nВы получили это письмо, потому что Ваш Email был указан в форме регитрации на <a href=\"https://milpro.ml\">сайте проекта MilitaryProject, Inc.</a>. Если Вы действительно регистрировались и ознакомились с пользовательским соглашением, то Вы можете пройти по <a href=\"index.php?act=confirm&token={$token}\">этой ссылке</a>, чтобы активировать Ваш аккаунт и начать им пользоваться.\r\n\r\n{$ehlo[$i]}\r\n Служба валидации аккаунтов MilitaryProject, Inc.";
						$headers = 'From: noreply@milpro.tk' . "\r\n" .
							'MIME-Version: 1.0' . "\r\n" .
							'Content-type: text/html;' . "\r\n" .
							'X-Mailer: PHP/' . phpversion();
						mail($to, $subject, $message, $headers);
						$checkEmail = true;
					} else {
						$error = 102;
					}
				} else {
					$error = 101;
				}
				break;
			case 'publish':
				$number = file_get_contents('news.global');
				$newsFile = fopen('news/'.($number-1).'.json', 'w');
				fwrite($newsFile, json_encode(
					array(
						'title' => $sdk->wikify($_POST['title']),
						'author' => $_COOKIE['login'],
						'pic' => $_POST['pic'],
						'time' => time(),
						'views' => 0,
						'preview_text' => $sdk->wikify($_POST['preview_text']),
						'all_text' => $sdk->wikify($_POST['all_text']),
						'gallery' => $_POST['gallery']
						)
					));
				fclose($newsFile);
				file_put_contents('news.global', $number-1);
				$sdk->VKNotify('Пользователь '.$_COOKIE['login'].' опубликовал новость "'.$_POST['title'].'": '."\n".'https://milpro.ml/index.php?filename='.($number-1).'.json%26activity=item');
				?>
					<!DOCTYPE html>
					<head>
						<script type="text/javascript">
							window.onload = function() {
								document.getElementById("link").click();
							}
						</script>
					</head>
					<body>
						<a href="index.php" id="link"></a>
					</body>
				<?php
		}
	} else if(isset($_GET['act'])) {
		switch($_GET['act']) {
			case 'unlogin':
				setcookie('logged','',time()-604800);
				setcookie('admin','no',time()-604800);
				setcookie('login','',time()-604800);
				setcookie('pass','',time()-604800);
				?>
					<!DOCTYPE html>
					<head>
						<script type="text/javascript">
							window.onload = function() {
								document.getElementById("link").click();
							}
						</script>
					</head>
					<body>
						<a href="index.php" id="link"></a>
					</body>
				<?php
				break;
			case 'confirm':
				if(isset($_GET['token'])) {
					$data = $sdk->DBGet('confirm', 'login, password, email', "WHERE token='{$_GET['token']}'; DELETE FROM `confirm` WHERE token={$_GET['token']}");
					if(isset($data['login'][0])) {
						$resp = $sdk->DBAppend('users', "NULL, 'noname', '{$data['login'][0]}', '{$data['password'][0]}', DEFAULT, '{$data['email'][0]}', 0, 'About text'");
						if($resp) {
							$conf = true;
						} else {
							$error = 102;
						}
					} else {
						$error = 103;
					}
				} else {
					$error = 101;
				}
				break;
		}
	}
	if(isset($_GET['delete'])) {
		if($sdk->isAdmin($_COOKIE['login']) and $sdk->logged($_COOKIE)) {
			unlink('news/'.$_GET['delete']);
			?>
				<!DOCTYPE html>
				<head>
					<script type="text/javascript">
						window.onload = function() {
							document.getElementById("link").click();
						}
					</script>
				</head>
				<body>
					<a href="index.php" id="link"></a>
				</body>
			<?php
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php
			if(isset($_GET['activity'])) switch($_GET['activity']) {
				case 'editor':
					echo 'Редактор новостей';
					break;
				case 'item':
					echo 'Новость';
					break;
				case 'send':
					echo 'Загрузка файла';
					break;
				case 'settings':
					echo 'Настройки';
					break;
				default:
					echo 'Главная страница';
					break;
			}
			else echo 'Главная страница';
		?></title>
		<?= ($checkEmail?'<script>window.onload=function(){alert("Проверьте почту, которую Вы указали при регистрации");}</script>':''); ?>
		<?= ($conf?'<script>window.onload=function(){alert("Аккаунт подтверждён, Вы можете в него войти!");}</script>':''); ?>
		<?= ($fileupl?'<script>window.onload=function(){alert("Файл успешно загружен и доступен при обращении: '.$filename.'");}</script>':''); ?>
		<?= ($error!=100?'<script>window.onload=function(){alert("Произошла ошибка с кодом: '.$error.'");}</script>':''); ?>
		<meta name="theme-color" content="#424242">
		<script type="text/javascript" src="//vk.com/js/api/openapi.js?154"></script>
		<link rel="shortcut icon" href="/images/icons/favicon.ico" type="image/x-icon">
		<script type="text/javascript">
			VK.init({apiId: 6473714, onlyWidgets: true});
		</script>
		<script type="text/javascript" src="script/query.js"></script>
		<script>
			var chat_data = {};
			var chatIn = false;
			function chatExit() {
			    chatIn = !chatIn;
			    document.getElementById("longpollctrl").style.backgroundColor = (chatIn?"green":"red");
			    document.getElementById("longpollctrl").innerHTML = "[LP]";
			    SendRequest("get", "https://hotstagemod.tk/chat.php", "", function(text) {
				    if(text.status != 524) {
					    document.getElementById('chat').innerHTML = makeChatHTML(JSON.parse(text.responseText));
				    }
			    });
			    SendLongPollRequest("get", "https://hotstagemod.tk/chatLongPolling.php", "", function(text) {
				    if(text.status != 524) {
					    pushData(JSON.parse(text.responseText));
					    document.getElementById('chat').innerHTML = makeChatHTML(chat_data);
				    }
			    });
			}
			function pushData(whatToAppend) {
				chat_data.response.user.push(whatToAppend.response.user);
				chat_data.response.message.push(whatToAppend.response.message);
				chat_data.response.time.push(whatToAppend.response.time);
			}
			function makeChatHTML(parsed) {
				var out = "";
				for(var i = parsed.response.user.length - 1; i >= 0; i--) {
					out += "<div style=\"width: 100%; background-color: ";
					if(i % 2 == 0) {
						out += "#888";
					} else {
						out += "#AAA";
					}
					out += ";\"><a href=\"profile.php?user="+parsed.response.user[i]+"\">"+parsed.response.user[i]+"</a> ("+parsed.response.time[i]+"): "+parsed.response.message[i]+"</div>";
				} 
				chat_data = parsed;
				return out;
			}
			SendRequest("get", "https://hotstagemod.tk/chat.php", "", function(text) {
				if(text.status != 524) {
					document.getElementById('chat').innerHTML = makeChatHTML(JSON.parse(text.responseText));
				}
			});
			SendLongPollRequest("get", "https://hotstagemod.tk/chatLongPolling.php", "", function(text) {
				if(text.status != 524) {
					pushData(JSON.parse(text.responseText));
					document.getElementById('chat').innerHTML = makeChatHTML(chat_data);
				}
			});
			function chatSend() {
				var input = document.getElementById("chatInput");
				if(input.innerHTML != "") SendRequest("get", "https://hotstagemod.tk/chat.php", "text="+input.innerHTML+"&user=<?=(isset($_COOKIE['login'])?$_COOKIE['login']:'anonymous');?>", function() {});
				input.innerHTML = "";
			}
			window.onkeypress = function(e) {
				var evt = e || event;
				switch(evt.keyKode) {
					case 13:
						document.getElementById("chatSend").click();
				}
			}
		</script>
		<style>
            .main {
                width: 100%;
                border-radius: 10px;
            }
            a {
            	color: #fff;
            	text-decoration: none;
            }
            .newitem {
                margin-bottom: 15px;
                width: 100%;
                border-radius: 10px;
                background-color: #6d6d6d;
            }
            div.title {
                font-weight: bold;
                font-size: 125%;
                height: 125%;
                background-color: #1b1b1b;
                border-top-left-radius: 10px;
                border-top-right-radius: 10px;
            }
            span.title {
            	margin: 1em;
            }
            div.itempic {
                text-align: center;
                display: inline-block;
                float: left;
                width: 11em;
                height: 8em;
                margin: 1em;
            }
            img.itempic {
                width: 100%;
                height: 100%;
                border-radius: 10px;
            }
            .itemtext {
                display: inline-block;
                width: 25em;
                margin-top: 1em;
            	float: left;
            	color: #fff;
            }
            div b {
            	margin: 1em;
            }
            .bottom {
            	clear: left;
                vertical-align: bottom;
            	width: 40em;
            	background-color: #1b1b1b;
            	border-bottom-left-radius: 10px;
                border-bottom-right-radius: 10px;
            }
            .al_l {
                text-align: left;
            }
            .al_r {
                text-align: right;
            }
            .left_icon {
                width: 35px;
                height: 25px;
                opacity: 0.75;
                filter: alpha(opacity=75);
                background: url(/images/icons/menu_icon.png?7) no-repeat 7px -441px;
                background-position: 7px -21px;
            }
            .left_fixer, .left_fixer>span {
                display: block;
            }
            .left_row {
                color: #285473;
                border: 0;
                display: block;
                white-space: nowrap;
            }
            
            .left_label {
                height: 28px;
                line-height: 27px;
                font-size: 12.5px;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: baseline;
            }
            .profilePic {
            	width: 100%;
            	border-radius: 10px;
            }
            .login {
            	width: 12em;
            	margin: auto;
            	padding: 0.2em;
            	border: none;
            	border-bottom: 3px black solid;
            	background-color: gray;
            }
            pre {
            	text-align: center;
            }
            .register {
            	width: 100%;
            	height: 100%;
            	margin: auto;
            	margin-top: auto;
            }
            .registerForm {
            	background-color: gray;
            	margin: auto;
            	width: 20%;
            }
            .news {
            	border-bottom: solid 2px white;
            	white-space: pre-wrap;
            }
            .newsBlock {
            	width: 100%;
            	margin-top: 0;
            	border: none;
            	overflow: hidden;
            	border-radius: 10px;
            }
            .mainBlock {
            	width: 40em;
            	margin: auto;
            	border-bottom: none;	
            }	
            .loginBlock {
            	width: 12em;
            	text-align: center;
            	background-color: #6d6d6d;
            	border-radius: 10px;
            }
            .leftBlock {
                width: 12em;
                text-align: center;
                background-color: #6d6d6d;
                position: fixed;
                margin-left: 4%;
            }
            .chatBlock {
                width: 12em;
                background-color: #6d6d6d;
                border-radius: 10px;
            }
            .chat {
                background-color: #6d6d6d;
                color: #000;
                border-radius: 10px;
                width: 100%;
            }
            .user {
                color: #6d6d6d;
            }
            .headBlock {
            	text-align: center;
            	color: #fff;
                font-size: 15px;
                margin-bottom: 20px;
            }
            body {
            	background-color: #424242;
            	font-family: 'Segoe UI', sans-serif, Helvetica Neue;
            	color: #fff;
            	margin: 0;
            }
            
            a.headLink {
            	color: #fff;
            	text-decoration-color: #fff;
            	background-color: #6d6d6d;
            	border-radius: 10px;
            	padding: 5px;
            }
            a.headLink:hover {
            	color: #f00;
            	text-decoration-color: #4f90de;
            }
            a.center {
            	color: #fff;
            	text-decoration-color: #fff;
            }
            a.center:hover {
            	color: #fff;
            	text-decoration-color: #4f90de;
            }
			.leftstr, .rightstr {
				float: left;
				width: 50%; 
			}
			.rightstr {
				text-align: right;
			}
			.bl_rd_h {
				color: black;
				text-decoration-color: red;
			}
			.bl_rd_h:hover {
				color: red;
				text-decoration-color: red;
			}
			.sendButton {
				background: url(images/icons/send_button.png) no-repeat;
				background-size: cover;
				display: inline-block;
				border: none;
				width: 2em;
				height: 2em;
			}
			#chatInput {
				height: 2em;
				width: 9.5em;
				background-color: white;
				display: inline-block;
				color: black;
				overflow-y: auto;
				border-bottom-left-radius: 10px;
			}
			.item {
				white-space: pre-wrap;
				color: #fff;
				margin-top: 25px;
				margin-bottom: 25px;
			}
			.round {
				border-radius: 2em;
			}
			.r_b {
			    border-radius: 10px;
			}
			.lig {
			    background-color: #424242;
			}
			.mid {
			    background-color: #6d6d6d;
			}
			.dar {
			    background-color: #1b1b1b;
			}
			.w100 {
			    width: 100%;
			}
			.c_w {
			    color: #000;
			}
			.c_b {
			    color: #fff;
			}
			.mb15 {
			    margin-bottom: 15px;
			}
			.mt15 {
			    margin-top: 15px;
			}
			.p1 {
			    padding: 1em;
			}
			
            .gallery {
              position: relative;
              padding-top: 50%;
              -moz-user-select: none; user-select: none;
              background-color: #424242;
              border-radius: 10px;
            }
            .gallery img {
              position: absolute;
              top: 25%;
              left: 12.5%;
              max-width: 24.5%;
              max-height: 49.5%;
              -webkit-transform: translate(-50%, -50%);
              transform: translate(-50%, -50%);
              cursor: zoom-in;
              transition: .2s;
              border-radius: 10px;
            }
            .gallery img:nth-child(4n-2) {left: 37.5%;}
            .gallery img:nth-child(4n-1) {left: 62.5%;}
            .gallery img:nth-child(4n) {left: 87.5%;}
            .gallery img:nth-child(n+5) {top: 75%;}
            .gallery img:focus {
              position: absolute;
              top: 50%;
              left: 50%;
              z-index: 1;
              max-width: 100%;
              max-height: 100%;
              outline: none;
              pointer-events: none;
            }
            .gallery img:focus ~ div {
              position: absolute;
              top: 0;
              left: 0;
              right: 0;
              bottom: 0;
              cursor: zoom-out;
            }
		</style>
	</head>
	<body>
		<div style="margin-left: 4%; height: 100%; width: 12em; position: fixed; align-items: center;">
			<div style="width: 12em; text-align: center; background-color: gray; border-radius: 10px;">
				<?php
					if(!$sdk->logged($_COOKIE) and isset($_COOKIE['login'])) {
						$sdk->echoLBUData($_COOKIE);
					} elseif($sdk->logged($_COOKIE)) {
						$sdk->echoLBLData($_COOKIE);
					} else {
						$sdk->echoLBData();
					}
				?>
			</div>
			<div style="margin-top: 2em; width: 12em; background-color: gray; border-radius: 10px;">
				<p style="text-align: center; margin-bottom: 0em;">Ламповый чатик <font id="longpollctrl" style="background-color: red" onclick="chatExit()">[Upd.]</font></p>
				
				<div id="chat" class="chat" style="width: 100%; height: 20em; overflow-y: scroll;">
				
				</div>
				<?=$sdk->logged($_COOKIE)?'<div style="width: 100%;">
					<div id="chatInput" style="vertical-align: top" contentEditable></div>
					<div style="display: inline-block;width: 2em; vertical-align: top; height:2em;"><button style="vertical-align: top" id="chatSend" onclick="chatSend()" class="sendButton"></button></div>
				</div>':'<p style="text-align: center; margin-bottom: 1em;">Чтобы писать в чат войдите или зарегистрируйтесь.</p>'?>
			</div>
		</div>
		<div class="mainBlock">
			<img class="main" src="images/head.jpg">
			<div class="headBlock">
				<a href="index.php" class="headLink">Главная</a> <a href="forum.html" class="headLink">Форум</a> <a href="download.html" class="headLink">Скачать мод</a> <a href="http://place-game.com/load/torrent_igry/strategy/men_of_war_assault_squad_v_2_05_15_6dls/7-1-0-869" class="headLink">Скачать игру</a> <a href="https://vk.com/hotstagemod" class="headLink"><img src="images/vklogo.png" width="20"></a> <a href="https://www.youtube.com/channel/UCdir_DYFvo_rPs0TPg83qYA" class="headLink"><img src="images/youtubelogo.png" width="20"></a> <a href="https://www.moddb.com/mods/fuel-of-war-mod" class="headLink" title="View Fuel of War Mod on Mod DB" target="_blank"><img src="https://media.moddb.com/images/global/moddb.png"; alt="Fuel of War Mod" width=20></a>
			</div>
			<?php 
				if(isset($_GET['activity'])) {
					switch($_GET['activity']) {
						case 'send':
							?>
								<div class="newsBlock">
									<form action="index.php" enctype="multipart/form-data" method="post">
										<input type="file" name="FILE">
										<input type="submit">
									</form>
								</div>
							<?php
							break;
						case 'item':
							$fn = json_decode(file_get_contents('news/'.$_GET['filename']), true);
							$pic = $sdk->DBGet('users','prof_pic','WHERE `login`=\''.$fn['author'].'\'');
							$gallery = isset($fn['gallery'])?explode(';', $fn['gallery']):'';
							if(strcmp($gallery[0], '') == 0) {
							    unset($gallery);
							}
							echo '<div style="width: 38em; background-color: #6d6d6d; border-radius: 10px; padding: 1em; margin-bottom: 25px;"><table style="width: 100%;"><tr>
    							<td style="display: inline-block; height: 4em; width: 4em; vertical-align: top"><img style="width: 4em; height: 4em;" class="round" src="'.$pic['prof_pic'][0].'"></td>
    							<td style="display: inline-block; vertical-align: top"><font style="color: #fff; font-size: 150%;">'.$fn['author'].'</font><br><font style="color: #fff;">'.date('d-m-Y',$fn['time']).'</font><br><font style="color: #fff;">Просмотров: '.$fn['views'].'</font></td>
    							<td style="width: 15em; color: #fff; font-size: 150%;">'.$fn['title'].'</td>
							</tr>
							</table>
							<div class="item">'.$fn['all_text'].'</div>';
							$galleryhtml = '';
							if(isset($gallery)) {
							    for($i = 0; $i < count($gallery); $i++) {
							        $galleryhtml .= '<img src="'.$gallery[$i].'" alt="Картинка '.$i.'" tabindex="0" />';
							    }
							}
							echo isset($gallery)?'<div class="gallery">'.$galleryhtml.'<div></div></div>':'';
							echo '</div>';
							$fn['views']++;
							$sdk->sendToFile('news/'.$_GET['filename'], json_encode($fn));
							break;
						case 'editor':
						?>
							<div class="newsBlock" style="background-color: #6d6d6d; color: #fff; border-radius: 10px; margin-bottom: 15px;">
								<form action="index.php" method="post">
									<div style="margin: 1em;">
										<div style="width: 40%;">
											Название: <input onkeypress="handle(e)" id="name" type="text" name="title"><br>
										</div>
										<br>
										<div style="width: 50%;">
											Картинка превью: <input id="imgpath" type="text" name="pic" value="photos/News_blank.png"><br><a class="bl_rd_h" style="text-decoration: solid white; color: #fff;" href="index.php?activity=send">(Загрузить фотографию)</a>
										</div>
										<div style="width: 100%;">
											Основной текст: <br>
											<textarea style="width: 100%;" name="all_text"></textarea>
										</div>
										<div style="width: 100%;">
											Текст превью: <br>
											<textarea id="previewText" style="width: 100%;" name="preview_text"></textarea>
										</div>
										<div style="width: 100%;">
											Галерея: <br>
											<textarea style="width: 100%;" name="gallery"></textarea>
										</div>
										Просто начните писать. <a href="guide.html">Гайд, как этим пользоваться</a><br>
										<div>
											<input type="text" name="act" value="publish" hidden>
											<input type="submit" value="Опубликовать">
										</div>
									</div>
								</form>
								<font style="margin: 1em; color: #fff;">Ниже показано, как данная новость будет выглядеть в ленте.</font>
						  	</div>
							
							<div class="newitem">
								<div class="title" style="clear: left;">
									<div style="width: 85%; display: inline-block;">
										<span class="title" id="titler">
                                            
										</span>
									</div>
									<div style="text-align: right; width: 13.9%; display: inline-block;">
										<a href="#" style="color: #ffff00; font-size: 0.9em; margin-right: 1em;">
											Х
										</a>
									</div>
								</div>
								<div class="body">
									<div class="itempic">
										<img id="img" class="itempic" src="
										
										">
									</div>
									<div id="itemText" class="itemtext">
                                        
									</div>
								</div>
								<table style="background-color: #1b1b1b;" class="bottom">
									<tr>
										<td style="float: left; margin-left: 1em;">11-06-2018</td>
										<td style="float: right; margin-right: 1em;">
                                            <a class="user" href="#"><?=$_COOKIE['login']?></a>
										</td>
									</tr>
								</table>
                            </div>
							<script type="text/javascript" src="script/editor.js"></script>
						<?php
						    break;
						case 'settings':
							echo '<div class="r_b w100 c_w mid">
							    
							</div>';
							break;	
						default:
							?>
								<div class="r_b w100 mid">
									<b class="c_w">Мы не можем найти такую активность.</b>
								</div>
							<?php
							break;
					}
				} else {
					echo '<div class="newsBlock">';
					$files = scandir('news');
					for($i = 0; $i < count($files); $i++) {
						if((strcmp($files[$i], '.') != 0) and (strcmp($files[$i], '..'))) {
							$item = json_decode(file_get_contents('news/' . $files[$i]), true);
							echo '<div class="newitem"><a class="nodec" href="index.php?activity=item&filename='.$files[$i].'">
									<div class="title" style="clear: left;">
										<div style="width: '.(
											$sdk->logged($_COOKIE)?(
												$sdk->isAdmin($_COOKIE['login'])?'85%':'100%'
											):'100%'
										).'; display: inline-block;">
											<span class="title">'.
												$item['title']
											.'</span>
										</div>'.(
											$sdk->logged($_COOKIE)?(
												$sdk->isadmin($_COOKIE['login'])?
												'<div style="text-align: right; width: 15%; display: inline-block;">
													<a href="index.php?delete='.$files[$i].'" style="color: #ffff00; font-size: 0.9em; margin-right: 1em;">
														Х
													</a>
												</div>':''
											):''
										)
									.'</div>
									<div class="body">
										<div class="itempic">
											<img class="itempic" src="'.
											$item['pic']
											.'">
										</div>
										<div class="itemtext">'.
										$item['preview_text']
										.'</div>
									</div>
									<table style="background-color: #1b1b1b;" class="bottom">
										<tr>
											<td style="float: left; margin-left: 1em;">'.date('d-m-Y', $item['time']).'</td>
											<td style="float: right; margin-right: 1em;">
											 <a class="user" href="users/'.$item['author'].'">'.$item['author'].'</a>
											</td>
										</tr>
									</table></a>
							  </div>
							  ';
						} 
					}
					echo '</div>';
				}
			?>
			<div id="vk_comments" style="border-radius: 10px;"></div>
			<script type="text/javascript">
				VK.Widgets.Comments("vk_comments", {limit: 20, attach: "*"});
			</script>
			<p style="color: black; text-align: center; background-color: #6d6d6d; border-radius: 10px;">(c) Nikita Tihonovich, 2018. <a style="background-color: #4a76a8; color: #fff;" href="https://vk.com/id165054978">VK</a></p>
		</div>
	</body>
</html>