<?php
// Use absolute path to require database configuration
require_once __DIR__ . '/../config/database.php';

function registerUser($full_name, $email, $contact_number, $password, $user_type) {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, contact_number, password, user_type) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$full_name, $email, $contact_number, $hashed_password, $user_type]);
}

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
    }
    return false;
}

function hasGivenFeedback($ride_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE ride_id = ?");
        $stmt->execute([$ride_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking feedback: " . $e->getMessage());
        return false;
    }
}
