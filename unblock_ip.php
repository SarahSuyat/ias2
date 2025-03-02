<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ip = $_POST['ip'];

$sql = "UPDATE login_attempts SET blocked = 0 WHERE ip_address = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ip);
$stmt->execute();

echo $stmt->affected_rows > 0 ? "success" : "error";

$stmt->close();
$conn->close();
?>
