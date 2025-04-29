<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Ride Hailing Service</title>
    <link rel="stylesheet" href="<?php echo isset($base_url) ? $base_url : ''; ?>../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a>KalTrike V2</a>
            </div>
            <nav>
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Logged-in User Navigation -->
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../admin/dashboard.php">Admin Dashboard</a></li>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../admin/users.php">Manage Users</a></li>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../admin/drivers.php">Manage Drivers</a></li>
                        <?php elseif ($_SESSION['user_type'] === 'driver'): ?>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../driver/dashboard.php">Driver Dashboard</a></li>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../driver/earnings.php">My Earnings</a></li>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../driver/profile.php">My Profile</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../user/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../user/book.php">Book a Ride</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>../logout.php">Logout</a></li>
                    <?php else: ?>
                        <!-- Guest Navigation -->
                        <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>./index.php">Home</a></li>
                        <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>./register.php">Register</a></li>
                        <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>./login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">