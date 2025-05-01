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

// Generate CAPTCHA code if not already set
if (!isset($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = strtoupper(substr(md5(rand()), 0, 6)); // Random 6-character CAPTCHA
}

// Handle IP Blocking
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $conn->prepare("SELECT blocked FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param("s", $ip);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['blocked'] == 1) {
    die("Access denied. Your IP ($ip) has been blocked.");
}

// Handle login form submission with CAPTCHA validation
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $captcha = $_POST['captcha']; // Get CAPTCHA value entered by the user

    // Verify CAPTCHA
    if ($captcha != $_SESSION['captcha_code']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CAPTCHA.']);
        exit();
    }

    // Verify user credentials
    $stmt = $conn->prepare("SELECT password, verified FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Check if email is verified
        if ($user['verified'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Your email is not verified yet.']);
            exit();
        }

        // Email verified, proceed
        $_SESSION['username'] = $username;
        echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Poppins', sans-serif;
        background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
        background-size: cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    body::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.4); /* dark overlay */
        
        z-index: 0;
    }
    .container {
        position: relative;
        background: rgba(44, 42, 42, 0.83); /* translucent */
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0px 10px 25px rgba(0,0,0,0.3);
        width: 400px;
        text-align: center;
        z-index: 1;
        backdrop-filter: blur(15px); /* form blur */
        border: 1px solid rgba(255, 255, 255, 0.3);
        animation: fadeIn 1s ease-in-out;
        color: white;
    }
    h2 {
        margin-bottom: 20px;
        color: #fff;
    }
    input[type="text"], input[type="password"], input[type="captcha"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        outline: none;
        color: white;
        transition: 0.3s;
    }
    input::placeholder {
        color: #ddd;
    }
    input:focus {
        border-color: #fff;
        box-shadow: 0 0 8px #fff;
    }
    button {
        width: 100%;
        padding: 12px;
        background: linear-gradient(to right, #6a11cb, #2575fc);
        border: none;
        border-radius: 8px;
        color: white;
        font-size: 16px;
        margin-top: 15px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    button:hover {
        background: linear-gradient(to right, #2575fc, #6a11cb);
    }
    #refreshCaptcha {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #ddd;
        margin-top: 5px;
        margin-bottom: 10px;
    }
    #refreshCaptcha:hover {
        color: #fff;
    }
    #message {
        margin-top: 20px;
        font-size: 14px;
        color: #ffb3b3;
        min-height: 20px;
    }
    canvas#captchaCanvas {
        margin: 10px 0;
        border-radius: 8px;
        background-color: rgba(255,255,255,0.1);
        display: block;
        margin-left: auto;
        margin-right: auto;
        box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
    }
    #webcamContainer {
        margin-top: 30px;
        text-align: center;
    }
    #webcam {
        border: 3px solid #6a5acd;
        border-radius: 10px;
    }
    #capturedImage {
        margin-top: 10px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }
    .hidden { display: none; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #countdown {
        margin-top: 10px;
        font-size: 14px;
        color: #ffcccb;
    }
    .register-button {
        background: none;
        border: 2px solid #fff;
        color: white;
        margin-top: 20px;
        transition: 0.3s;
        width: 100%;
        padding: 10px;
    }
    .register-button:hover {
        background: #6a5acd;
        color: white;
    }
</style>

</head>

<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST" id="loginForm">
            <input type="text" name="username" id="username" placeholder="Username" required><br>
            <input type="password" name="password" id="password" placeholder="Password" required><br>

            <!-- CAPTCHA Section -->
            <canvas id="captchaCanvas"></canvas>
            <button type="button" id="refreshCaptcha">🔄 Refresh</button><br>
            <input type="text" id="captchaInput" name="captcha" placeholder="Enter CAPTCHA" required>

            <button type="submit" id="loginButton">Login</button>
            <p id="countdown" class="hidden"></p>
        </form>

        <div>
            <a href="register.php">
                <button type="button" class="register-button">Doesn't have an account yet?</button>
            </a>
        </div>

        <p id="message"></p>
    </div>

    <!-- Webcam and Image Capture -->
    <div id="webcamContainer" class="hidden">
        <video id="webcam" width="320" height="240" autoplay></video>
        <canvas id="snapshotCanvas" class="hidden"></canvas>
        <img id="capturedImage" class="hidden" />
    </div>

    <script>
        const maxAttempts = 3;
        let attempts = 0;
        let isBlocked = false;
        const countdownElement = document.getElementById("countdown");
        const loginButton = document.getElementById("loginButton");

        const webcam = document.getElementById("webcam");
        const snapshotCanvas = document.getElementById("snapshotCanvas");
        const capturedImage = document.getElementById("capturedImage");

        async function startWebcam() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                webcam.srcObject = stream;
                webcam.classList.remove("hidden");
            } catch (error) {
                console.error("Webcam access denied:", error);
            }
        }

        function captureImage(username) {
            const ctx = snapshotCanvas.getContext("2d");
            snapshotCanvas.width = webcam.videoWidth;
            snapshotCanvas.height = webcam.videoHeight;
            ctx.drawImage(webcam, 0, 0, snapshotCanvas.width, snapshotCanvas.height);

            let imageData = snapshotCanvas.toDataURL("image/png");
            capturedImage.src = imageData;
            capturedImage.classList.remove("hidden");

            let formData = new FormData();
            formData.append("username", username);
            formData.append("image", imageData);

            fetch("save_attempt.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => console.log(data))
            .catch(error => console.error("Error:", error));
        }

        function drawCaptcha(text) {
            const canvas = document.getElementById("captchaCanvas");
            const ctx = canvas.getContext("2d");
            canvas.width = 120;
            canvas.height = 40;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = "#f2f2f2";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.font = "20px Arial";
            ctx.fillStyle = "black";
            ctx.fillText(text, 20, 25);
        }

        function refreshCaptcha() {
            fetch("refresh_captcha.php")
                .then(response => response.json())
                .then(data => {
                    if (data.captcha) {
                        drawCaptcha(data.captcha);
                    }
                })
                .catch(error => console.error("Error refreshing CAPTCHA:", error));
        }

        function handleLogin(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            if (isBlocked) {
                document.getElementById("message").textContent = "Too many failed attempts. Try again later.";
                return;
            }

            if (webcam.classList.contains("hidden")) {
                startWebcam();
            }

            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    document.getElementById("message").textContent = data.message;
                    if (data.message === 'Your email is not verified yet.') {
                        captureImage(formData.get('username'));
                        refreshCaptcha();
                    }
                    if (data.message === 'Invalid username or password.') {
                        attempts++;
                        if (attempts >= maxAttempts) {
                            isBlocked = true;
                            captureImage(formData.get('username'));
                            startCountdown();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById("message").textContent = "An error occurred. Please try again.";
            });
        }

        function startCountdown() {
            let timeLeft = 30;
            countdownElement.classList.remove("hidden");
            loginButton.disabled = true;

            let timer = setInterval(() => {
                countdownElement.textContent = `Try again in ${timeLeft} seconds`;
                timeLeft--;
                if (timeLeft < 0) {
                    clearInterval(timer);
                    countdownElement.classList.add("hidden");
                    loginButton.disabled = false;
                    document.getElementById("message").textContent = "You can now try logging in again.";
                }
            }, 1000);
        }

        document.getElementById("refreshCaptcha").addEventListener("click", refreshCaptcha);
        document.getElementById("loginForm").addEventListener("submit", handleLogin);

        document.addEventListener("DOMContentLoaded", function() {
            drawCaptcha("<?php echo $_SESSION['captcha_code']; ?>");
        });
    </script>
</body>
</html>
