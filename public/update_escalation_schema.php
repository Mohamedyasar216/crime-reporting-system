<?php
require_once __DIR__ . '/../app/config/db.php';

try {
    $pdo->exec("ALTER TABLE crimes ADD COLUMN is_escalated TINYINT(1) DEFAULT 0");
    echo "✅ Added is_escalated column to crimes table.\n";
} catch (PDOException $e) {
    echo "ℹ️ Column might already exist: " . $e->getMessage() . "\n";
}
