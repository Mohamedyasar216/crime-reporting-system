<?php
// scripts/seed_tamilnadu_police.php
// This script automatically generates 1 DGP and 38 SP accounts for Tamil Nadu

require_once __DIR__ . '/../app/config/db.php';

echo "<h3>👮 Generating Tamil Nadu Police Force Hierarchy...</h3>";

// 0. Ensure Schema Compatibility (Add 'rank' if missing)
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN rank VARCHAR(50) DEFAULT NULL");
} catch (PDOException $e) {
    // Column likely exists
}

$districts = [
    "Ariyalur", "Chengalpattu", "Chennai", "Coimbatore", "Cuddalore", "Dharmapuri", 
    "Dindigul", "Erode", "Kallakurichi", "Kanchipuram", "Kanyakumari", "Karur", 
    "Krishnagiri", "Madurai", "Mayiladuthurai", "Nagapattinam", "Namakkal", "Nilgiris", 
    "Perambalur", "Pudukkottai", "Ramanathapuram", "Ranipet", "Salem", "Sivaganga", 
    "Tenkasi", "Thanjavur", "Theni", "Thoothukudi", "Tiruchirappalli", "Tirunelveli", 
    "Tirupathur", "Tiruppur", "Tiruvallur", "Tiruvannamalai", "Tiruvarur", "Vellore", 
    "Viluppuram", "Virudhunagar"
];

$passwordHash = password_hash('password', PASSWORD_DEFAULT); // Default password for all

try {
    $pdo->beginTransaction();

    // 1. Create DGP (Head of State)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE rank = 'DGP'");
    $stmt->execute();
    if(!$stmt->fetch()) {
        $sql = "INSERT INTO users (full_name, email, password, mobile, role, rank, district) 
                VALUES (?, ?, ?, ?, 'police', 'DGP', 'Tamil Nadu')";
        $pdo->prepare($sql)->execute([
            'Director General of Police', 
            'dgp@tnpolice.gov.in', 
            $passwordHash, 
            '9999999999'
        ]);
        echo "✅ Created DGP Account (dgp@tnpolice.gov.in)<br>";
    } else {
        echo "ℹ️ DGP Account already exists.<br>";
    }

    // 2. Create SPs for each District
    $sqlSP = "INSERT INTO users (full_name, email, password, mobile, role, rank, district, district_id) 
              VALUES (?, ?, ?, ?, 'police', 'SP', ?, ?)";
    $stmtSP = $pdo->prepare($sqlSP);

    foreach ($districts as $index => $dist) {
        $email = "sp_" . strtolower(str_replace(' ', '', $dist)) . "@tnpolice.gov.in";
        
        // Fetch District ID
        $dStmt = $pdo->prepare("SELECT id FROM districts WHERE name = ?");
        $dStmt->execute([$dist]);
        $district_id = $dStmt->fetchColumn();

        // Check if exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        $existing_user = $check->fetch();
        
        if(!$existing_user) {
            $stmtSP->execute([
                "SP " . $dist,
                $email,
                $passwordHash,
                "98765432" . str_pad($index, 2, '0', STR_PAD_LEFT), // Mock phone
                $dist,
                $district_id
            ]);
            echo "✅ Created SP Account for <b>$dist</b> ($email)<br>";
        } else {
            // Update existing SP with district_id
            $pdo->prepare("UPDATE users SET district_id = ? WHERE id = ?")->execute([$district_id, $existing_user['id']]);
            echo "🔄 Updated SP Account for <b>$dist</b> with district_id.<br>";
        }
    }

    $pdo->commit();
    echo "<h3 style='color:green'>🎉 Setup Complete! All 38 Districts have an SP.</h3>";
    echo "Default Password for all provided accounts is: <b>password</b>";
    echo "\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h3 style='color:red'>❌ Error: " . $e->getMessage() . "</h3>";
    echo "\n";
}
?>
