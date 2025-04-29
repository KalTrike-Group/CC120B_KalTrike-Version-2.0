<?php
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Get report parameters
$report_type = $_GET['report_type'] ?? 'rides';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Generate reports
if ($report_type === 'rides') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_rides, 
                          SUM(fare) as total_earnings,
                          AVG(fare) as avg_fare,
                          status 
                          FROM rides 
                          WHERE DATE(requested_time) BETWEEN ? AND ?
                          GROUP BY status");
    $stmt->execute([$date_from, $date_to]);
    $ride_stats = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT DATE(requested_time) as ride_date, 
                          COUNT(*) as ride_count,
                          SUM(fare) as daily_earnings
                          FROM rides 
                          WHERE DATE(requested_time) BETWEEN ? AND ?
                          GROUP BY DATE(requested_time)
                          ORDER BY ride_date");
    $stmt->execute([$date_from, $date_to]);
    $daily_stats = $stmt->fetchAll();
} elseif ($report_type === 'feedback') {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating,
                          COUNT(*) as feedback_count,
                          rating 
                          FROM feedback 
                          WHERE DATE(created_at) BETWEEN ? AND ?
                          GROUP BY rating");
    $stmt->execute([$date_from, $date_to]);
    $feedback_stats = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT d.driver_id, u.full_name as driver_name,
                          AVG(f.rating) as avg_rating,
                          COUNT(f.feedback_id) as feedback_count
                          FROM feedback f
                          JOIN drivers d ON f.driver_id = d.driver_id
                          JOIN users u ON d.user_id = u.user_id
                          WHERE DATE(f.created_at) BETWEEN ? AND ?
                          GROUP BY d.driver_id
                          ORDER BY avg_rating DESC");
    $stmt->execute([$date_from, $date_to]);
    $driver_feedback = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>System Reports</h1>
        
        <div class="report-filters">
            <form method="GET" class="filter-form">
                <div class="form-group-inline">
                    <label for="report_type">Report Type:</label>
                    <select id="report_type" name="report_type" onchange="this.form.submit()">
                        <option value="rides" <?php echo $report_type === 'rides' ? 'selected' : ''; ?>>Rides Report</option>
                        <option value="feedback" <?php echo $report_type === 'feedback' ? 'selected' : ''; ?>>Feedback Report</option>
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
                
                <button type="submit" class="btn btn-secondary">Generate</button>
                <button type="button" onclick="window.print()" class="btn btn-secondary">Print</button>
            </form>
        </div>
        
        <?php if ($report_type === 'rides'): ?>
            <div class="report-section">
                <h2>Rides Summary (<?php echo date('M j, Y', strtotime($date_from)); ?> to <?php echo date('M j, Y', strtotime($date_to)); ?>)</h2>
                
                <div class="stats-grid">
                    <?php
                    $total_rides = 0;
                    $total_earnings = 0;
                    
                    foreach ($ride_stats as $stat) {
                        $total_rides += $stat['total_rides'];
                        if ($stat['status'] === 'completed') {
                            $total_earnings += $stat['total_earnings'];
                        }
                    }
                    ?>
                    
                    <div class="stat-card">
                        <h3>Total Rides</h3>
                        <p><?php echo $total_rides; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Earnings</h3>
                        <p>₱<?php echo number_format($total_earnings, 2); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Average Fare</h3>
                        <p>₱<?php echo number_format($ride_stats[0]['avg_fare'] ?? 0, 2); ?></p>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="ridesChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <canvas id="earningsChart"></canvas>
                </div>
                
                <script>
                    // Rides by Status Chart
                    const ridesCtx = document.getElementById('ridesChart').getContext('2d');
                    const ridesChart = new Chart(ridesCtx, {
                        type: 'pie',
                        data: {
                            labels: <?php echo json_encode(array_column($ride_stats, 'status')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($ride_stats, 'total_rides')); ?>,
                                backgroundColor: [
                                    '#3498db',
                                    '#2ecc71',
                                    '#f1c40f',
                                    '#e74c3c',
                                    '#95a5a6'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Rides by Status'
                                }
                            }
                        }
                    });
                    
                    // Daily Earnings Chart
                    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
                    const earningsChart = new Chart(earningsCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_column($daily_stats, 'ride_date')); ?>,
                            datasets: [{
                                label: 'Daily Earnings',
                                data: <?php echo json_encode(array_column($daily_stats, 'daily_earnings')); ?>,
                                borderColor: '#2ecc71',
                                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Daily Earnings Trend'
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
        <?php elseif ($report_type === 'feedback'): ?>
            <div class="report-section">
                <h2>Feedback Summary (<?php echo date('M j, Y', strtotime($date_from)); ?> to <?php echo date('M j, Y', strtotime($date_to)); ?>)</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Feedback</h3>
                        <p><?php echo array_sum(array_column($feedback_stats, 'feedback_count')); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Average Rating</h3>
                        <p><?php echo number_format($feedback_stats[0]['avg_rating'] ?? 0, 1); ?> ★</p>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="feedbackChart"></canvas>
                </div>
                
                <h3>Driver Ratings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Average Rating</th>
                            <th>Feedback Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($driver_feedback as $driver): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($driver['driver_name']); ?></td>
                            <td><?php echo number_format($driver['avg_rating'], 1); ?> ★</td>
                            <td><?php echo $driver['feedback_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <script>
                    // Feedback Distribution Chart
                    const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
                    const feedbackChart = new Chart(feedbackCtx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($feedback_stats, 'rating')); ?>,
                            datasets: [{
                                label: 'Feedback Count',
                                data: <?php echo json_encode(array_column($feedback_stats, 'feedback_count')); ?>,
                                backgroundColor: '#3498db'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Feedback Distribution by Rating'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    stepSize: 1
                                }
                            }
                        }
                    });
                </script>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>