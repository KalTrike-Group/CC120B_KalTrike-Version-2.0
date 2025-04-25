<?php
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT r.*, u.full_name as user_name, d.user_id as driver_id, 
          du.full_name as driver_name 
          FROM rides r 
          JOIN users u ON r.user_id = u.user_id 
          LEFT JOIN drivers d ON r.driver_id = d.driver_id 
          LEFT JOIN users du ON d.user_id = du.user_id 
          WHERE 1=1";
$params = [];

if (!empty($status)) {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $query .= " AND DATE(r.requested_time) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(r.requested_time) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY r.requested_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rides = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Manage Rides</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Manage Rides</h1>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group-inline">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group-inline">
                    <label for="date_from">From:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="form-group-inline">
                    <label for="date_to">To:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <button type="submit" class="btn btn-secondary">Apply Filters</button>
                <a href="rides.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ride ID</th>
                        <th>User</th>
                        <th>Driver</th>
                        <th>Pickup</th>
                        <th>Dropoff</th>
                        <th>Requested</th>
                        <th>Fare</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rides as $ride): ?>
                    <tr>
                        <td><?php echo $ride['ride_id']; ?></td>
                        <td><?php echo htmlspecialchars($ride['user_name']); ?></td>
                        <td><?php echo $ride['driver_name'] ? htmlspecialchars($ride['driver_name']) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($ride['pickup_location']); ?></td>
                        <td><?php echo htmlspecialchars($ride['dropoff_location']); ?></td>
                        <td><?php echo date('M j, Y h:i A', strtotime($ride['requested_time'])); ?></td>
                        <td>₱<?php echo number_format($ride['fare'], 2); ?></td>
                        <td><?php echo ucfirst($ride['status']); ?></td>
                        <td>
                            <a href="ride_details.php?id=<?php echo $ride['ride_id']; ?>" class="btn btn-small">View</a>
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