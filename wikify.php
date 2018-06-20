<?php
header('Access-Control-Allow-Origin: https://milpro.ml');
require_once 'sdk.php';
$sdk = new SDK();
echo $sdk->wikify($_POST['t']);
?>