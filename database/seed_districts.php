<?php
/**
 * PHP Script to Seed Districts into the database
 */

require_once __DIR__ . '/../app/config/db.php';

$districts = [
    'Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore',
    'Dharmapuri', 'Dindigul', 'Erode', 'Kallakurichi', 'Kanchipuram',
    'Kanyakumari', 'Karur', 'Krishnagiri', 'Madurai', 'Mayiladuthurai',
    'Nagapattinam', 'Namakkal', 'Nilgiris', 'Perambalur', 'Pudukkottai',
    'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga', 'Tenkasi',
    'Thanjavur', 'Theni', 'Thoothukudi', 'Tiruchirappalli', 'Tirunelveli',
    'Tirupathur', 'Tiruppur', 'Tiruvallur', 'Tiruvannamalai', 'Tiruvarur',
    'Vellore', 'Viluppuram', 'Virudhunagar'
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Seeding districts into database...\n";

    $stmt = $pdo->prepare("INSERT INTO districts (name) VALUES (?) ON DUPLICATE KEY UPDATE name = name");

    foreach ($districts as $district) {
        $stmt->execute([$district]);
        echo "✅ Seeded: $district\n";
    }

    echo "\n🎉 Finished seeding 38 districts.\n";

} catch (PDOException $e) {
    die("❌ Error during seeding: " . $e->getMessage());
}
