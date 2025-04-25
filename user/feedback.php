<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();
if (!isUser()) {
    header("Location: ../index.php");
    exit();
}

$ride_id = $_GET['ride_id'] ?? 0;
$success = false;
$error = '';

// Get ride details
$stmt = $pdo->prepare("SELECT r.*, u.full_name as driver_name 
                      FROM rides r 
                      JOIN drivers d ON r.driver_id = d.driver_id 
                      JOIN users u ON d.user_id = u.user_id 
                      WHERE r.ride_id = ? AND r.user_id = ? AND r.status = 'completed'");
$stmt->execute([$ride_id, $_SESSION['user_id']]);
$ride = $stmt->fetch();

if (!$ride) {
    header("Location: history.php");
    exit();
}

// Check if feedback already given
$stmt = $pdo->prepare("SELECT * FROM feedback WHERE ride_id = ?");
$stmt->execute([$ride_id]);
$existing_feedback = $stmt->fetch();

if ($existing_feedback) {
    header("Location: history.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $comments = $_POST['comments'] ?? '';
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please provide a rating between 1 and 5';
    } else {
        $stmt = $pdo->prepare("INSERT INTO feedback 
                              (ride_id, user_id, driver_id, rating, comments) 
                              VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$ride_id, $_SESSION['user_id'], $ride['driver_id'], $rating, $comments])) {
            $success = true;
            
            // Update driver's average rating
            updateDriverRating($ride['driver_id']);
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    }
}

function updateDriverRating($driver_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM feedback WHERE driver_id = ?");
    $stmt->execute([$driver_id]);
    $avg = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("UPDATE drivers SET rating = ? WHERE driver_id = ?");
    $stmt->execute([$avg, $driver_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Give Feedback</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styles for feedback page */
        .feedback-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .ride-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 2rem;
            border-left: 4px solid #4e73df;
        }
        
        .ride-details p {
            margin: 0.5rem 0;
            color: #4a4a4a;
        }
        
        .rating-stars {
            display: flex;
            direction: rtl;
            justify-content: center;
            margin: 1.5rem 0;
        }
        
        .rating-stars input {
            display: none;
        }
        
        .rating-stars label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            padding: 0 5px;
            transition: color 0.2s;
        }
        
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #f8d64e;
        }
        
        .rating-stars input:checked + label {
            color: #f8d64e;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        textarea:focus {
            border-color: #4e73df;
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .thank-you-message {
            text-align: center;
            padding: 2rem;
        }
        
        .thank-you-message h2 {
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="feedback-container">
            <h1>Feedback for Ride #<?php echo $ride['ride_id']; ?></h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="thank-you-message">
                    <h2>Thank You!</h2>
                    <p>Your feedback has been submitted successfully.</p>
                    <p>We appreciate your time and valuable input.</p>
                    <a href="history.php" class="btn btn-primary">Back to Ride History</a>
                </div>
            <?php else: ?>
                <div class="ride-details">
                    <h3>Ride Details</h3>
                    <p><strong>Driver:</strong> <?php echo htmlspecialchars($ride['driver_name']); ?></p>
                    <p><strong>Pickup:</strong> <?php echo htmlspecialchars($ride['pickup_location']); ?></p>
                    <p><strong>Dropoff:</strong> <?php echo htmlspecialchars($ride['dropoff_location']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('M j, Y h:i A', strtotime($ride['requested_time'])); ?></p>
                    <p><strong>Fare:</strong> ₱<?php echo number_format($ride['fare'], 2); ?></p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label><strong>How would you rate your experience?</strong></label>
                        <div class="rating-stars">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5">★</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4">★</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3">★</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2">★</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments"><strong>Additional Comments</strong> <small>(Optional)</small></label>
                        <textarea id="comments" name="comments" rows="4" placeholder="Tell us about your experience..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        <a href="history.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
    <?php include '../includes/footer.php'; ?>
    </footer>
</body>
</html>