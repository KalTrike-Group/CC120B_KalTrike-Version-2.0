<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isDriver()) {
    header("Location: ../index.php");
    exit();
}

// Get driver ID
$stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$driver = $stmt->fetch();

// Get pending ride requests for this driver's area
$stmt = $pdo->prepare("SELECT r.*, u.full_name as passenger_name 
                      FROM rides r
                      JOIN users u ON r.user_id = u.user_id
                      WHERE r.status = 'pending'
                      ORDER BY r.requested_time DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Requests | KalTrike V2</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .notification-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-accept {
            background-color: #2ecc71;
            color: white;
        }
        .btn-reject {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>New Ride Requests</h1>
        
        <?php if (count($requests) > 0): ?>
            <?php foreach ($requests as $request): ?>
                <div class="notification-card">
                    <h3>Ride Request #<?php echo $request['ride_id']; ?></h3>
                    <p><strong>Passenger:</strong> <?php echo htmlspecialchars($request['passenger_name']); ?></p>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($request['pickup_location']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($request['dropoff_location']); ?></p>
                    <p><strong>Requested Time:</strong> <?php echo date('M j, Y h:i A', strtotime($request['requested_time'])); ?></p>
                    <p><strong>Fare:</strong> ₱<?php echo number_format($request['fare'], 2); ?></p>
                    
                    <div class="actions">
                        <form method="POST" action="process_ride.php">
                            <input type="hidden" name="ride_id" value="<?php echo $request['ride_id']; ?>">
                            <input type="hidden" name="driver_id" value="<?php echo $driver['driver_id']; ?>">
                            <input type="hidden" name="action" value="accept_ride">
                            <button type="submit" class="btn btn-accept">Accept</button>
                        </form>
                        
                        <form method="POST" action="process_ride.php">
                            <input type="hidden" name="ride_id" value="<?php echo $request['ride_id']; ?>">
                            <input type="hidden" name="action" value="reject_ride">
                            <button type="submit" class="btn btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No new ride requests at the moment.</p>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>