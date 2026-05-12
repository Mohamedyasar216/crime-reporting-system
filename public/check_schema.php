<?php
require_once __DIR__ . '/../app/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    $stmt = $pdo->query("DESCRIBE crimes");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
