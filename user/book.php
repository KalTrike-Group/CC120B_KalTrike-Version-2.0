<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isUser()) {
    header("Location: ../index.php");
    exit();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup = trim($_POST['pickup_location'] ?? '');
    $dropoff = trim($_POST['dropoff_location'] ?? '');
    $preferred_time = $_POST['preferred_time'] ?? date('Y-m-d H:i:s');
    
    // Validate inputs
    if (empty($pickup) || empty($dropoff)) {
        $error = 'Pickup and dropoff locations are required';
    } elseif (strlen($pickup) < 5 || strlen($dropoff) < 5) {
        $error = 'Locations must be at least 5 characters';
    } else {
        try {
            // Calculate fare (simple fixed fare for now)
            $fare = 50.00; // Define the fare variable
            
            // Find available drivers
            $stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE status = 'available' ORDER BY RAND() LIMIT 1");
            $stmt->execute();
            $driver = $stmt->fetch(); // Define the driver variable
            
            if (!$driver) {
                throw new Exception("No drivers available at the moment. Please try again later.");
            }
            
            // Create ride record
            $stmt = $pdo->prepare("INSERT INTO rides 
                (user_id, driver_id, pickup_location, dropoff_location, requested_time, fare, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            
            if ($stmt->execute([
                $_SESSION['user_id'],
                $driver['driver_id'], // Now $driver is defined
                $pickup,
                $dropoff,
                $preferred_time,
                $fare // Now $fare is defined
            ])) {
                // Update driver status
                $stmt = $pdo->prepare("UPDATE drivers SET status = 'unavailable' WHERE driver_id = ?");
                $stmt->execute([$driver['driver_id']]);
                
                header("Location: dashboard.php?ride_booked=1");
                exit();
            } else {
                throw new Exception("Failed to book ride. Please try again.");
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Booking error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Ride | KalTrike V2</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .booking-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Book a New Ride</h1>
        
        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="booking-form">
            <div class="form-group">
                <label for="pickup_location">Pickup Location</label>
                <input type="text" id="pickup_location" name="pickup_location" required 
                       placeholder="Enter your pickup address" value="<?php echo htmlspecialchars($_POST['pickup_location'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="dropoff_location">Drop-off Location</label>
                <input type="text" id="dropoff_location" name="dropoff_location" required 
                       placeholder="Enter your destination" value="<?php echo htmlspecialchars($_POST['dropoff_location'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="preferred_time">Preferred Time (optional)</label>
                <input type="datetime-local" id="preferred_time" name="preferred_time" 
                       value="<?php echo htmlspecialchars($_POST['preferred_time'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn-primary">Book Ride Now</button>
        </form>
    </div>
</body>
</html>