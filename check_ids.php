<?php
require 'app/config/db.php';
$ids = [1,2,3,4,5,6,7,8,9,10,43,44,45,46,47,50,51,52,53,54,55,56,57,58,59];
$stmt = $pdo->prepare("SELECT id, full_name, role, email FROM users WHERE id = ?");
foreach($ids as $id) {
    $stmt->execute([$id]);
    if($row = $stmt->fetch()) {
        echo "ID {$row['id']}: {$row['full_name']} ({$row['role']}) - {$row['email']}\n";
    } else {
        echo "ID {$id}: NOT FOUND\n";
    }
}
?>
