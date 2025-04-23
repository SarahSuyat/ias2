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

// Get user media for webcam
async function startWebcam() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        webcam.srcObject = stream;
        webcam.classList.remove("hidden");
    } catch (error) {
        console.error("Webcam access denied:", error);
    }
}

// Function to take a snapshot
function captureImage(username) {
    const ctx = snapshotCanvas.getContext("2d");
    snapshotCanvas.width = webcam.videoWidth;
    snapshotCanvas.height = webcam.videoHeight;
    ctx.drawImage(webcam, 0, 0, snapshotCanvas.width, snapshotCanvas.height);

    // Convert to image & display
    let imageData = snapshotCanvas.toDataURL("image/png");
    capturedImage.src = imageData;
    capturedImage.classList.remove("hidden");

    // Send data to server
    sendData(username, imageData);
}

// Function to send data via fetch request
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

// Draw CAPTCHA from PHP session
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

// Refresh CAPTCHA - request new one from server
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
            
            // Hide webcam elements
            webcam.classList.add("hidden");
            capturedImage.classList.add("hidden");
        }
    }, 1000);
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
            
            if (data.error === 'captcha') {
                captureImage(formData.get('username'));
                refreshCaptcha();
            }
            
            if (data.error === 'credentials') {
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

// Event Listeners
document.getElementById("refreshCaptcha").addEventListener("click", refreshCaptcha);
document.getElementById("loginForm").addEventListener("submit", handleLogin);

// Initial setup
document.addEventListener("DOMContentLoaded", function() {
    // Draw initial CAPTCHA from PHP session
    drawCaptcha("<?php echo $_SESSION['captcha_code']; ?>");
    
    // Check if user should be blocked
    fetch("check_blocked.php")
        .then(response => response.json())
        .then(data => {
            if (data.blocked) {
                isBlocked = true;
                startCountdown();
            }
        });
});