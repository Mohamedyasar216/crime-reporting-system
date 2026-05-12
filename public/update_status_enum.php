<?php
require_once __DIR__ . '/../app/config/db.php';

echo "<h3>Updating Enum Values for 'status' column in 'crimes' table...</h3>";

try {
    // Modify the enum to include new status options
    $sql = "ALTER TABLE crimes MODIFY COLUMN status ENUM(
        'New', 
        'Pending', 
        'Assigned', 
        'Investigation Ongoing', 
        'Action Taken', 
        'Investigation Completed', 
        'Filed in Court', 
        'Case Closed', 
        'Resolved', 
        'Rejected'
    ) DEFAULT 'New'";
    
    $pdo->exec($sql);
    echo "<h4 style='color: green;'>Successfully updated status ENUM values.</h4>";
} catch (PDOException $e) {
    echo "<h4 style='color: red;'>Error updating table: " . $e->getMessage() . "</h4>";
}
?>
