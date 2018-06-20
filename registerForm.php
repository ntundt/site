<!DOCTYPE html>
<head>
	<link rel="stylesheet" href="style/main.css">
	<link rel="stylesheet" href="https://pastebin.com/raw/ADr7mXUG">
	<title>Регистрация</title>
	<link rel="shortcut icon" href="/images/icons/favicon.ico" type="image/x-icon">
	<style>pre{white-space: pre-wrap;}</style>
</head>
<body>
	<div class="registerForm">
		<form action="index.php" method="post">
			<pre>Логин:<input type="text" name="login"><br></pre><?=(isset($_GET['isset'])?'Логин, который Вы указали ранее, уже занят.<br>':''); ?></pre>
<pre>Пароль:<input type="password" name="password"><br><center>Не принимает почту в доменной зоне .ru! Рекомендуем использовать Gmail.</center></pre>
<pre></pre>Почта:<input type="text" name="email"><br><input type="text" name="act" value="register" hidden>
<input type="submit">
			</pre>
		</form>
	</div>
</body>