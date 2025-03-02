const correctUsername = "admin";
const correctPassword = "password123";
const maxAttempts = 3;
let attempts = parseInt(localStorage.getItem("attempts")) || 0;
let isBlocked = localStorage.getItem("blocked");
let countdownElement = document.getElementById("countdown");
let loginButton = document.getElementById("loginButton");

let captchaCode = ""; // Stores CAPTCHA
let userInteracted = false; // Track user interaction

// Webcam elements
const webcam = document.getElementById("webcam");
const snapshotCanvas = document.getElementById("snapshotCanvas");
const capturedImage = document.getElementById("capturedImage");

// Get user media for webcam
async function startWebcam() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        webcam.srcObject = stream;
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
    let imageData = snapshotCanvas.toDataURL("image/png"); // ✅ Define imageData
    capturedImage.src = imageData;
    capturedImage.classList.remove("hidden");

    sendData(username, imageData); // ✅ Now imageData is correctly passed
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


// Detect user interaction
document.addEventListener("mousemove", enableLogin);
document.addEventListener("keydown", enableLogin);

function enableLogin() {
    if (!userInteracted) {
        userInteracted = true;
        loginButton.disabled = false;
        startWebcam(); // Start webcam when user interacts
    }
}

// Generate CAPTCHA
function generateCaptcha() {
    captchaCode = Math.random().toString(36).substring(2, 8).toUpperCase();
    drawCaptcha(captchaCode);
}

// Draw CAPTCHA
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



// Start countdown if locked
if (isBlocked) {
    startCountdown();
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
            localStorage.removeItem("blocked");
            localStorage.setItem("attempts", 0);
            countdownElement.classList.add("hidden");
            loginButton.disabled = false;
            document.getElementById("message").textContent = "You can now try logging in again.";

            // Clear captured image and hide webcam
            capturedImage.src = "";
            capturedImage.classList.add("d-none");
            webcam.style.display = "none";

            
        }
    }, 1000);
}

document.getElementById("refreshCaptcha").addEventListener("click", generateCaptcha);

document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;
    let captchaInput = document.getElementById("captchaInput").value.toUpperCase();
    
    // Retrieve the latest attempt count
    let attempts = parseInt(localStorage.getItem("attempts")) || 0;

    // If user is blocked, stop execution
    if (localStorage.getItem("blocked")) {
        document.getElementById("message").textContent = "Too many failed attempts. Try again later.";
        return;
    }

    // CAPTCHA Validation
    if (captchaInput !== captchaCode) {
        document.getElementById("message").textContent = "Incorrect CAPTCHA. Taking a picture...";
        captureImage(username);
        generateCaptcha();
        document.getElementById("captchaInput").value = ""; // Clear input

        setTimeout(() => {
            capturedImage.src = "";
            capturedImage.classList.add("d-none");
            webcam.style.display = "none";
        }, 3000); // Hide after 5 seconds

        return;
    }

    // Check credentials
    if (username === correctUsername && password === correctPassword) {
        alert("Login successful!");
        localStorage.setItem("attempts", 0); // Reset attempts on success
        window.location.href = "dashboard.php"; // Redirect to admin dashboard
    } else {
        attempts++; // Increase attempts
        localStorage.setItem("attempts", attempts);
        document.getElementById("message").textContent = `Incorrect credentials. Attempts left: ${maxAttempts - attempts}`;

        // If max attempts reached, block user
        if (attempts >= maxAttempts) {
            document.getElementById("message").textContent = "Too many failed attempts. Capturing image...";
            captureImage(username);
            localStorage.setItem("blocked", "true");
            startCountdown();
        }
    }
});


// Generate initial CAPTCHA
generateCaptcha();

