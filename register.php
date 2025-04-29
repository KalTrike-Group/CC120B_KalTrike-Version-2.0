<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    // Additional fields for drivers
    $license_number = trim($_POST['license_number'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $vehicle_plate = trim($_POST['vehicle_plate'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($contact_number) || empty($password)) {
        $errors[] = "All fields are required";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($user_type === 'driver' && (empty($license_number) || empty($vehicle_type) || empty($vehicle_plate))) {
        $errors[] = "Driver license, vehicle type and plate number are required";
    }

    // Validate vehicle type
    if ($user_type === 'driver' && !empty($vehicle_type)) {
        $allowed_vehicle_types = ['Tricycle', 'Ombak'];
        if (!in_array($vehicle_type, $allowed_vehicle_types)) {
            $errors[] = "Invalid vehicle type selected";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Check if email or contact number already exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR contact_number = ?");
            $stmt->execute([$email, $contact_number]);
            
            if ($stmt->fetch()) {
                $errors[] = "Email or contact number already registered";
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, contact_number, password, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $contact_number, $hashed_password, $user_type]);
                $user_id = $pdo->lastInsertId();
                
                // If driver, create driver record
                if ($user_type === 'driver') {
                    $stmt = $pdo->prepare("INSERT INTO drivers (user_id, license_number, vehicle_type, vehicle_plate, status) VALUES (?, ?, ?, ?, 'available')");
                    $stmt->execute([$user_id, $license_number, $vehicle_type, $vehicle_plate]);
                }
                
                $pdo->commit();
                
                $success = true;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | KalTrike V2</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="auth-card">
            <h1>Register</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Registration successful! You can now <a href="login.php">login</a>.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($contact_number ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="user_type">Register as</label>
                    <select id="user_type" name="user_type" onchange="toggleDriverFields()">
                        <option value="user" <?php echo ($user_type ?? 'user') === 'user' ? 'selected' : ''; ?>>Passenger</option>
                        <option value="driver" <?php echo ($user_type ?? 'user') === 'driver' ? 'selected' : ''; ?>>Driver</option>
                    </select>
                </div>
                
                <div id="driver-fields" style="display: <?php echo ($user_type ?? 'user') === 'driver' ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label for="license_number">Driver's License Number</label>
                        <input type="text" id="license_number" name="license_number" value="<?php echo htmlspecialchars($license_number ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type</label>
                        <select id="vehicle_type" name="vehicle_type">
                            <option value="">Select Vehicle Type</option>
                            <option value="Tricycle" <?php echo ($vehicle_type ?? '') === 'Tricycle' ? 'selected' : ''; ?>>Tricycle</option>
                            <option value="Ombak" <?php echo ($vehicle_type ?? '') === 'Ombak' ? 'selected' : ''; ?>>Ombak</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_plate">Vehicle Plate Number</label>
                        <input type="text" id="vehicle_plate" name="vehicle_plate" value="<?php echo htmlspecialchars($vehicle_plate ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
                
                <div class="auth-links">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleDriverFields() {
            const driverFields = document.getElementById('driver-fields');
            const userType = document.getElementById('user_type').value;
            
            if (userType === 'driver') {
                driverFields.style.display = 'block';
                document.getElementById('license_number').required = true;
                document.getElementById('vehicle_type').required = true;
                document.getElementById('vehicle_plate').required = true;
            } else {
                driverFields.style.display = 'none';
                document.getElementById('license_number').required = false;
                document.getElementById('vehicle_type').required = false;
                document.getElementById('vehicle_plate').required = false;
            }
        }
    </script>
</body>
</html>
