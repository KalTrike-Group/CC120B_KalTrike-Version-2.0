<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isDriver()) {
    header("Location: ../index.php");
    exit();
}

// Get driver details
$stmt = $pdo->prepare("SELECT d.* FROM drivers d WHERE d.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$driver = $stmt->fetch();

// Get earnings summary
$stmt = $pdo->prepare("SELECT 
                       COUNT(*) as total_rides,
                       SUM(r.fare) as total_earnings,
                       AVG(r.fare) as avg_fare,
                       COUNT(f.feedback_id) as feedback_count,
                       AVG(f.rating) as avg_rating
                       FROM rides r
                       LEFT JOIN feedback f ON r.ride_id = f.ride_id
                       WHERE r.driver_id = ? AND r.status = 'completed'");
$stmt->execute([$driver['driver_id']]);
$summary = $stmt->fetch();

// Get recent completed rides
$stmt = $pdo->prepare("SELECT r.*, u.full_name as passenger_name 
                      FROM rides r
                      JOIN users u ON r.user_id = u.user_id
                      WHERE r.driver_id = ? AND r.status = 'completed'
                      ORDER BY r.end_time DESC
                      LIMIT 10");
$stmt->execute([$driver['driver_id']]);
$rides = $stmt->fetchAll();

// Get weekly earnings
$weekly_earnings = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(fare), 0) as earnings 
                          FROM rides 
                          WHERE driver_id = ? AND status = 'completed' AND DATE(end_time) = ?");
    $stmt->execute([$driver['driver_id'], $date]);
    $weekly_earnings[$date] = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - My Earnings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>My Earnings</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Rides</h3>
                <p><?php echo $summary['total_rides'] ?? 0; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Total Earnings</h3>
                <p>₱<?php echo number_format($summary['total_earnings'] ?? 0, 2); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Average Fare</h3>
                <p>₱<?php echo number_format($summary['avg_fare'] ?? 0, 2); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Average Rating</h3>
                <p><?php echo number_format($summary['avg_rating'] ?? 0, 1); ?> ★</p>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="earningsChart"></canvas>
        </div>
        
        <h2>Recent Rides</h2>
        <table>
            <thead>
                <tr>
                    <th>Ride ID</th>
                    <th>Passenger</th>
                    <th>Date</th>
                    <th>Fare</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rides as $ride): ?>
                <tr>
                    <td><?php echo $ride['ride_id']; ?></td>
                    <td><?php echo htmlspecialchars($ride['passenger_name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($ride['end_time'])); ?></td>
                    <td>₱<?php echo number_format($ride['fare'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script>
            // Weekly Earnings Chart
            const ctx = document.getElementById('earningsChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($weekly_earnings)); ?>,
                    datasets: [{
                        label: 'Daily Earnings',
                        data: <?php echo json_encode(array_values($weekly_earnings)); ?>,
                        backgroundColor: '#3498db'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Last 7 Days Earnings'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>