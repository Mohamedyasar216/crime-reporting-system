<?php
require_once 'app/config/db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        $colName = $column['Field'];
        $stmt = $pdo->query("SELECT `$colName` FROM `$table` WHERE `$colName` REGEXP '[\\x{0900}-\\x{097F}]'");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "Table: $table | Column: $colName | Value: " . $row[$colName] . "\n";
            }
        }
    }
}
?>
