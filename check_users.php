<?php
require 'app/config/db.php';
$stmt = $pdo->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['role'] . ': ' . $row['count'] . "\n";
}
?>
