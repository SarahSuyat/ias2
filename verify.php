<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the verification code from the URL
$verificationCode = $_GET['code'];

// Check if the verification code exists in the database
$stmt = $conn->prepare("SELECT username, verified FROM users WHERE verification_code = ?");
$stmt->bind_param("s", $verificationCode);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Check if the account is already verified
    if ($user['verified'] == 1) {
        echo "Your email is already verified. You can log in now.";
    } else {
        // Mark the user as verified in the database
        $stmt = $conn->prepare("UPDATE users SET verified = 1 WHERE verification_code = ?");
        $stmt->bind_param("s", $verificationCode);
        if ($stmt->execute()) {
            echo "Your email has been successfully verified. You can now <a href='http://localhost/ias2/login.php'>log in</a>.";
        } else {
            echo "Something went wrong. Please try again later.";
        }
    }
} else {
    echo "Invalid verification code.";
}

$conn->close();
?>
