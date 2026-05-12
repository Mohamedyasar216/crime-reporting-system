<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

if(!isset($_GET['id'])) die("Invalid Case ID");
$crime_id = $_GET['id'];

// Handle Assignment / Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['assign_police'])) {
        $pid = $_POST['police_id'];

        // Fetch Current Status for Log
        $curr = $pdo->prepare("SELECT status FROM crimes WHERE id = ?");
        $curr->execute([$crime_id]);
        $old_status = $curr->fetchColumn() ?: 'New';

        $pdo->prepare("UPDATE crimes SET assigned_to = ?, status = 'Assigned' WHERE id = ?")->execute([$pid, $crime_id]);
        
        // Log Update
        $pdo->prepare("INSERT INTO crime_updates (crime_id, status_from, status_to, updated_by, remarks) VALUES (?, ?, 'Assigned', ?, 'Case assigned to new officer.')")->execute([$crime_id, $old_status, $_SESSION['user_id']]);

        // System Audit Log
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], "Case Assigned", "Case ID: $crime_id, Police ID: $pid"]);

        $msg = "Case assigned successfully.";
    }
    if(isset($_POST['update_status'])) {
        $st = $_POST['status'];
        $rem = $_POST['remarks'];

        // Fetch Current Status for Log
        $curr = $pdo->prepare("SELECT status FROM crimes WHERE id = ?");
        $curr->execute([$crime_id]);
        $old_status = $curr->fetchColumn() ?: 'New';

         $pdo->prepare("UPDATE crimes SET status = ? WHERE id = ?")->execute([$st, $crime_id]);
         $pdo->prepare("INSERT INTO crime_updates (crime_id, status_from, status_to, updated_by, remarks) VALUES (?, ?, ?, ?, ?)")->execute([$crime_id, $old_status, $st, $_SESSION['user_id'], $rem]);

         // System Audit Log
         $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
         $logStmt->execute([$_SESSION['user_id'], "Case Status Updated", "Case ID: $crime_id, New Status: $st"]);

         $msg = "Case status updated.";
    }
}

