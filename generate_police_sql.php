<?php
// Script to generate seed_police.sql
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

$sql = "-- Seed Police Force for all 38 Districts\n";
$sql .= "USE `crime_reporting_db`;\n\n";
$sql .= "INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `district`, `badge_number`, `rank`, `police_status`, `district_id`) VALUES\n";

$rows = [];
foreach ($districts as $id => $name) {
    $lowerName = str_replace(' ', '', strtolower($name));
    $email = "sp_$lowerName@gmail.com";
    $rawPassword = $lowerName . "123";
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
    $fullName = "SP " . $name;
    $badgeNumber = "TN" . sprintf("%02d", $id) . "SP";
    
    $rows[] = "('$fullName', '$email', '$hashedPassword', 'police', '$name', '$badgeNumber', 'SP', 'Active', $id)";
}

$sql .= implode(",\n", $rows);
$sql .= "\nON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password = VALUES(password), district_id = VALUES(district_id);";

file_put_contents('database/seed_police.sql', $sql);
echo "SQL File generated: database/seed_police.sql\n";
?>
