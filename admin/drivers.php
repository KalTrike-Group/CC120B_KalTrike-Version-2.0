<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Get all drivers
$stmt = $pdo->query("SELECT d.*, u.full_name, u.email, u.contact_number 
                     FROM drivers d 
                     JOIN users u ON d.user_id = u.user_id 
                     ORDER BY d.driver_id DESC");
$drivers = $stmt->fetchAll();

// Handle driver status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['driver_id'])) {
    $driver_id = $_POST['driver_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE drivers SET status = ? WHERE driver_id = ?");
    $stmt->execute([$status, $driver_id]);
    
    header("Location: drivers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Manage Drivers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Manage Drivers</h1>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>License</th>
                        <th>Vehicle</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drivers as $driver): ?>
                    <tr>
                        <td><?php echo $driver['driver_id']; ?></td>
                        <td><?php echo htmlspecialchars($driver['full_name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($driver['email']); ?><br>
                            <?php echo htmlspecialchars($driver['contact_number']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($driver['vehicle_type']); ?><br>
                            <?php echo htmlspecialchars($driver['vehicle_plate']); ?>
                        </td>
                        <td><?php echo number_format($driver['rating'], 1); ?> ★</td>
                        <td>
                            <form method="POST" class="status-form">
                                <input type="hidden" name="driver_id" value="<?php echo $driver['driver_id']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="available" <?php echo $driver['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="unavailable" <?php echo $driver['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <a href="driver_details.php?id=<?php echo $driver['driver_id']; ?>" class="btn btn-small">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <footer>
    <?php include '../includes/footer.php'; ?>
    </footer>
</body>
</html>