<?php
session_start();
require_once '../app/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'police') {
    header("Location: login");
    exit;
}

$crime_id = $_GET['id'] ?? null;
if(!$crime_id) die("Invalid ID");

$my_id = $_SESSION['user_id'];

// Fetch Officer Details
$me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$me->execute([$my_id]);
$user = $me->fetch();
$rank = $user['rank'] ?? 'Officer';
$district = $user['district'];

// Escalation Logic (DGP Only)
if (isset($_POST['escalate']) && $rank == 'DGP') {
    $pdo->prepare("UPDATE crimes SET is_escalated = 1 WHERE id = ?")->execute([$crime_id]);
    $pdo->prepare("INSERT INTO crime_updates (crime_id, status_from, status_to, remarks) VALUES (?, ?, ?, ?)")->execute([$crime_id, 'Normal', 'Escalated by DGP', 'High priority escalation initiated by the Director General of Police.']);
    
    // System Audit Log
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$my_id, "Case Escalated", "Case ID: $crime_id"]);

    $msg = "Case Escalated for immediate action!";
}

// Update Status Logic (Non-DGP)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status']) && $rank != 'DGP') {
    $status_to = $_POST['status'];
    $remarks = htmlspecialchars($_POST['remarks']);
    
    $curr = $pdo->prepare("SELECT status FROM crimes WHERE id = ?");
    $curr->execute([$crime_id]);
    $status_from = $curr->fetchColumn();

    $pdo->prepare("UPDATE crimes SET status = ? WHERE id = ?")->execute([$status_to, $crime_id]);
    $pdo->prepare("INSERT INTO crime_updates (crime_id, status_from, status_to, remarks) VALUES (?, ?, ?, ?)")->execute([$crime_id, $status_from, $status_to, $remarks]);
    
    // System Audit Log
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$my_id, "Police Status Update", "Case ID: $crime_id, New Status: $status_to"]);

    header("Location: police_dashboard?msg=case_updated");
    exit;
}

