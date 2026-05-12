<?php
require 'app/config/db.php';
$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `mobile`, `role`, `address`, `state`, `district`, `pincode`, `badge_number`, `rank`, `specialization`, `police_status`, `created_at`, `district_id`) VALUES\n";
$rows = [];
foreach($users as $u) {
    $vals = [];
    foreach([
        'id', 'full_name', 'email', 'password', 'mobile', 'role', 'address', 'state', 'district', 'pincode', 'badge_number', 'rank', 'specialization', 'police_status', 'created_at', 'district_id'
    ] as $col) {
        $val = $u[$col];
        if ($val === null) {
            $vals[] = "NULL";
        } elseif (is_numeric($val) && $col !== 'mobile' && $col !== 'pincode' && $col !== 'badge_number') {
            $vals[] = $val;
        } else {
            $vals[] = "'" . str_replace("'", "''", $val) . "'";
        }
    }
    $rows[] = "(" . implode(", ", $vals) . ")";
}
echo implode(",\n", $rows) . ";\n";
?>
