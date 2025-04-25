<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isDriver()) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id = $_POST['ride_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $driver_id = $_POST['driver_id'] ?? 0;
    
    try {
        switch ($action) {
            case 'accept_ride':
                // Assign driver to ride
                $stmt = $pdo->prepare("UPDATE rides SET 
                    driver_id = ?, 
                    status = 'accepted' 
                    WHERE ride_id = ? AND status = 'pending'");
                $stmt->execute([$driver_id, $ride_id]);
                
                // Update driver status
                $stmt = $pdo->prepare("UPDATE drivers SET status = 'unavailable' WHERE driver_id = ?");
                $stmt->execute([$driver_id]);
                
                // Notify passenger (in real app, would use websockets/push)
                break;
                
            case 'reject_ride':
                // Just update status (other drivers can still accept)
                $stmt = $pdo->prepare("UPDATE rides SET 
                    status = 'rejected' 
                    WHERE ride_id = ? AND status = 'pending'");
                $stmt->execute([$ride_id]);
                break;
                
                case 'start_ride':
                    // Verify the ride exists and is assigned to this driver
                    $stmt = $pdo->prepare("SELECT status FROM rides WHERE ride_id = ? AND driver_id = ?");
                    $stmt->execute([$ride_id, $driver_id]);
                    $ride = $stmt->fetch();
                    
                    if (!$ride) {
                        $_SESSION['error'] = "Ride not found or not assigned to you";
                        break;
                    }
                    
                    if ($ride['status'] !== 'accepted') {
                        $_SESSION['error'] = "Can only start rides that are in 'accepted' status";
                        break;
                    }
                    
                    // Update the ride status
                    $stmt = $pdo->prepare("UPDATE rides SET status = 'ongoing', start_time = NOW() WHERE ride_id = ?");
                    if ($stmt->execute([$ride_id])) {
                        $_SESSION['success'] = "Ride started successfully";
                        
                        // Update driver status to 'on_ride'
                        $stmt = $pdo->prepare("UPDATE drivers SET status = 'on_ride' WHERE driver_id = ?");
                        $stmt->execute([$driver_id]);
                    } else {
                        $_SESSION['error'] = "Failed to start ride";
                    }
                    break;
                
                    case 'complete_ride':
                        // Verify the ride exists and is assigned to this driver
                        $stmt = $pdo->prepare("SELECT status FROM rides WHERE ride_id = ? AND driver_id = ?");
                        $stmt->execute([$ride_id, $driver_id]);
                        $ride = $stmt->fetch();
                        
                        if (!$ride) {
                            $_SESSION['error'] = "Ride not found or not assigned to you";
                            break;
                        }
                        
                        if ($ride['status'] !== 'ongoing') {
                            $_SESSION['error'] = "Can only complete rides that are in 'ongoing' status";
                            break;
                        }
                        
                        try {
                            $pdo->beginTransaction();
                            
                            // 1. Update ride status to completed
                            $stmt = $pdo->prepare("UPDATE rides SET 
                                status = 'completed', 
                                end_time = NOW() 
                                WHERE ride_id = ?");
                            $stmt->execute([$ride_id]);
                            
                            // 2. Update driver status back to available
                            $stmt = $pdo->prepare("UPDATE drivers SET status = 'available' WHERE driver_id = ?");
                            $stmt->execute([$driver_id]);
                            
                            // 3. Create payment record
                            $stmt = $pdo->prepare("SELECT fare FROM rides WHERE ride_id = ?");
                            $stmt->execute([$ride_id]);
                            $fare = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare("INSERT INTO payments 
                                (ride_id, amount, payment_method, status) 
                                VALUES (?, ?, 'cash', 'completed')");
                            $stmt->execute([$ride_id, $fare]);
                            
                            $pdo->commit();
                            $_SESSION['success'] = "Ride completed successfully";
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            $_SESSION['error'] = "Failed to complete ride: " . $e->getMessage();
                        }
                        break;
        }
        
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        die("Error processing ride: " . $e->getMessage());
    }
}

header("Location: dashboard.php");
?>