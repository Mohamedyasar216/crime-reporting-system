<?php
require_once __DIR__ . '/../app/config/db.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