// Fetch Crime Details
$stmt = $pdo->prepare("
    SELECT c.*, 
           u.full_name as reporter_name, u.mobile as reporter_mobile, u.email as reporter_email,
           p.full_name as police_name
    FROM crimes c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN users p ON c.assigned_to = p.id
    WHERE c.id = ?
");
$stmt->execute([$crime_id]);
$crime = $stmt->fetch();

if(!$crime) die("Case Not Found");

// Fetch Evidence
$media = $pdo->prepare("SELECT * FROM evidence WHERE crime_id = ?");
$media->execute([$crime_id]);
$evidence = $media->fetchAll();

// Fetch Available Police (for assignment) - Filter by District
$policeStmt = $pdo->prepare("SELECT * FROM users WHERE role = 'police' AND district = ?");
$policeStmt->execute([$crime['district']]);
$police_list = $policeStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - CRS Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root { 
            --sidebar-width: 240px; 
            --sidebar-collapsed-width: 70px;
            --color-mint: #69A481;
            --color-smoke: #E7EDEB;
            --color-claret: #7C1F31;
            --color-grey: #64748b;
            
            --bg-body: #f1f5f4;
            --sidebar-bg: #ffffff;
            --sidebar-text: #475569;
            --sidebar-hover-bg: #f8faf9;
            --sidebar-border: #e2e8f0;
            
            --grad-claret: linear-gradient(135deg, #7C1F31 0%, #a42a42 100%);
            --grad-mint: linear-gradient(135deg, #69A481 0%, #8bbba1 100%);
            --grad-mixed: linear-gradient(135deg, #7C1F31 0%, #69A481 100%);
        }
        
        body { background: var(--bg-body); font-family: 'Inter', sans-serif; }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1100;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--sidebar-border);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
            overflow: hidden;
            border-bottom: 1px solid #f1f5f9;
            color: var(--color-claret);
            letter-spacing: -0.5px;
        }

        .sidebar.collapsed .brand-text { display: none; }

        .nav-group { padding: 1.5rem 0.75rem; flex: 1; }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin-bottom: 0.3rem;
            white-space: nowrap;
            overflow: hidden;
            border: 1px solid transparent;
            font-size: 0.85rem;
        }

        .nav-item:hover {
            background: var(--sidebar-hover-bg);
            color: var(--color-mint);
            transform: translateX(3px);
        }

        .nav-item.active {
            background: var(--grad-mixed);
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(124, 31, 49, 0.2);
            border: none;
        }
        
        .nav-item i {
            min-width: 20px;
            font-size: 1rem;
            margin-right: 12px;
        }

        .sidebar.collapsed .nav-text { display: none; }
        .sidebar.collapsed .nav-item { padding: 0.85rem; justify-content: center; }
        .sidebar.collapsed .nav-item i { margin-right: 0; }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .collapse-btn {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .collapse-btn:hover { background: var(--color-smoke); color: var(--color-claret); }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            background: var(--bg-body);
            padding: 2.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 0;
        }

        /* Admin Banner (Police-like) */
        .admin-banner {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(0,0,0,0.03);
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .banner-icon {
            width: 80px; height: 80px; 
            background: var(--color-smoke); color: var(--color-claret);
            border-radius: 24px; display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; transform: rotate(-5deg);
            box-shadow: 0 8px 20px rgba(124, 31, 49, 0.1);
        }
        .banner-info h2 { margin: 0; font-size: 1.7rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px; }
        .banner-info p { margin: 0.5rem 0 0; font-size: 0.95rem; color: #64748b; font-weight: 500; }
        .tag {
            background: var(--color-claret); color: white; padding: 6px 14px; border-radius: 8px; 
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-right: 10px;
        }
        
        .btn-mixed {
            background: var(--grad-mixed) !important;
            color: white !important;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(124, 31, 49, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .btn-mixed:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(124, 31, 49, 0.3);
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Standardized Buttons */
        .btn-primary {
            background: var(--grad-mixed) !important;
            color: white !important;
            border: none;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(124, 31, 49, 0.2);
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
        }
        .btn-primary:hover {
            background: var(--btn-hover-gradient) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 54, 49, 0.4);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 2000;
            }
            .sidebar.active { transform: translateX(0); }
            .sidebar.collapsed { width: 280px; }
            .sidebar.collapsed .brand-text, .sidebar.collapsed .nav-text { display: block; }
            
            .admin-banner { 
                grid-template-columns: 1fr; 
                text-align: center; 
                padding: 1.5rem; 
                gap: 1.25rem; 
            }
            .banner-icon { margin: 0 auto; width: 60px; height: 60px; font-size: 1.8rem; }
            .banner-info h2 { font-size: 1.4rem; }
            .banner-info p { display: flex; flex-direction: column; align-items: center; gap: 8px; }
            .banner-info p span { margin-left: 0 !important; }
            .admin-banner div:last-child { text-align: center !important; }
            
            .top-bar { display: none !important; }
            .top-bar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 1.5rem; padding-top: 8rem !important; }
            
            .mobile-header {
                display: flex;
                position: fixed;
                top: 0; left: 0; right: 0;
                background: #ffffff !important;
                padding: 0 1.25rem;
                z-index: 3000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                justify-content: space-between;
                align-items: center;
                height: 64px;
                border-bottom: 2px solid var(--color-claret);
            }
            .mobile-header .page-title {
                font-weight: 800;
                color: var(--color-claret) !important;
                font-size: 1.1rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .sidebar { z-index: 4000; }
            .sidebar-overlay { z-index: 3500; }
            .sidebar-overlay.active { display: block; }
            .logout-btn-sidebar { 
                display: flex !important;
                background: #ef4444;
                color: #fff !important;
                margin: 1rem;
                padding: 0.8rem;
                border-radius: 10px;
                justify-content: center;
                align-items: center;
                gap: 10px;
                font-weight: 700;
                text-decoration: none;
                position: relative;
                z-index: 5000;
            }
            .sidebar { overflow-y: auto; }
            .nav-item.logout-link { display: none; }
        }
    </style>
</head>
<body>


    <div class="mobile-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <button id="sidebarToggle" style="background: #f1f5f9; border: none; width: 38px; height: 38px; border-radius: 8px; font-size: 1.1rem; color: var(--color-claret); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title">Case View</span>
        </div>
        <a href="admin_dashboard" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-halved" style="color: var(--color-claret);"></i>
            <span style="font-weight: 800; font-size: 0.9rem; color: var(--color-claret);">CRS</span>
        </a>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-shield-halved" style="color: var(--color-claret);"></i> 
                <span class="brand-text">CRS Admin</span>
            </div>
            
            <nav class="nav-group">
                <a href="admin_dashboard" class="nav-item">
                    <i class="fas fa-th-large"></i> 
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="admin_analytics" class="nav-item">
                    <i class="fas fa-chart-pie"></i> 
                    <span class="nav-text">Analytics</span>
                </a>
                <a href="admin_crimes" class="nav-item">
                    <i class="fas fa-file-alt"></i> 
                    <span class="nav-text">Complaints</span>
                </a>
                <a href="admin_police" class="nav-item">
                    <i class="fas fa-user-shield"></i> 
                    <span class="nav-text">Police Force</span>
                </a>
                <a href="admin_users" class="nav-item">
                    <i class="fas fa-users"></i> 
                    <span class="nav-text">Citizens</span>
                </a>
                <a href="admin_map" class="nav-item">
                    <i class="fas fa-map-marked-alt"></i> 
                    <span class="nav-text">GIS Map</span>
                </a>
                <a href="admin_logs" class="nav-item">
                    <i class="fas fa-history"></i> 
                    <span class="nav-text">System Logs</span>
                </a>
                <a href="admin_broadcast" class="nav-item">
                    <i class="fas fa-bullhorn"></i> 
                    <span class="nav-text">Broadcast</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout" class="nav-item logout-link" style="color: #ef4444; margin-bottom: 0.5rem; justify-content: center;">
                    <i class="fas fa-sign-out-alt"></i> 
                    <span class="nav-text">Logout</span>
                </a>
                <button class="collapse-btn" id="collapseBtn">
                    <i class="fas fa-angle-double-left" id="collapseIcon"></i>
                </button>
            </div>
        </aside>

        <main class="main-content" id="mainContent">
            <!-- Header Bar -->
            <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; background: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button id="desktopToggle" style="background: var(--color-smoke); border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; color: var(--color-claret); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Case Investigation</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Detailed Report Analysis</p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_crimes" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: var(--color-mint) !important; box-shadow: 0 4px 12px rgba(105, 164, 129, 0.2);">
                        <i class="fas fa-arrow-left"></i> BACK TO COMPLAINTS
                    </a>
                    <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: #ef4444 !important; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">
                        <i class="fas fa-sign-out-alt"></i> LOGOUT
                    </a>
                </div>
            </div>

            <?php if(isset($msg)) echo "<div style='padding: 1rem; background: #dcfce7; color: #166534; margin-bottom: 1rem; border-radius: 6px;'>$msg</div>"; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                
                <!-- Left Column: Details -->
                <div>
                    <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 2rem;">
                        <h2 style="margin-bottom: 0.5rem; color: var(--primary-green);">Case #<?php echo $crime['id']; ?>: <?php echo htmlspecialchars($crime['crime_type']); ?></h2>
                        <span style="background: #f3f4f6; padding: 4px 10px; border-radius: 4px; font-size: 0.9rem; font-weight: 600;">
                            Current Status: <?php echo $crime['status']; ?>
                        </span>

                        <div style="margin-top: 2rem;">
                            <h4 style="color: #64748b; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; font-weight: 700;">Incident Description</h4>
                            <p style="line-height: 1.6; margin-top: 0.5rem; color: #334155;"><?php echo nl2br(htmlspecialchars($crime['description'])); ?></p>
                        </div>

                        <div style="margin-top: 2rem;">
                            <h4 style="color: #64748b; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; font-weight: 700;">Location Details</h4>
                            <p style="margin-top: 0.5rem; color: #334155;">
                                <strong>Address:</strong> <?php echo htmlspecialchars($crime['landmark']); ?><br>
                                <strong>Area:</strong> <?php echo htmlspecialchars($crime['area']); ?><br>
                                <strong>District:</strong> <?php echo htmlspecialchars($crime['district']); ?>
                            </p>
                            <!-- Mini Map -->
                            <div id="mini-map" style="height: 200px; width: 100%; border-radius: 8px; margin-top: 1rem;"></div>

                        </div>

                        <div style="margin-top: 2rem;">
                             <h4 style="color: #64748b; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; font-weight: 700;">Evidence Attached</h4>
                             <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                <?php if(count($evidence) > 0): ?>
                                    <?php foreach($evidence as $e): ?>
                                        <?php 
                                            $ext = strtolower(pathinfo($e['file_path'], PATHINFO_EXTENSION));
                                            $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        ?>
                                        <a href="<?php echo htmlspecialchars($e['file_path']); ?>" target="_blank" style="display: block; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; padding: 5px;">
                                            <?php if($is_img): ?>
                                                <img src="<?php echo htmlspecialchars($e['file_path']); ?>" style="max-width: 150px; height: auto; border-radius: 4px;">
                                            <?php else: ?>
                                                <div style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-file-image" style="font-size: 2rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No evidence files uploaded.</p>
                                <?php endif; ?>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Admin Actions -->
                <div>
                    <!-- Reporter Bio -->
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1.5rem;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-green);">Reporter Info</h4>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong style="color: #334155;"><?php echo htmlspecialchars($crime['reporter_name']); ?></strong><br>
                                <span class="text-muted" style="font-size: 0.9rem;"><?php echo htmlspecialchars($crime['reporter_mobile']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Panel -->
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1.5rem;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-green);">Police Assignment</h4>
                        
                        <?php if($crime['assigned_to']): ?>
                            <div style="background: #f0fdf4; padding: 1rem; border-radius: 6px; border: 1px solid #bbf7d0; margin-bottom: 1rem;">
                                <strong style="color: #166534;">Currently Assigned To:</strong><br>
                                <?php echo htmlspecialchars($crime['police_name']); ?>
                            </div>
                        <?php else: ?>
                            <div style="background: #fff7ed; padding: 1rem; border-radius: 6px; border: 1px solid #ffedd5; margin-bottom: 1rem; color: #9a3412;">
                                Unassigned Case
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">Assign / Re-assign To:</label>
                            <select name="police_id" class="form-control" style="width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <option value="">Select Officer</option>
                                <?php foreach($police_list as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_police" class="btn-mixed" style="width: 100%; justify-content: center;">Update Assignment</button>
                        </form>
                    </div>

                    <!-- Status Update -->
                     <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-green);">Update Status</h4>
                        <form method="POST">
                            <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">New Status</label>
                            <input type="text" name="status" class="form-control" list="status_options" style="width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #cbd5e1;" value="<?php echo htmlspecialchars($crime['status']); ?>" required>
                            <datalist id="status_options">
                                <option value="Pending">
                                <option value="Assigned">
                                <option value="Under Investigation">
                                <option value="Action Taken">
                                <option value="Investigation Completed">
                                <option value="Resolved">
                                <option value="Filed in Court">
                                <option value="Case Closed">
                                <option value="Rejected">
                            </datalist>
                            <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">Admin Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Add note..." style="width: 100%; padding: 0.6rem; margin-bottom: 1rem; border-radius: 6px; border: 1px solid #cbd5e1;"></textarea>
                            <button type="submit" name="update_status" class="btn-mixed" style="width: 100%; justify-content: center;">Update Status</button>
                        </form>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const collapseBtn = document.getElementById('collapseBtn');
        const collapseIcon = document.getElementById('collapseIcon');

        // Restore Sidebar State
        if (localStorage.getItem('adminSidebarCollapsed') === 'true' && window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
            collapseIcon.classList.replace('fa-angle-double-left', 'fa-angle-double-right');
        }

        // Toggle Sidebar (Desktop Collapse)
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('adminSidebarCollapsed', isCollapsed);
                
                if (isCollapsed) {
                    collapseIcon.classList.replace('fa-angle-double-left', 'fa-angle-double-right');
                } else {
                    collapseIcon.classList.replace('fa-angle-double-right', 'fa-angle-double-left');
                }
            });
        }

        const desktopToggle = document.getElementById('desktopToggle');
        if (desktopToggle) {
            desktopToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('adminSidebarCollapsed', isCollapsed);
                
                if (isCollapsed) {
                    collapseIcon.classList.replace('fa-angle-double-left', 'fa-angle-double-right');
                } else {
                    collapseIcon.classList.replace('fa-angle-double-right', 'fa-angle-double-left');
                }
            });
        }

        // Toggle Sidebar (Mobile Drawer)
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }
        });
    </script>

    <?php if(isset($crime['latitude']) && isset($crime['longitude'])): ?>
    <script>
        var map = L.map('mini-map', {zoomControl: false}).setView([<?php echo $crime['latitude']; ?>, <?php echo $crime['longitude']; ?>], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        L.marker([<?php echo $crime['latitude']; ?>, <?php echo $crime['longitude']; ?>]).addTo(map);
        
        // Invalidate size on sidebar change
        const observer = new MutationObserver(() => {
            setTimeout(() => map.invalidateSize(), 300);
        });
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    </script>
    <?php endif; ?>
</body>
</html>
