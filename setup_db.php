<?php
// Auto-Setup Script for Database
// Run this file once to setup the database if you can't access phpMyAdmin

require_once 'app/config/db.php';

try {
    // 1. Connect without DB name first to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>CRS Database Setup...</h3>";

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo "✅ Database '$db_name' checked/created.<br>";
    
    // 3. Select Database
    $pdo->exec("USE `$db_name`");
    
    // 4. Read SQL Files
    $schemaSql = file_get_contents('database/schema.sql');
    $seedSql = file_get_contents('database/seed.sql');
    
    // 5. Execute Schema
    // Remove USE command from string if it conflicts (though it shouldn't) or just execute
    $pdo->exec($schemaSql);
    echo "✅ Tables created successfully from schema.sql.<br>";
    
    // 6. Execute Seed (Handle potential duplicates gracefully)
    try {
        $pdo->exec($seedSql);
        echo "✅ Seed data inserted successfully.<br>";
    } catch (PDOException $e) {
        if(strpos($e->getMessage(), 'Duplicate entry') !== false) {
             echo "⚠️ Seed data already exists (Duplicate entry skipped).<br>";
        } else {
            throw $e;
        }
    }
    
    echo "<h3>🎉 Setup Complete! You can now login.</h3>";
    echo "<a href='public/login.php'>Go to Login Page</a>";

} catch (PDOException $e) {
    die("❌ Setup Error: " . $e->getMessage());
}
