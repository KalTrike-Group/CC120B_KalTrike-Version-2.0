<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();
if (!isUser()) {
    header("Location: ../index.php");
    exit();
}

// Fetch user's ride history
$stmt = $pdo->prepare("SELECT r.*, 
                      d.driver_id, u.full_name as driver_name,
                      d.vehicle_type, d.vehicle_plate
                      FROM rides r
                      LEFT JOIN drivers d ON r.driver_id = d.driver_id
                      LEFT JOIN users u ON d.user_id = u.user_id
                      WHERE r.user_id = ?
                      ORDER BY r.requested_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$rides = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Ride History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Your Ride History</h1>
        
        <div class="history-actions">
            <form method="GET" class="filter-form">
                <div class="form-group-inline">
                    <label for="status">Filter by Status:</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Apply Filter</button>
            </form>
            
            <a href="javascript:window.print()" class="btn btn-secondary">Print Report</a>
        </div>
        
        <table class="history-table">
            <thead>
                <tr>
                    <th>Ride ID</th>
                    <th>Date</th>
                    <th>Pickup</th>
                    <th>Dropoff</th>
                    <th>Driver</th>
                    <th>Vehicle</th>
                    <th>Fare</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rides as $ride): ?>
    <tr>
        <td><?php echo $ride['ride_id']; ?></td>
        <td><?php echo date('M j, Y h:i A', strtotime($ride['requested_time'])); ?></td>
        <td><?php echo htmlspecialchars($ride['pickup_location']); ?></td>
        <td><?php echo htmlspecialchars($ride['dropoff_location']); ?></td>
        <td>
            <?php if ($ride['driver_name']): ?>
                <?php echo htmlspecialchars($ride['driver_name']); ?><br>
                <?php echo htmlspecialchars($ride['vehicle_type']); ?> (<?php echo htmlspecialchars($ride['vehicle_plate']); ?>)
            <?php else: ?>
                Not assigned
            <?php endif; ?>
        </td>
        <td>₱<?php echo number_format($ride['fare'], 2); ?></td>
        <td>
            <span class="status-badge <?php echo $ride['status']; ?>">
                <?php echo ucfirst($ride['status']); ?>
            </span>
        </td>
        <td class="actions">
    <?php if ($ride['status'] === 'completed'): ?>
        <?php 
        // Check if feedback exists
        $feedbackGiven = hasGivenFeedback($ride['ride_id']);
        ?>
        
        <?php if (!$feedbackGiven): ?>
            <a href="feedback.php?ride_id=<?php echo $ride['ride_id']; ?>" 
               class="btn btn-small btn-primary">
               Give Feedback
            </a>
        <?php else: ?>
            <span class="text-muted">Feedback submitted</span>
        <?php endif; ?>
    <?php elseif ($ride['status'] === 'pending'): ?>
        <form method="POST" action="cancel_ride.php">
            <input type="hidden" name="ride_id" value="<?php echo $ride['ride_id']; ?>">
            <button type="submit" class="btn btn-small btn-danger">
                Cancel Ride
            </button>
        </form>
    <?php endif; ?>
</td>
    </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>