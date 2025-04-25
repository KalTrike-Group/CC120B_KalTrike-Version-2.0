<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

if (!isUser()) {
    header("Location: ../index.php");
    exit();
}

// Check if ride_id is provided
if (isset($_GET['ride_id'])) {
    $ride_id = $_GET['ride_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify the ride belongs to the user and is cancellable
    $stmt = $pdo->prepare("SELECT status FROM rides WHERE ride_id = ? AND user_id = ?");
    $stmt->execute([$ride_id, $user_id]);
    $ride = $stmt->fetch();
    
    if ($ride) {
        $cancellable_statuses = ['pending', 'accepted'];
        if (in_array($ride['status'], $cancellable_statuses)) {
            // Update ride status to cancelled
            $update_stmt = $pdo->prepare("UPDATE rides SET status = 'cancelled', cancelled_time = NOW() WHERE ride_id = ?");
            $update_stmt->execute([$ride_id]);
            
            $_SESSION['success_message'] = "Ride #$ride_id has been cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Ride #$ride_id cannot be cancelled as it's already " . $ride['status'] . ".";
        }
    } else {
        $_SESSION['error_message'] = "Ride #$ride_id not found or doesn't belong to you.";
    }
    
    header("Location: dashboard.php");
    exit();
}

// If no ride_id provided, redirect to dashboard
header("Location: dashboard.php");
exit();
?>