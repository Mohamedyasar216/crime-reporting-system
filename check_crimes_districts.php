<?php
require_once 'app/config/db.php';
$stmt = $pdo->query("SELECT DISTINCT district FROM crimes");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "District: " . $row['district'] . "\n";
}
?>
