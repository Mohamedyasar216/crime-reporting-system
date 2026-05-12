<?php
session_start();
require_once '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    // If anonymous, we store NULL for user_id to protect identity
    $user_id = $is_anonymous ? NULL : $_SESSION['user_id'];
    $reporter_id_for_log = $_SESSION['user_id']; // For system audit log (still track who did it, but crime record is clean)
    
    $crime_type = $_POST['crime_type'];
    $description = htmlspecialchars($_POST['description']);
    $incident_date = $_POST['datetime'];
    // Address Details
    $landmark = htmlspecialchars($_POST['landmark']);
    $area = htmlspecialchars($_POST['area']);
    $district_id = $_POST['district_id'];
    $state = htmlspecialchars($_POST['state']);

    // Fetch District Name for Legacy Column
    $dNameStmt = $pdo->prepare("SELECT name FROM districts WHERE id = ?");
    $dNameStmt->execute([$district_id]);
    $district_name = $dNameStmt->fetchColumn();
    
    // GIS
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : NULL;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : NULL;

    try {
        $pdo->beginTransaction();

        // 0. Auto-Assign Logic: Find SP of this District ID
        $spStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'police' AND rank = 'SP' AND district_id = ? LIMIT 1");
        $spStmt->execute([$district_id]);
        $sp = $spStmt->fetch();
        
        $assigned_to = $sp ? $sp['id'] : NULL;
        $status = $sp ? 'Assigned' : 'Pending';

        // 1. Insert Crime Report with district_id and is_anonymous
        $stmt = $pdo->prepare("INSERT INTO crimes (user_id, crime_type, description, incident_date, landmark, area, district, district_id, state, latitude, longitude, status, assigned_to, is_anonymous) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $crime_type, $description, $incident_date, $landmark, $area, $district_name, $district_id, $state, $latitude, $longitude, $status, $assigned_to, $is_anonymous]);
        
        $crime_id = $pdo->lastInsertId();

        // 2. Insert Initial Update Log
        $remarks = $sp ? "Auto-assigned to SP of $district_name" : "Pending assignment (No SP found for this district)";
        $updateStmt = $pdo->prepare("INSERT INTO crime_updates (crime_id, status_from, status_to, remarks) VALUES (?, ?, ?, ?)");
        $updateStmt->execute([$crime_id, 'New', $status, $remarks]);

        // System Audit Log
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_action = $is_anonymous ? "Anonymous Report Filed" : "Report Filed";
        $logStmt->execute([$reporter_id_for_log, $log_action, "Case ID: $crime_id, Type: $crime_type"]);

        // 3. Handle File Uploads (Evidence)
        if (isset($_FILES['evidence']) && count($_FILES['evidence']['name']) > 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'video/mp4', 'audio/mpeg'];
            $upload_dir = '../../public/uploads/';
            
            // Create dir if not exists
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            for ($i = 0; $i < count($_FILES['evidence']['name']); $i++) {
                $file_name = $_FILES['evidence']['name'][$i];
                $file_tmp = $_FILES['evidence']['tmp_name'][$i];
                $file_type = $_FILES['evidence']['type'][$i];
                
                if (!empty($file_name)) {
                    $unique_name = time() . '_' . $file_name;
                    $target_file = $upload_dir . $unique_name;
                    
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $evidenceStmt = $pdo->prepare("INSERT INTO evidence (crime_id, file_path, file_type) VALUES (?, ?, ?)");
                        $stmtType = (strpos($file_type, 'image') !== false) ? 'image' : ((strpos($file_type, 'video') !== false) ? 'video' : 'other');
                        $evidenceStmt->execute([$crime_id, 'uploads/' . $unique_name, $stmtType]);
                    }
                }
            }
        }

        $pdo->commit();
        
        // Success
        header("Location: ../../public/report_success.php?id=" . $crime_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error reporting crime: " . $e->getMessage());
    }
}
?>
