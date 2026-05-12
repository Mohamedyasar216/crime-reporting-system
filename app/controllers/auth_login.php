<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // 0. Gmail Format Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
        echo "<script>alert('Error: Please enter a correct Gmail ID.'); window.history.back();</script>";
        exit;
    }

    // 1. Fetch User (Allow login by Email)
    // Note: User requirements said "Email / Mobile". I'll check both.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR mobile = ?");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        // 2. Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        // Audit Log
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$user['id'], "User Login", "Login successful via " . $email]);

        // 3. Redirect based on Role
        if ($user['role'] == 'admin') {
            header("Location: ../../public/admin_dashboard.php");
        } elseif ($user['role'] == 'police') {
            header("Location: ../../public/police_dashboard.php");
        } else {
            // Citizen
            header("Location: ../../public/dashboard.php");
        }
        exit;

    } else {
        // Login Failed
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (NULL, ?, ?)");
        $logStmt->execute(["Login Failed", "Attempted email: " . $email]);

        $error = "Invalid credentials!";
        // Redirect back with error (in real app, use flash data)
        echo "<script>alert('Invalid Email or Password'); window.location.href='../../public/login.php';</script>";
    }
}
?>
