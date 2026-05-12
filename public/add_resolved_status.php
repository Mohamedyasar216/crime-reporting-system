<?php
require_once __DIR__ . '/../app/config/db.php';

try {
    // Add 'Resolved' to the ENUM list
    $sql = "ALTER TABLE crimes MODIFY COLUMN status ENUM('New', 'Pending', 'Assigned', 'Investigation Ongoing', 'Action Taken', 'Investigation Completed', 'Case Filed in Court', 'Case Closed', 'Resolved') DEFAULT 'New'";
    $pdo->exec($sql);
    echo "Successfully added 'Resolved' to status enum.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
