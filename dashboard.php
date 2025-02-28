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
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>

<?php $conn->close(); ?>
