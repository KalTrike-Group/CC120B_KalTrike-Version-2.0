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

if (!$driver) {
    die("Driver record not found");
}

// Handle driver status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $newStatus = $driver['status'] === 'available' ? 'unavailable' : 'available';
        $stmt = $pdo->prepare("UPDATE drivers SET status = ? WHERE driver_id = ?");
        $stmt->execute([$newStatus, $driver['driver_id']]);
        header("Location: dashboard.php");
        exit();
    }
}

// Refresh driver data after potential update
$stmt = $pdo->prepare("SELECT d.* FROM drivers d WHERE d.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$driver = $stmt->fetch();

// Get current ride
$currentRide = null;
$stmt = $pdo->prepare("SELECT r.*, u.full_name as passenger_name 
                      FROM rides r
                      JOIN users u ON r.user_id = u.user_id
                      WHERE r.driver_id = ? AND r.status IN ('accepted', 'ongoing')");
$stmt->execute([$driver['driver_id']]);
$currentRide = $stmt->fetch();

// Get new ride requests (only if driver is available)
$rideRequests = [];
if ($driver['status'] === 'available') {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as passenger_name 
                          FROM rides r
                          JOIN users u ON r.user_id = u.user_id
                          WHERE r.status = 'pending'
                          ORDER BY r.created_at DESC");
    $stmt->execute();
    $rideRequests = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard | KalTrike V2</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .driver-status {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .ride-card, .ride-request {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        .btn-success:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        
        <div class="driver-status">
            <h2>Current Status: <?php echo ucfirst($driver['status']); ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="toggle_status">
                <button type="submit" class="btn btn-primary">
                    <?php echo $driver['status'] === 'available' ? 'Go Offline' : 'Go Online'; ?>
                </button>
            </form>
        </div>
        
        <section class="current-ride">
            <h2>Current Ride</h2>
            <?php if ($currentRide): ?>
                <div class="ride-card">
                    <h3>Ride #<?php echo $currentRide['ride_id']; ?></h3>
                    <p>Passenger: <?php echo htmlspecialchars($currentRide['passenger_name']); ?></p>
                    <p>From: <?php echo htmlspecialchars($currentRide['pickup_location']); ?></p>
                    <p>To: <?php echo htmlspecialchars($currentRide['dropoff_location']); ?></p>
                    <p>Fare: ₱<?php echo number_format($currentRide['fare'], 2); ?></p>
                    
                    <form method="POST" action="process_ride.php">
                        <input type="hidden" name="ride_id" value="<?php echo $currentRide['ride_id']; ?>">
                        <?php if ($currentRide && $currentRide['status'] === 'accepted'): ?>
                                <form method="POST" action="process_ride.php">
                                    <input type="hidden" name="ride_id" value="<?php echo $currentRide['ride_id']; ?>">
                                    <input type="hidden" name="driver_id" value="<?php echo $_SESSION['driver_id']; ?>">
                                    <input type="hidden" name="action" value="start_ride">
                                    <button type="submit" class="btn btn-primary">Start Ride</button>
                                </form>
                            <?php elseif ($currentRide && $currentRide['status'] === 'ongoing'): ?>
                                <form method="POST" action="process_ride.php" onsubmit="return confirm('Complete this ride?');">
                                    <input type="hidden" name="ride_id" value="<?php echo $currentRide['ride_id']; ?>">
                                    <input type="hidden" name="driver_id" value="<?php echo $_SESSION['driver_id']; ?>">
                                    <input type="hidden" name="action" value="complete_ride">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check-circle"></i> Complete Ride
                                    </button>
                                </form>
                            <?php endif; ?>
                    </form>
                </div>
            <?php else: ?>
                <p>You have no active rides at the moment.</p>
            <?php endif; ?>
        </section>
        
        <section class="ride-requests">
            <h2>New Ride Requests</h2>
            <?php if ($driver['status'] === 'available'): ?>
                <?php if (count($rideRequests) > 0): ?>
                    <?php foreach ($rideRequests as $request): ?>
                        <div class="ride-request">
                            <h3>Ride #<?php echo $request['ride_id']; ?></h3>
                            <p>Passenger: <?php echo htmlspecialchars($request['passenger_name']); ?></p>
                            <p>From: <?php echo htmlspecialchars($request['pickup_location']); ?></p>
                            <p>To: <?php echo htmlspecialchars($request['dropoff_location']); ?></p>
                            <p>Fare: ₱<?php echo number_format($request['fare'], 2); ?></p>
                            
                            <form method="POST" action="process_ride.php">
                                <input type="hidden" name="ride_id" value="<?php echo $request['ride_id']; ?>">
                                <input type="hidden" name="driver_id" value="<?php echo $driver['driver_id']; ?>">
                                <input type="hidden" name="action" value="accept_ride">
                                <button type="submit" class="btn btn-primary">Accept Ride</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No new ride requests at the moment.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>You need to be available to receive ride requests.</p>
            <?php endif; ?>
        </section>
        <section class="notifications">
            <h2>Ride Requests</h2>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rides WHERE status = 'pending'");
            $stmt->execute();
            $pending_requests = $stmt->fetchColumn();
            ?>
            <p>You have <?php echo $pending_requests; ?> new ride requests.</p>
            <a href="../driver/notification.php" class="btn btn-primary">View Requests</a>
        </section>
    </div>
    
</body>
</html>