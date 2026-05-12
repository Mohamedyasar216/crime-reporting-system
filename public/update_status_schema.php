<?php
require_once __DIR__ . '/../app/config/db.php';

try {
    echo "Updating Status Enum...<br>";
    
    // We include 'Assigned' to maintain compatibility with the auto-assignment logic
    $sql = "ALTER TABLE crimes MODIFY COLUMN status ENUM(
        'Pending',
        'Assigned',
        'Investigation Ongoing',
        'Action Taken',
        'Investigation Completed',
        'Case Filed in Court',
        'Case Closed'
    ) DEFAULT 'Pending'";

    $pdo->exec($sql);
    echo "Success! Database schema updated to support new statuses.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