// Fetch Crime Details
$stmt = $pdo->prepare("SELECT c.*, u.full_name, u.mobile FROM crimes c LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$stmt->execute([$crime_id]);
$crime = $stmt->fetch();

if(!$crime) die("Access Denied or Case not found.");

// Permission Check: Must be DGP or match the district
if ($rank != 'DGP' && $crime['district'] != $district && $crime['assigned_to'] != $my_id) {
    die("Access Denied: You do not have permission to view this case docket.");
}

// Fetch Evidence
$ev = $pdo->prepare("SELECT * FROM evidence WHERE crime_id = ?");
$ev->execute([$crime_id]);
$evidence = $ev->fetchAll();

// Fetch History
$hist = $pdo->prepare("SELECT * FROM crime_updates WHERE crime_id = ? ORDER BY id DESC");
$hist->execute([$crime_id]);
$updates = $hist->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case File #<?php echo $crime['id']; ?> - TN Police</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root { 
            --sidebar-width: 240px; 
            --primary: #C1121F; 
            --accent: #B3E5FC;
            --police-bg: #f4f7fa; 
            --text-dark: #1e293b;
            --gradient-red: linear-gradient(135deg, #C1121F 0%, #e53935 100%);
            --gradient-mixed: linear-gradient(135deg, #C1121F 0%, #B3E5FC 100%);
        }
        
        body { margin: 0; background: var(--police-bg); font-family: 'Inter', system-ui, sans-serif; color: var(--text-dark); }
        
        /* Professional Police Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid rgba(0,0,0,0.05);
            color: #1e293b;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.02);
            transition: transform 0.3s ease;
        }
        .sidebar-brand {
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
        }
        .brand-text { font-size: 1.25rem; font-weight: 900; color: var(--primary); letter-spacing: -0.5px; }
        
        .nav-group { padding: 1rem 0.75rem; }
        .nav-item { 
            display: flex; align-items: center; padding: 0.85rem 1.25rem; color: #64748b; 
            text-decoration: none; transition: 0.3s; border-radius: 12px; margin-bottom: 0.5rem; font-weight: 600;
            font-size: 0.9rem;
        }
        .nav-item:hover { background: var(--accent); color: var(--primary); }
        .nav-item.active { 
            background: var(--gradient-mixed); 
            color: white; 
            box-shadow: 0 8px 20px rgba(193, 18, 31, 0.15); 
        }
        .nav-item i { width: 20px; font-size: 1.1rem; margin-right: 12px; }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2.5rem;
        }

        /* Officer Profile Section */
        .officer-banner {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(0,0,0,0.03);
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .badge-icon {
            width: 80px; height: 80px; 
            background: var(--accent); color: var(--primary);
            border-radius: 24px; display: flex; align-items: center; justify-content: center;
            font-size: 2rem; transform: rotate(-5deg);
        }
        .officer-info h2 { margin: 0; font-size: 1.6rem; color: #1e293b; font-weight: 900; }
        .officer-info p { margin: 0.5rem 0 0; font-size: 0.95rem; color: #64748b; font-weight: 500; }
        .rank-tag {
            background: var(--primary); color: white; padding: 6px 14px; border-radius: 8px; 
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-right: 10px;
        }

        /* Case Card */
        .case-card {
            background: #ffffff; border-radius: 24px; padding: 2.5rem; border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 2rem;
        }
        .header-action { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem; }
        
        .timeline-item { position: relative; padding-left: 2.5rem; border-left: 3px solid var(--accent); margin-bottom: 2rem; padding-bottom: 1rem; }
        .timeline-item::before { content: ''; position: absolute; left: -10px; top: 0; width: 18px; height: 18px; background: white; border: 4px solid var(--primary); border-radius: 50%; }
        
        .case-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 3rem; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2.5rem; }
        .detail-label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: block; }
        .detail-val { font-size: 1.1rem; color: var(--text-dark); font-weight: 700; line-height: 1.6; }

        .form-control { width: 100%; padding: 1rem; border-radius: 12px; border: 2px solid #f1f5f9; font-family: inherit; font-size: 0.95rem; margin-top: 8px; box-sizing: border-box; transition: 0.3s; }
        .form-control:focus { border-color: var(--accent); outline: none; }
        
        .btn-update { 
            width: 100%; padding: 1rem; background: var(--gradient-red); color: white; border: none; 
            border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s; 
            box-shadow: 0 4px 15px rgba(193, 18, 31, 0.2);
        }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(193, 18, 31, 0.3); }

        .exhibit-link {
            display: flex; align-items: center; gap: 12px; padding: 1.25rem; 
            background: #ffffff; border: 2px solid #f1f5f9; border-radius: 16px; 
            text-decoration: none; color: var(--primary); font-weight: 800; font-size: 0.85rem;
            transition: 0.3s;
        }
        .exhibit-link:hover { border-color: var(--accent); background: var(--accent); color: var(--primary); }

        .mobile-header {
            display: none; height: 70px; background: white; border-bottom: 2px solid var(--primary);
            padding: 0 1.5rem; align-items: center; justify-content: space-between; position: fixed;
            top: 0; left: 0; right: 0; z-index: 2000;
        }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 992px) {
            .case-grid { grid-template-columns: 1fr; }
            .case-view-sidebar { order: -1; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; padding-top: 6.5rem; }
            .officer-banner { grid-template-columns: 1fr; text-align: center; gap: 1.5rem; }
            .badge-icon { margin: 0 auto; }
            .detail-grid { grid-template-columns: 1fr; gap: 2rem; }
            .header-action { flex-direction: column; gap: 1.5rem; }
            .mobile-header { display: flex; }
        }
    </style>
</head>
<body>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <a href="index" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-halved" style="color: var(--primary);"></i>
            <span style="font-weight: 800; font-size: 1.1rem;">TN POLICE</span>
        </a>
        <button id="sidebarToggle" style="background: none; border: none; font-size: 1.5rem; color: var(--primary); cursor: pointer;">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" style="z-index: 999;"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-shield-halved" style="font-size: 1.8rem; color: var(--primary);"></i>
            <span class="brand-text">TN POLICE</span>
        </div>
        <div class="nav-group">
            <a href="police_dashboard" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="police_map" class="nav-item"><i class="fas fa-map-location-dot"></i> Crime Map</a>
            <a href="police_crimes" class="nav-item active"><i class="fas fa-folder-tree"></i> Crime Details</a>
            <?php if($rank == 'DGP'): ?>
                <a href="police_analytics" class="nav-item"><i class="fas fa-chart-line"></i> Analytics</a>
            <?php endif; ?>
            <hr style="border:0; border-top:1px solid #f1f5f9; margin: 1.5rem 0;">
            <a href="logout" class="nav-item" style="color: #dc2626;"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <main class="main-content">
        <!-- Officer Profile Banner -->
        <div class="officer-banner">
            <div class="badge-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="officer-info">
                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p>
                    <span class="rank-tag"><?php echo htmlspecialchars($rank); ?></span>
                    <span><i class="fas fa-building-shield"></i> <?php echo htmlspecialchars($district); ?> District HQ</span>
                    <span style="margin-left: 15px;"><i class="fas fa-id-badge"></i> Officer ID: #<?php echo $my_id; ?></span>
                </p>
            </div>
            <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                <i class="fas fa-clock"></i> Logged in: <?php echo date('H:i'); ?><br>
                <?php echo date('d M, Y'); ?>
            </div>
        </div>

        <div class="case-grid">
            
            <div class="case-view-main">
                <div class="case-card">
                    <div class="header-action">
                        <div>
                            <span class="detail-label">Official Docket ID</span>
                            <h1 style="margin: 0; font-size: 2rem; color: var(--primary); font-weight: 900;">CASE-<?php echo $crime['id']; ?></h1>
                        </div>
                        <?php if($crime['is_escalated']): ?>
                            <div style="background: #fef2f2; border: 1px solid #fee2e2; color: #dc2626; padding: 10px 18px; border-radius: 50px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;">
                                State Level Escalation
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="margin-bottom: 2.5rem;">
                        <span class="detail-label">Incident Statement</span>
                        <p class="detail-val" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border-left: 4px solid #cbd5e1;">
                            <?php echo nl2br(htmlspecialchars($crime['description'])); ?>
                        </p>
                    </div>

                    <div class="detail-grid">
                        <div>
                            <span class="detail-label">Occurrence Location</span>
                            <div class="detail-val">
                                <i class="fas fa-location-dot" style="color: var(--primary);"></i> <?php echo htmlspecialchars($crime['area']); ?><br>
                                <span style="font-size: 0.9rem; opacity: 0.7;"><?php echo htmlspecialchars($crime['district']); ?>, TN</span>
                            </div>
                        </div>
                        <div>
                            <span class="detail-label">Primary Informant</span>
                            <div class="detail-val">
                                <?php if($crime['full_name']): ?>
                                    <i class="fas fa-user-circle" style="color: var(--primary);"></i> <?php echo htmlspecialchars($crime['full_name']); ?><br>
                                    <span style="font-size: 0.9rem; opacity: 0.7;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($crime['mobile']); ?></span>
                                <?php else: ?>
                                    <i class="fas fa-user-secret" style="color: #64748b;"></i> Anonymous Reporter<br>
                                    <span style="font-size: 0.8rem; color: #dc2626; font-weight: 700;">(Identity Protected)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2.5rem;">
                        <span class="detail-label">Crime Scene Reconnaissance (GIS)</span>
                        <div id="case-map" style="height: 350px; width: 100%; border-radius: 16px; border: 1px solid #e2e8f0;"></div>
                    </div>
                </div>

                <div class="case-card">
                    <h3 style="margin-top: 0; margin-bottom: 2rem; font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">Investigation Timeline</h3>
                    <div class="timeline">
                        <?php foreach($updates as $u): ?>
                        <div class="timeline-item">
                            <div style="font-size: 0.75rem; color: #64748b; font-weight: 700;"><?php echo date('d M Y, H:i', strtotime($u['updated_at'])); ?></div>
                            <div style="font-weight: 800; color: #1e293b; margin: 4px 0;">STATUS: <?php echo htmlspecialchars($u['status_to']); ?></div>
                            <p style="margin: 0; font-size: 0.9rem; color: #475569; line-height: 1.5;"><?php echo htmlspecialchars($u['remarks']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="case-view-sidebar">
                <!-- Action Panel -->
                <div class="case-card" style="position: sticky; top: 2.5rem;">
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: #1e293b;">Case Management</h3>
                    
                    <?php if($rank == 'DGP'): ?>
                        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                            <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 0.9rem; text-transform: uppercase;">Command Authority</h4>
                            <p style="margin: 0; font-size: 0.85rem; color: #92400e; line-height: 1.4;">Strategic escalation focuses all district resources on this investigation.</p>
                        </div>
                        <?php if(!$crime['is_escalated']): ?>
                            <form method="POST">
                                <input type="hidden" name="escalate" value="1">
                                <button type="submit" class="btn-update">INITIATE ESCALATION</button>
                            </form>
                        <?php else: ?>
                            <div style="background: #f1f5f9; padding: 1rem; border-radius: 10px; text-align: center; color: #64748b; font-weight: 700;">ALREADY ESCALATED</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST">
                            <div style="margin-bottom: 1.5rem;">
                                <label class="detail-label">Update Investigative Status</label>
                                <input type="text" name="status" class="form-control" list="status_options" value="<?php echo htmlspecialchars($crime['status']); ?>" placeholder="Enter status or select below..." required>
                                <datalist id="status_options">
                                    <option value="Under Investigation">
                                    <option value="Action Taken">
                                    <option value="Filed in Court">
                                    <option value="Investigation Completed">
                                    <option value="Case Closed">
                                    <option value="Resolved">
                                    <option value="Rejected">
                                </datalist>
                            </div>
                            <div style="margin-bottom: 1.5rem;">
                                <label class="detail-label">Official Remarks</label>
                                <textarea name="remarks" rows="5" class="form-control" placeholder="Detailed investigation findings..." required></textarea>
                            </div>
                            <button type="submit" class="btn-update">SUBMIT CASE UPDATE</button>
                        </form>
                    <?php endif; ?>

                    <!-- Evidence Section -->
                    <div style="margin-top: 3rem; border-top: 1px solid #f1f5f9; padding-top: 2rem;">
                        <label class="detail-label">Exhibits & Evidence</label>
                        <?php if(count($evidence) > 0): ?>
                            <div style="display: grid; gap: 10px;">
                                <?php foreach($evidence as $e): ?>
                                    <a href="<?php echo htmlspecialchars($e['file_path']); ?>" target="_blank" class="exhibit-link">
                                        <i class="fas fa-file-image"></i> SCAN EXHIBIT_<?php echo $e['id']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 0.85rem; color: #94a3b8; text-align: center; font-style: italic;">No exhibits filed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

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

        var lat = <?php echo $crime['latitude'] ? $crime['latitude'] : '13.0827'; ?>;
        var lng = <?php echo $crime['longitude'] ? $crime['longitude'] : '80.2707'; ?>;
        
        var map = L.map('case-map', { zoomControl: false }).setView([lat, lng], 14);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        
        <?php if($crime['latitude']): ?>
            L.circleMarker([lat, lng], {
                radius: 10,
                fillColor: "var(--primary)",
                color: "#fff",
                weight: 3,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map).bindPopup("<b>Incident Epicenter</b>").openPopup();
        <?php endif; ?>

        // Fix map size check for mobile
        setTimeout(() => { map.invalidateSize(); }, 500);
    </script>
</body>
</html>
