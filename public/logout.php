<?php
session_start();
require_once '../app/config/db.php';

if(isset($_SESSION['user_id'])) {
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], "User Logout", "User logged out successfully."]);
}

session_destroy();
header("Location: login");
exit;
?>
