<?php
// Fix Database Structure Script
// Run this to ensure your database matches the unified schema

require_once 'app/config/db.php';

echo "<h3>CRS Database Fixer...</h3>";

try {
    // 1. Check if 'role' column exists in 'users' table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $hasRole = $stmt->fetch();

    if ($hasRole) {
        echo "✅ 'role' column already exists.<br>";
    } else {
        echo "⚠️ 'role' column MISSING. Applying Schema Updates...<br>";

        // Simplest way: Drop old tables and recreate
        // (Since this is dev environment, data loss is acceptable for correct structure)
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $tables = ['users', 'admins', 'police', 'crimes', 'evidence', 'crime_updates'];
        
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "🗑️ Dropped table `$table` (if existed)<br>";
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Execute unified schema
        $schemaSql = file_get_contents('database/schema.sql');
        $pdo->exec($schemaSql);
        echo "✅ Re-created tables from schema.sql<br>";

        // Execute seed
        $seedSql = file_get_contents('database/seed.sql');
        try {
            $pdo->exec($seedSql);
            echo "✅ Seed data inserted.<br>";
        } catch (PDOException $e) {
            echo "ℹ️ Seed info: " . $e->getMessage() . "<br>";
        }
    }
    
    // 2. Extra verification for Mobile column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'mobile'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN mobile VARCHAR(15) DEFAULT NULL");
        echo "✅ Added missing 'mobile' column.<br>";
    }

    echo "<h3>🎉 Fix Complete!</h3>";
    echo "<a href='public/register.php'>Go to Registration</a> | <a href='public/login.php'>Go to Login</a>";

} catch (PDOException $e) {
    die("❌ Fix Error: " . $e->getMessage());
}
?>
