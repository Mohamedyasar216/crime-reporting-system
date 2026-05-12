<?php
require_once 'app/config/db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "Checking table: $table\n";
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        foreach ($row as $col => $val) {
            if ($val && preg_match('/[\x{0900}-\x{097F}]/u', $val)) {
                echo "FOUND in $table.$col: $val\n";
            }
        }
    }
}
?>
