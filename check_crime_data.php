<?php
require_once 'app/config/db.php';
$stmt = $pdo->query("SELECT area, landmark, district FROM crimes");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Dist: " . $row['district'] . " | Area: " . $row['area'] . " | Landmark: " . $row['landmark'] . "\n";
}
?>
