<?php
session_start();

// Generate new CAPTCHA
$_SESSION['captcha_code'] = strtoupper(substr(md5(rand()), 0, 6));

// Return the new CAPTCHA
echo json_encode(['captcha' => $_SESSION['captcha_code']]);
?>