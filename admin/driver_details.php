<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Get driver ID from URL
$driver_id = $_GET['id'] ?? 0;

// Fetch driver details
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email, u.contact_number, u.created_at 
                      FROM drivers d 
                      JOIN users u ON d.user_id = u.user_id 
                      WHERE d.driver_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

if (!$driver) {
    header("Location: drivers.php?error=driver_not_found");
    exit();
}

// Fetch driver's ride history
$stmt = $pdo->prepare("SELECT r.*, u.full_name as passenger_name 
                      FROM rides r
                      JOIN users u ON r.user_id = u.user_id
                      WHERE r.driver_id = ?
                      ORDER BY r.requested_time DESC");
$stmt->execute([$driver_id]);
$rides = $stmt->fetchAll();

// Calculate driver statistics
$stmt = $pdo->prepare("SELECT 
                       COUNT(*) as total_rides,
                       SUM(r.fare) as total_earnings,
                       AVG(r.fare) as avg_fare,
                       AVG(f.rating) as avg_rating
                       FROM rides r
                       LEFT JOIN feedback f ON r.ride_id = f.ride_id
                       WHERE r.driver_id = ? AND r.status = 'completed'");
$stmt->execute([$driver_id]);
$stats = $stmt->fetch();

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE drivers SET status = ? WHERE driver_id = ?");
        $stmt->execute([$new_status, $driver_id]);
        header("Location: driver_details.php?id=" . $driver_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Details | KalTrike V2</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .driver-profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .driver-info {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .driver-stats {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            background: #f8f9fa;
        }
        .stat-card h3 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        .rating {
            color: #f1c40f;
            font-size: 24px;
        }
        .status-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .ride-history {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        .badge-unavailable {
            background: #fff3cd;
            color: #856404;
        }
        .badge-on-ride {
            background: #cce5ff;
            color: #004085;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Driver Details</h1>
            <a href="drivers.php" class="btn btn-secondary">Back to Drivers</a>
        </div>
        
        <div class="driver-profile">
            <div class="driver-info">
                <h2><?php echo htmlspecialchars($driver['full_name']); ?></h2>
                <p><strong>Driver ID:</strong> <?php echo $driver['driver_id']; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($driver['email']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($driver['contact_number']); ?></p>
                <p><strong>License:</strong> <?php echo htmlspecialchars($driver['license_number']); ?></p>
                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($driver['vehicle_type']); ?> (<?php echo htmlspecialchars($driver['vehicle_plate']); ?>)</p>
                <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($driver['created_at'])); ?></p>
                
                <div class="status-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="form-group">
                            <label for="status">Driver Status</label>
                            <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                                <option value="available" <?php echo $driver['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="unavailable" <?php echo $driver['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                <option value="on_ride" <?php echo $driver['status'] === 'on_ride' ? 'selected' : ''; ?>>On Ride</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="driver-stats">
                <h2>Performance Statistics</h2>
                <div class="stat-grid">
                    <div class="stat-card">
                        <h3>Total Rides</h3>
                        <p><?php echo $stats['total_rides'] ?? 0; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Earnings</h3>
                        <p>₱<?php echo number_format($stats['total_earnings'] ?? 0, 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Average Fare</h3>
                        <p>₱<?php echo number_format($stats['avg_fare'] ?? 0, 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Average Rating</h3>
                        <p>
                            <?php if ($stats['avg_rating']): ?>
                                <span class="rating">
                                    <?php echo str_repeat('★', round($stats['avg_rating'])); ?>
                                </span>
                                (<?php echo number_format($stats['avg_rating'], 1); ?>)
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ride-history">
            <h2>Ride History</h2>
            <?php if (count($rides) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ride ID</th>
                            <th>Passenger</th>
                            <th>Date</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Fare</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rides as $ride): ?>
                        <tr>
                            <td><?php echo $ride['ride_id']; ?></td>
                            <td><?php echo htmlspecialchars($ride['passenger_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($ride['requested_time'])); ?></td>
                            <td><?php echo htmlspecialchars($ride['pickup_location']); ?></td>
                            <td><?php echo htmlspecialchars($ride['dropoff_location']); ?></td>
                            <td>₱<?php echo number_format($ride['fare'], 2); ?></td>
                            <td>
                                <?php if ($ride['status'] === 'completed'): ?>
                                    <span class="badge badge-available">Completed</span>
                                <?php elseif ($ride['status'] === 'cancelled'): ?>
                                    <span class="badge badge-unavailable">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge badge-on-ride"><?php echo ucfirst($ride['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No ride history found for this driver.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>