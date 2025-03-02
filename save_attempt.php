<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$imageData = $_POST['image'];

// Decode and save image
$imagePath = "captures/" . time() . ".png";
$imageData = str_replace("data:image/png;base64,", "", $imageData);
$imageData = base64_decode($imageData);
file_put_contents($imagePath, $imageData);

// Insert into database
$sql = "INSERT INTO login_attempts (username, ip_address, image_path) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $ip_address, $imagePath);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Attempt saved.";
} else {
    echo "Error saving attempt.";
}

$stmt->close();
$conn->close();
?>