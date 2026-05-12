<?php
require_once 'app/config/db.php';
$stmt = $pdo->query("DESCRIBE districts");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
