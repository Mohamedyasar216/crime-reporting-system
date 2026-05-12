<?php
require_once __DIR__ . '/../app/config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS public_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    target_role ENUM('all', 'citizen', 'police') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $pdo->exec($sql);
    echo "✅ Public notices table created successfully.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
