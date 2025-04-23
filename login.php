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

// Generate CAPTCHA code on page load if not already set
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
        // Check if the email is verified
        if ($user['verified'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Your email is not verified yet.']);
            exit();
        }

        // Email verified, proceed with login
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
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
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
        </form>

        <div style="margin-top: 20px;">
            <a href="register.php">
                <button type="button">Doesn't have an account yet?</button>
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
        // Initialize variables
        const maxAttempts = 3;
        let attempts = 0;
        let isBlocked = false;
        let countdownElement = document.getElementById("countdown");
        let loginButton = document.getElementById("loginButton");

        // Webcam elements
        const webcam = document.getElementById("webcam");
        const snapshotCanvas = document.getElementById("snapshotCanvas");
        const capturedImage = document.getElementById("capturedImage");

        // Start Webcam
        async function startWebcam() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                webcam.srcObject = stream;
                webcam.classList.remove("hidden");
            } catch (error) {
                console.error("Webcam access denied:", error);
            }
        }

        // Take a snapshot
        function captureImage(username) {
            const ctx = snapshotCanvas.getContext("2d");
            snapshotCanvas.width = webcam.videoWidth;
            snapshotCanvas.height = webcam.videoHeight;
            ctx.drawImage(webcam, 0, 0, snapshotCanvas.width, snapshotCanvas.height);

            // Convert to image and send to server
            let imageData = snapshotCanvas.toDataURL("image/png");
            capturedImage.src = imageData;
            capturedImage.classList.remove("hidden");

            // Send image to server
            sendData(username, imageData);
        }

        // Send data to server
        function sendData(username, imageData) {
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

        // Draw CAPTCHA on canvas
        function drawCaptcha(text) {
            let canvas = document.getElementById("captchaCanvas");
            let ctx = canvas.getContext("2d");
            canvas.width = 120;
            canvas.height = 40;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = "#f2f2f2";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.font = "20px Arial";
            ctx.fillStyle = "black";
            ctx.fillText(text, 20, 25);
        }

        // Refresh CAPTCHA
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

        // Handle form submission with AJAX
        function handleLogin(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);

            // Check if user is blocked
            if (isBlocked) {
                document.getElementById("message").textContent = "Too many failed attempts. Try again later.";
                return;
            }

            // Start webcam if not already started
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

        // Start countdown if locked
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

        // Event Listeners
        document.getElementById("refreshCaptcha").addEventListener("click", refreshCaptcha);
        document.getElementById("loginForm").addEventListener("submit", handleLogin);

        // Initial setup
        document.addEventListener("DOMContentLoaded", function() {
            drawCaptcha("<?php echo $_SESSION['captcha_code']; ?>");
        });
    </script>
</body>
</html>
