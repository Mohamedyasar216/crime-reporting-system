<?php
require_once '../app/config/db.php';
$latestAssigned = $pdo->query("SELECT district, status FROM crimes WHERE status = 'Assigned' ORDER BY updated_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Latest Assigned: "; print_r($latestAssigned);
$latestCrime = $pdo->query("SELECT district FROM crimes ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "\nLatest Crime: "; print_r($latestCrime);
