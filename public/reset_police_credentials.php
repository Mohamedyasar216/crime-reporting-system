<?php
// public/reset_police_credentials.php
// Updates all SP accounts to use the pattern:
// Email: sp_district@gmail.com
// Password: district123

require_once __DIR__ . '/../app/config/db.php';

echo "<h3>👮 Updating Police Credentials...</h3>";

$districts = [
    "Ariyalur", "Chengalpattu", "Chennai", "Coimbatore", "Cuddalore", "Dharmapuri", 
    "Dindigul", "Erode", "Kallakurichi", "Kanchipuram", "Kanyakumari", "Karur", 
    "Krishnagiri", "Madurai", "Mayiladuthurai", "Nagapattinam", "Namakkal", "Nilgiris", 
    "Perambalur", "Pudukkottai", "Ramanathapuram", "Ranipet", "Salem", "Sivaganga", 
    "Tenkasi", "Thanjavur", "Theni", "Thoothukudi", "Tiruchirappalli", "Tirunelveli", 
    "Tirupathur", "Tiruppur", "Tiruvallur", "Tiruvannamalai", "Tiruvarur", "Vellore", 
    "Viluppuram", "Virudhunagar"
];

?>
<table border="1" style="border-collapse: collapse; width: 100%;">
    <tr>
        <th style="padding: 8px;">District</th>
        <th style="padding: 8px;">New Email</th>
        <th style="padding: 8px;">New Password</th>
        <th style="padding: 8px;">Status</th>
    </tr>

<?php
foreach ($districts as $dist) {
    // 1. Generate Pattern
    $slug = strtolower(str_replace(' ', '', $dist));
    $new_email = "sp_{$slug}@gmail.com";
    $plain_password = "{$slug}123";
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    // 2. Check if SP exists for this district
    $stmt = $pdo->prepare("SELECT id FROM users WHERE district = ? AND rank = 'SP'");
    $stmt->execute([$dist]);
    $user = $stmt->fetch();

    $status = "";

    if ($user) {
        // UPDATE existing
        $update = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
        $update->execute([$new_email, $hashed_password, $user['id']]);
        $status = "<span style='color:blue'>Updated</span>";
    } else {
        // CREATE new
        $insert = $pdo->prepare("INSERT INTO users (full_name, email, password, mobile, role, rank, district) VALUES (?, ?, ?, '9999999999', 'police', 'SP', ?)");
        $insert->execute(["SP $dist", $new_email, $hashed_password, $dist]);
        $status = "<span style='color:green'>Created</span>";
    }

    echo "<tr>
            <td style='padding: 8px;'>$dist</td>
            <td style='padding: 8px;'>$new_email</td>
            <td style='padding: 8px;'>$plain_password</td>
            <td style='padding: 8px;'>$status</td>
          </tr>";
}
?>
</table>
<br>
<h3>Done! All 38 Districts have been updated to the new pattern.</h3>
