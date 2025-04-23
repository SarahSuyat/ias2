<?php
// Database connection settings
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

// Create a new connection to MySQL database
$conn = new mysqli($host, $user, $pass, $dbname);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
