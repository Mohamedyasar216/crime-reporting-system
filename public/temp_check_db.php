<?php
require_once '../app/config/db.php';
$latestDistrict = $pdo->query("SELECT district FROM crimes ORDER BY id DESC LIMIT 1")->fetchColumn();
echo "Latest District: " . $latestDistrict . "\n";
$allDistricts = $pdo->query("SELECT district, COUNT(*) as count FROM crimes GROUP BY district")->fetchAll(PDO::FETCH_ASSOC);
print_r($allDistricts);
