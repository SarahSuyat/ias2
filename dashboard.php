
<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "login_security";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM login_attempts ORDER BY timestamp DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="mb-4">Login Attempts</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Username</th>
                <th>IP Address</th>
                <th>Timestamp</th>
                <th>Captured Image</th>
            </tr>
        </thead>
        <tbody>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo $row['ip_address']; ?></td>
            <td><?php echo $row['timestamp']; ?></td>
            <td>
                <?php if ($row['image_path']) { ?>
                    <img src="http://localhost/ias_final/<?php echo $row['image_path']; ?>" width="100">
                <?php } else { echo "No image"; } ?>
            </td>
            <td>
                <?php if ($row['blocked']) { ?>
                    <button class="btn btn-success unblock-btn" data-ip="<?php echo $row['ip_address']; ?>">Unblock</button>
                <?php } else { ?>
                    <button class="btn btn-danger block-btn" data-ip="<?php echo $row['ip_address']; ?>">Block</button>
                <?php } ?>
                <button class="btn btn-warning delete-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
            </td>
        </tr>
    <?php } ?>
</tbody>

    </table>

    <script>
document.addEventListener("DOMContentLoaded", function () {
    function updateIPStatus(ip, url, element) {
        if (!ip) {
            alert("Invalid IP address.");
            return;
        }

        fetch(url, {
            method: "POST",
            body: new URLSearchParams({ ip: ip }),
            headers: { "Content-Type": "application/x-www-form-urlencoded" }
        })
        .then(response => response.text())
        .then(data => {
            console.log(`Response from ${url}:`, data); // Debugging
            if (data.trim() === "success") {
                alert("Operation Successful!");
                location.reload(); // Refresh UI
            } else {
                alert("Error: " + data);
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            alert("Network error.");
        });
    }

    // 🟢 Ensure buttons use updateIPStatus AFTER it is defined
    document.querySelectorAll(".block-btn").forEach(button => {
        button.addEventListener("click", function () {
            let ip = this.dataset.ip;
            updateIPStatus(ip, "block_ip.php", this);
        });
    });

    document.querySelectorAll(".unblock-btn").forEach(button => {
        button.addEventListener("click", function () {
            let ip = this.dataset.ip;
            updateIPStatus(ip, "unblock_ip.php", this);
        });
    });

    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            let recordId = this.dataset.id;
            if (confirm("Are you sure you want to delete this record?")) {
                updateRecordStatus(recordId, "delete_ip.php", this.closest("tr"));
            }
        });
    });

    function updateRecordStatus(id, url, element) {
        fetch(url, {
            method: "POST",
            body: new URLSearchParams({ id: id }),
            headers: { "Content-Type": "application/x-www-form-urlencoded" }
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "success") {
                element.remove(); // Remove only the selected row
            } else {
                alert("Error deleting record.");
            }
        })
        .catch(error => console.error("Error:", error));
    }
});

</script>

</body>
</html>

<?php $conn->close(); ?>