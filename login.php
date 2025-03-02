<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ip = $_SERVER['REMOTE_ADDR']; // Get the user's IP address

// 🔴 **Check if the IP is blocked BEFORE allowing login**
$stmt = $conn->prepare("SELECT blocked FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['blocked'] == 1) {
    die("Access denied. Your IP ($ip) has been blocked.");
}

// ✅ If the IP is not blocked, proceed with login authentication
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        echo "Login successful!";
    } else {
        echo "Invalid username or password.";
    }
}

$conn->close();
?>
