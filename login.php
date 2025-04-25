<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';  // Add this line

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // After successful login (around line 8-9 in your login.php)
if (loginUser($email, $password)) {
    // For drivers, set driver_id in session
    if ($_SESSION['user_type'] === 'driver') {
        $stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $driver = $stmt->fetch();
        if ($driver) {
            $_SESSION['driver_id'] = $driver['driver_id'];
        }
    }
    redirectBasedOnUserType();
}
    if (loginUser($email, $password)) {
        redirectBasedOnUserType();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">KalTrike V2</div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container auth-container">
        <div class="auth-card">
            <h1>Login</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>