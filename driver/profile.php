<?php
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
if (!isDriver()) {
    header("Location: ../index.php");
    exit();
}

// Get driver details
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email, u.contact_number 
                      FROM drivers d 
                      JOIN users u ON d.user_id = u.user_id 
                      WHERE d.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$driver = $stmt->fetch();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact_number = $_POST['contact_number'] ?? '';
        $license_number = $_POST['license_number'] ?? '';
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        $vehicle_plate = $_POST['vehicle_plate'] ?? '';
        
        if (empty($full_name) || empty($email) || empty($contact_number) || 
            empty($license_number) || empty($vehicle_type) || empty($vehicle_plate)) {
            $error = 'All fields are required';
        } else {
            // Update user details
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, contact_number = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $contact_number, $_SESSION['user_id']]);
            
            // Update driver details
            $stmt = $pdo->prepare("UPDATE drivers SET 
                                  license_number = ?, 
                                  vehicle_type = ?, 
                                  vehicle_plate = ? 
                                  WHERE user_id = ?");
            $stmt->execute([$license_number, $vehicle_type, $vehicle_plate, $_SESSION['user_id']]);
            
            $_SESSION['full_name'] = $full_name;
            $success = 'Profile updated successfully';
            
            // Refresh driver data
            $stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email, u.contact_number 
                                  FROM drivers d 
                                  JOIN users u ON d.user_id = u.user_id 
                                  WHERE d.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $driver = $stmt->fetch();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $success = 'Password changed successfully';
                } else {
                    $error = 'Failed to change password';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KalTrike V2 - My Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>My Profile</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profile-tabs">
            <button class="tab-btn active" onclick="openTab('profile')">Profile Information</button>
            <button class="tab-btn" onclick="openTab('password')">Change Password</button>
        </div>
        
        <div id="profile" class="tab-content" style="display: block;">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <h3>Personal Information</h3>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($driver['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($driver['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($driver['contact_number']); ?>" required>
                </div>
                
                <h3>Driver Information</h3>
                <div class="form-group">
                    <label for="license_number">Driver's License Number</label>
                    <input type="text" id="license_number" name="license_number" value="<?php echo htmlspecialchars($driver['license_number']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type</label>
                    <input type="text" id="vehicle_type" name="vehicle_type" value="<?php echo htmlspecialchars($driver['vehicle_type']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_plate">Vehicle Plate Number</label>
                    <input type="text" id="vehicle_plate" name="vehicle_plate" value="<?php echo htmlspecialchars($driver['vehicle_plate']); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div id="password" class="tab-content" style="display: none;">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> KalTrike V2. All rights reserved.</p>
    </footer>
    
    <script>
        function openTab(tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].style.display = "none";
            }
            
            const tabButtons = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }
            
            document.getElementById(tabName).style.display = "block";
            event.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>