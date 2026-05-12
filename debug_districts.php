<?php
require_once 'app/config/db.php';
$names = ['Tirupathur', 'Tiruvarur', 'Kanyakumari', 'Tiruvallur'];
foreach($names as $n) {
    $stmt = $pdo->prepare("SELECT name FROM districts WHERE name LIKE ?");
    $stmt->execute(['%' . $n . '%']);
    $res = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo $n . ": " . implode(',', $res) . "\n";
}
