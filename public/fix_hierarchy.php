<?php
require_once __DIR__ . '/../app/config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Create districts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS districts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Created districts table.\n";

    // 2. Insert Districts
    $districts = [
        "Ariyalur", "Chengalpattu", "Chennai", "Coimbatore", "Cuddalore", "Dharmapuri", 
        "Dindigul", "Erode", "Kallakurichi", "Kanchipuram", "Kanyakumari", "Karur", 
        "Krishnagiri", "Madurai", "Mayiladuthurai", "Nagapattinam", "Namakkal", "Nilgiris", 
        "Perambalur", "Pudukkottai", "Ramanathapuram", "Ranipet", "Salem", "Sivaganga", 
        "Tenkasi", "Thanjavur", "Theni", "Thoothukudi", "Tiruchirappalli", "Tirunelveli", 
        "Tirupathur", "Tiruppur", "Tiruvallur", "Tiruvannamalai", "Tiruvarur", "Vellore", 
        "Viluppuram", "Virudhunagar"
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO districts (name) VALUES (?)");
    foreach ($districts as $d) {
        $stmt->execute([$d]);
    }
    echo "✅ Inserted 38 districts.\n";

    // 3. Add district_id to users
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN district_id INT DEFAULT NULL, ADD CONSTRAINT fk_user_district FOREIGN KEY (district_id) REFERENCES districts(id)");
        echo "✅ Added district_id to users.\n";
    } catch (PDOException $e) {
        echo "ℹ️ district_id in users might already exist.\n";
    }

    // 4. Add district_id to crimes
    try {
        $pdo->exec("ALTER TABLE crimes ADD COLUMN district_id INT DEFAULT NULL, ADD CONSTRAINT fk_crime_district FOREIGN KEY (district_id) REFERENCES districts(id)");
        echo "✅ Added district_id to crimes.\n";
    } catch (PDOException $e) {
        echo "ℹ️ district_id in crimes might already exist.\n";
    }

    // 5. Update users mapping (optional but good for existing data)
    $pdo->exec("UPDATE users u JOIN districts d ON u.district = d.name SET u.district_id = d.id");
    
    // 6. Update crimes mapping
    $pdo->exec("UPDATE crimes c JOIN districts d ON c.district = d.name SET c.district_id = d.id");

    $pdo->commit();
    echo "🚀 Hierarchy normalization complete.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
