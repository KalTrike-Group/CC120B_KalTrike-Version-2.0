<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    redirectBasedOnUserType();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Ride Hailing Service</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
        <div class="logo" style="color: white !important;">KalTrike V2</div>
            <nav>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="hero">
        <div class="container">
            <h1>Your Reliable Ride Hailing Service</h1>
            <p>Book a ride in minutes and get to your destination safely and comfortably</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary">Sign Up Now</a>
                <a href="login.php" class="btn btn-secondary">Driver Login</a>
            </div>
        </div>
    </div>

    <div class="features">
        <div class="container">
            <h2>Why Choose KalTrike V2?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>Fast Pickup</h3>
                    <p>Our drivers are always nearby to pick you up quickly</p>
                </div>
                <div class="feature-card">
                    <h3>Affordable Rates</h3>
                    <p>Competitive pricing with no hidden charges</p>
                </div>
                <div class="feature-card">
                    <h3>Safe Rides</h3>
                    <p>All drivers are verified and vehicles are inspected</p>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
        <?php include 'includes/footer.php'; ?>
        </div>
    </footer>
</body>
</html>