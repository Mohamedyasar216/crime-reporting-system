<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize Input
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars(strip_tags($_POST['mobile'])); // Changed from 'phone' to 'mobile' to match DB/Req
    $address = htmlspecialchars(strip_tags($_POST['address']));
    $state = htmlspecialchars(strip_tags($_POST['state']));
    $district_id = $_POST['district_id'];
    $area = isset($_POST['area']) ? htmlspecialchars(strip_tags($_POST['area'])) : '';
    $pincode = isset($_POST['pincode']) ? htmlspecialchars(strip_tags($_POST['pincode'])) : '';

    // Fetch District Name for Legacy Column
    $dStmt = $pdo->prepare("SELECT name FROM districts WHERE id = ?");
    $dStmt->execute([$district_id]);
    $district_name = $dStmt->fetchColumn();

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Validation
    // Email Format & Gmail Requirement
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
        echo "<script>alert('Error: Please enter a correct Gmail ID (e.g., example@gmail.com)'); window.history.back();</script>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Error: Passwords do not match.'); window.history.back();</script>";
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Error: This Gmail ID is already registered. Please login or use a different email.'); window.location.href='../../public/login.php';</script>";
        exit;
    }

    // Check if mobile already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE mobile = ?");
    $stmt->execute([$mobile]);
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Error: This Mobile number is already registered. Please use a different number or login.'); window.history.back();</script>";
        exit;
    }

    // 3. Hash Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert User (Role = 'citizen' by default)
    $sql = "INSERT INTO users (full_name, email, mobile, password, address, state, district, district_id, pincode, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'citizen')";
    // Note: 'area' and 'pincode' removed from form, so we use defaults/empty
    $pincode = '';
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $mobile, $hashed_password, $address, $state, $district_name, $district_id, $pincode]);
        $new_user_id = $pdo->lastInsertId();

        // System Audit Log
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$new_user_id, "User Registered", "Name: $name, Role: citizen"]);

        // 5. Success -> Redirect to Login
        $_SESSION['success_msg'] = "Registration successful! Please login.";
        header("Location: ../../public/login.php");
        exit;

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
