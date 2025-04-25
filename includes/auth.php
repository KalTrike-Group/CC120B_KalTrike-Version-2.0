<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isDriver() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'driver';
}

function isUser() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectBasedOnUserType() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: admin/dashboard.php");
        } elseif (isDriver()) {
            // Get driver_id if not already set
            if (!isset($_SESSION['driver_id'])) {
                global $pdo;
                $stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $driver = $stmt->fetch();
                if ($driver) {
                    $_SESSION['driver_id'] = $driver['driver_id'];
                }
            }
            header("Location: driver/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit();
    }
}
?>