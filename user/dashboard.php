<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isUser()) {
    header("Location: ../index.php");
    exit();
}

// Handle success/error messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
        <?php endif; ?>
        
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        
        <section class="upcoming-rides">
            <h2><i class="fas fa-calendar-alt"></i> Upcoming Rides</h2>
            <div class="rides-grid">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM rides WHERE user_id = ? AND status IN ('pending', 'accepted', 'ongoing') ORDER BY requested_time ASC");
                $stmt->execute([$_SESSION['user_id']]);
                $rides = $stmt->fetchAll();
                
                if (count($rides) > 0) {
                    foreach ($rides as $ride) {
                        echo '<div class="ride-card card">';
                        echo '<div class="card-header">';
                        echo '<h3>Ride #' . $ride['ride_id'] . '</h3>';
                        echo '<span class="status-badge ' . $ride['status'] . '">' . ucfirst($ride['status']) . '</span>';
                        echo '</div>';
                        echo '<div class="card-body">';
                        echo '<div class="ride-detail"><i class="fas fa-map-marker-alt"></i> <strong>From:</strong> ' . htmlspecialchars($ride['pickup_location']) . '</div>';
                        echo '<div class="ride-detail"><i class="fas fa-flag-checkered"></i> <strong>To:</strong> ' . htmlspecialchars($ride['dropoff_location']) . '</div>';
                        
                        if ($ride['driver_id']) {
                            $driver_stmt = $pdo->prepare("SELECT u.full_name, d.vehicle_type, d.vehicle_plate FROM drivers d JOIN users u ON d.user_id = u.user_id WHERE d.driver_id = ?");
                            $driver_stmt->execute([$ride['driver_id']]);
                            $driver = $driver_stmt->fetch();
                            echo '<div class="ride-detail"><i class="fas fa-user"></i> <strong>Driver:</strong> ' . htmlspecialchars($driver['full_name']) . '</div>';
                            echo '<div class="ride-detail"><i class="fas fa-car"></i> <strong>Vehicle:</strong> ' . htmlspecialchars($driver['vehicle_type']) . ' (' . htmlspecialchars($driver['vehicle_plate']) . ')</div>';
                        }
                        echo '</div>';
                        echo '<div class="card-footer">';
                        if (in_array($ride['status'], ['pending', 'accepted'])) {
                            echo '<button class="btn btn-danger" onclick="showCancelModal(' . $ride['ride_id'] . ')">';
                            echo '<i class="fas fa-times"></i> Cancel Ride';
                            echo '</button>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<i class="fas fa-calendar-times"></i>';
                    echo '<p>You have no upcoming rides</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </section>
        
        <!-- Cancel Ride Modal -->
        <div id="cancelModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Ride Cancellation</h3>
                <p>Are you sure you want to cancel this ride? This action cannot be undone.</p>
                <p id="rideDetails"></p>
                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="closeModal()">Go Back</button>
                    <button id="confirmCancel" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </div>
        </div>
        
        <section class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-buttons">
                <a href="book.php" class="btn btn-primary"><i class="fas fa-plus"></i> Book a New Ride</a>
                <a href="history.php" class="btn btn-secondary"><i class="fas fa-history"></i> Ride History</a>
                <a href="profile.php" class="btn btn-secondary"><i class="fas fa-user-cog"></i> Profile</a>
                <a href="feedback.php" class="btn btn-secondary"><i class="fas fa-comment-alt"></i> Feedback</a>
            </div>
        </section>
    </div>
    
    <script>
        let currentRideId = null;
        
        function showCancelModal(rideId) {
            currentRideId = rideId;
            document.getElementById('rideDetails').textContent = `You are about to cancel Ride #${rideId}`;
            document.getElementById('cancelModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('cancelModal').style.display = 'none';
            currentRideId = null;
        }
        
        document.getElementById('confirmCancel').addEventListener('click', function() {
            if (currentRideId) {
                window.location.href = `cancel_ride.php?ride_id=${currentRideId}`;
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('cancelModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>