<?php
/**
 * Database Connection Configuration
 * Using PDO for secure database interactions
 */

$host = 'localhost';
$port = '3306';
$db_name = 'crime_reporting_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // In production, log this error instead of showing it
    die("Database Connection Failed: " . $e->getMessage() . 
        "<br>Hint: Make sure you have imported the 'database/schema.sql' file into your MySQL server.");
}
