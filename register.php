<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

require_once 'mailer.php';


$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user's IP address
$ip = $_SERVER['REMOTE_ADDR'];

// 🔴 Check if IP is blocked
$stmt = $conn->prepare("SELECT blocked FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['blocked'] == 1) {
    die("Access denied. Your IP ($ip) has been blocked.");
}

// ✅ Handle registration logic
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $email = trim($_POST['email']);  // Add email field

    if (empty($username) || empty($password) || empty($confirm) || empty($email)) {
        $message = "All fields are required.";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username already exists.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "Email already registered.";
            } else {
                // Register new user
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $verificationCode = generateVerificationCode();  // Function to generate the verification code

                // Insert into the database with the verification code
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, verification_code) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed, $email, $verificationCode);

                if ($stmt->execute()) {
                    // Send the verification email
                    sendVerificationEmail($email, $username, $verificationCode);

                    $message = "Registration successful! Please check your email to verify your account.";
                } else {
                    $message = "Registration failed. Please try again.";
                }
            }
        }
    }
}

$conn->close();

// Generate a random verification code
function generateVerificationCode() {
    return bin2hex(random_bytes(16)); // Generates a 32-character hex string
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ecf0f1;
            padding: 50px;
            text-align: center;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 {
            margin-bottom: 20px;
        }
        input {
            padding: 10px;
            margin: 10px 0;
            width: 250px;
            font-size: 14px;
        }
        button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .msg {
            margin-top: 15px;
            font-weight: bold;
            color: #e74c3c;
        }
        .login-btn {
            margin-top: 20px;
        }
        .login-btn a button {
            background: #2ecc71;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Register</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="confirm" placeholder="Confirm Password" required><br>
        <input type="email" name="email" placeholder="Email" required><br> <!-- Email field added -->
        <button type="submit">Register</button>
    </form>

    <div class="msg"><?php echo $message; ?></div>

    <!-- Login button -->
    <div class="login-btn">
        <a href="login.php">
            <button type="button">Already have an account? Login</button>
        </a>
    </div>
</div>

</body>
</html>
