<?php
require 'app/config/db.php';
$stmt = $pdo->query("SELECT id, full_name, role FROM users ORDER BY role, id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['role'] . ' | ' . $row['id'] . ' | ' . $row['full_name'] . "\n";
}
?>
