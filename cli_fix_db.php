<?php
// CLI Database Fixer
require_once 'app/config/db.php';

echo "Starting Database Fix...\n";

try {
    // Drop old tables to force clean state
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['users', 'admins', 'police', 'crimes', 'evidence', 'crime_updates'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped table: $table\n";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Read full schema
    $schemaSql = file_get_contents('database/schema.sql');
    
    // Execute multiple statements
    // PDO::exec might fail on multiple statements depending on config, but usually works if not emulated
    // Split by semi-colon to be safe
    $statements = explode(';', $schemaSql);
    foreach ($statements as $stmt) {
        if (trim($stmt) != '') {
            $pdo->exec($stmt);
        }
    }
    echo "Schema imported successfully.\n";

    // Seed data
    $seedSql = file_get_contents('database/seed.sql');
    $seedStatements = explode(';', $seedSql);
    foreach ($seedStatements as $stmt) {
        if (trim($stmt) != '') {
            try {
                $pdo->exec($stmt);
            } catch (Exception $e) {
                // Ignore seed errors (duplicates etc)
            }
        }
    }
    echo "Seed data imported.\n";
    echo "DONE. Database is now compatible.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
