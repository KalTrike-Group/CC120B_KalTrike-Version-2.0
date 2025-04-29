<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Users</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'user'");
                echo '<p>' . $stmt->fetchColumn() . '</p>';
                ?>
            </div>
            
            <div class="stat-card">
                <h3>Total Drivers</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'driver'");
                echo '<p>' . $stmt->fetchColumn() . '</p>';
                ?>
            </div>
            
            <div class="stat-card">
                <h3>Total Rides</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM rides");
                echo '<p>' . $stmt->fetchColumn() . '</p>';
                ?>
            </div>
            
            <div class="stat-card">
                <h3>Today's Earnings</h3>
                <?php
                $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE DATE(payment_date) = CURDATE() AND status = 'completed'");
                $amount = $stmt->fetchColumn();
                echo '<p>₱' . number_format($amount ? $amount : 0, 2) . '</p>';
                ?>
            </div>
        </div>
        
        <section class="recent-activity">
            <h2>Recent Rides</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ride ID</th>
                        <th>User</th>
                        <th>Driver</th>
                        <th>Pickup</th>
                        <th>Dropoff</th>
                        <th>Fare</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT r.ride_id, u.full_name as user_name, d.user_id as driver_id, 
                                        du.full_name as driver_name, r.pickup_location, r.dropoff_location, 
                                        r.fare, r.status 
                                        FROM rides r 
                                        JOIN users u ON r.user_id = u.user_id 
                                        LEFT JOIN drivers d ON r.driver_id = d.driver_id 
                                        LEFT JOIN users du ON d.user_id = du.user_id 
                                        ORDER BY r.created_at DESC LIMIT 10");
                    while ($ride = $stmt->fetch()) {
                        echo '<tr>';
                        echo '<td>' . $ride['ride_id'] . '</td>';
                        echo '<td>' . htmlspecialchars($ride['user_name']) . '</td>';
                        echo '<td>' . ($ride['driver_name'] ? htmlspecialchars($ride['driver_name']) : 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($ride['pickup_location']) . '</td>';
                        echo '<td>' . htmlspecialchars($ride['dropoff_location']) . '</td>';
                        echo '<td>₱' . number_format($ride['fare'], 2) . '</td>';
                        echo '<td>' . ucfirst($ride['status']) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>