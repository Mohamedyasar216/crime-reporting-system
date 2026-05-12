<?php
/**
 * PHP Script to Seed Police Force for all 38 districts
 */

require_once __DIR__ . '/../app/config/db.php';

$districts = [
    1 => 'Ariyalur', 2 => 'Chengalpattu', 3 => 'Chennai', 4 => 'Coimbatore', 5 => 'Cuddalore',
    6 => 'Dharmapuri', 7 => 'Dindigul', 8 => 'Erode', 9 => 'Kallakurichi', 10 => 'Kanchipuram',
    11 => 'Kanyakumari', 12 => 'Karur', 13 => 'Krishnagiri', 14 => 'Madurai', 15 => 'Mayiladuthurai',
    16 => 'Nagapattinam', 17 => 'Namakkal', 18 => 'Nilgiris', 19 => 'Perambalur', 20 => 'Pudukkottai',
    21 => 'Ramanathapuram', 22 => 'Ranipet', 23 => 'Salem', 24 => 'Sivaganga', 25 => 'Tenkasi',
    26 => 'Thanjavur', 27 => 'Theni', 28 => 'Thoothukudi', 29 => 'Tiruchirappalli', 30 => 'Tirunelveli',
    31 => 'Tirupathur', 32 => 'Tiruppur', 33 => 'Tiruvallur', 34 => 'Tiruvannamalai', 35 => 'Tiruvarur',
    36 => 'Vellore', 37 => 'Viluppuram', 38 => 'Virudhunagar'
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Seeding police officers for all districts...\n";

    $stmt = $pdo->prepare("INSERT INTO users 
        (full_name, email, password, role, district, rank, badge_number, police_status, district_id) 
        VALUES (?, ?, ?, 'police', ?, 'SP', ?, 'Active', ?)
        ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), rank = VALUES(rank), district_id = VALUES(district_id)");

    foreach ($districts as $id => $name) {
        $lowerName = str_replace(' ', '', strtolower($name));
        $email = "sp_$lowerName@gmail.com";
        $rawPassword = $lowerName . "123";
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
        $fullName = "SP " . $name;
        $badgeNumber = "TN" . sprintf("%02d", $id) . "SP";

        $stmt->execute([
            $fullName,
            $email,
            $hashedPassword,
            $name,
            $badgeNumber,
            $id
        ]);
        echo "✅ Seeded Police Officer for: $name (Email: $email)\n";
    }

    echo "\n🎉 Finished seeding 38 police officers.\n";

} catch (PDOException $e) {
    die("❌ Error during seeding: " . $e->getMessage());
}
