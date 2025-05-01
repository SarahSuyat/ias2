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

$ip = $_SERVER['REMOTE_ADDR'];

$stmt = $conn->prepare("SELECT blocked FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['blocked'] == 1) {
    die("Access denied. Your IP ($ip) has been blocked.");
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $email = trim($_POST['email']);

    if (empty($username) || empty($password) || empty($confirm) || empty($email)) {
        $message = "All fields are required.";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username already exists.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "Email already registered.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $verificationCode = generateVerificationCode();

                $stmt = $conn->prepare("INSERT INTO users (username, password, email, verification_code) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed, $email, $verificationCode);

                if ($stmt->execute()) {
                    sendVerificationEmail($email, $username, $verificationCode);
                    $message = "Registration successful! Please check your email.";
                } else {
                    $message = "Registration failed. Please try again.";
                }
            }
        }
    }
}

$conn->close();

function generateVerificationCode() {
    return bin2hex(random_bytes(16));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .container {
            background: rgba(34, 31, 31, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 350px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        h2 {
            color: white;
            margin-bottom: 20px;
        }

        input {
            width: 80%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            border: none;
            outline: none;
            background: rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 14px;
        }

        ::placeholder {
            color: white;
            opacity: 0.8;
        }

        button {
            margin-top: 20px;
            width: 80%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: linear-gradient(135deg, #a777e3, #6e8efb);
        }

        .msg {
            margin-top: 15px;
            color: #f8d7da;
            font-weight: bold;
        }

        .login-btn {
            margin-top: 15px;
        }

        .login-btn a {
            text-decoration: none;
        }

        .login-btn button {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .login-btn button:hover {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Account</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="confirm" placeholder="Confirm Password" required><br>
        <button type="submit">Register</button>
    </form>

    <div class="msg"><?php echo $message; ?></div>

    <div class="login-btn">
        <a href="login.php">
            <button type="button">Already have an account?</button>
        </a>
    </div>
</div>

</body>
</html>
