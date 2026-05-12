<?php
require_once 'app/config/db.php';
$stmt = $pdo->query("SELECT name FROM districts");
$districts = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($districts);
?>
