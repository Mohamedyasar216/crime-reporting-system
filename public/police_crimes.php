<?php
session_start();
require_once '../app/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'police') {
    header("Location: login");
    exit;
}

$my_id = $_SESSION['user_id'];

// Fetch Rank & District
$me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$me->execute([$my_id]);
$user = $me->fetch();
$rank = $user['rank'] ?? 'Officer';
$district = $user['district'];

// Filters
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Query Logic (Hierarchy)
$sql = "SELECT c.*, u.full_name as reporter_name FROM crimes c LEFT JOIN users u ON c.user_id = u.id ";
$params = [];

if ($rank == 'DGP') {
    $sql .= "WHERE 1=1 ";
} else {
    // Both SP and regular officers are restricted to their assigned district
    $sql .= "WHERE c.district = ? ";
    $params[] = $district;
}

if ($filter_type) {
    $sql .= " AND c.crime_type = ?";
    $params[] = $filter_type;
}
if ($filter_status) {
    $sql .= " AND c.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$crimes = $stmt->fetchAll();

// Stats
$total = count($crimes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Records - TN Police</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Table Card */
        .record-card {
            background: #ffffff; border-radius: 24px; padding: 2.5rem; border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .police-table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .police-table th { text-align: left; padding: 1.25rem 1rem; color: #94a3b8; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border-bottom: 2px solid #f1f5f9; }
        .police-table td { padding: 1.25rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        
        .filter-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; gap: 2rem;
        }
        .filter-form { display: flex; gap: 1rem; flex: 1; flex-wrap: wrap; }
        .custom-select {
            padding: 0.75rem 1.25rem; border-radius: 12px; border: 2px solid #f1f5f9; 
            font-size: 0.95rem; color: var(--text-dark); font-weight: 600; min-width: 180px;
            transition: 0.3s;
        }
        .custom-select:focus { border-color: var(--accent); outline: none; }

        .btn-gradient-pol {
            background: var(--gradient-red); color: white; padding: 0.75rem 1.5rem; border-radius: 12px;
            font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: 0.3s;
            box-shadow: 0 4px 15px rgba(193, 18, 31, 0.2);
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-gradient-pol:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(193, 18, 31, 0.3); }

        .btn-outline-pol {
            border: 2px solid var(--accent); color: var(--primary); padding: 0.7rem 1.4rem; border-radius: 12px;
            font-weight: 700; text-decoration: none; transition: 0.3s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-outline-pol:hover { background: var(--accent); }

        .mobile-header {
            display: none; height: 70px; background: white; border-bottom: 2px solid var(--primary);
            padding: 0 1.5rem; align-items: center; justify-content: space-between; position: fixed;
            top: 0; left: 0; right: 0; z-index: 2000;
        }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 992px) {
            .filter-header { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
            .filter-form { width: 100%; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; padding-top: 6rem; }
            .officer-banner { grid-template-columns: 1fr; text-align: center; gap: 1.5rem; }
            .badge-icon { margin: 0 auto; }
            .mobile-header { display: flex; }
            .record-card { padding: 1.5rem; }
        }
        
        .mobile-view { display: none; }
        @media (max-width: 768px) {
            .desktop-view { display: none; }
            .mobile-view { display: block; }
            .record-card { padding: 0; background: transparent; box-shadow: none; border: none; }
            .filter-header { background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid rgba(0,0,0,0.03); margin-bottom: 1.5rem;}
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

        <div class="record-card">
            <div class="filter-header">
                <h3 style="margin: 0; font-weight: 800; color: #1e293b;">Central Investigation Logs</h3>
                <form action="" method="GET" class="filter-form">
                    <select name="type" class="custom-select">
                        <option value="">All Crime Types</option>
                        <option value="Theft" <?php if($filter_type=='Theft') echo 'selected'; ?>>Theft</option>
                        <option value="Robbery" <?php if($filter_type=='Robbery') echo 'selected'; ?>>Robbery</option>
                        <option value="Assault" <?php if($filter_type=='Assault') echo 'selected'; ?>>Assault</option>
                        <option value="Cybercrime" <?php if($filter_type=='Cybercrime') echo 'selected'; ?>>Cybercrime</option>
                        <option value="Missing Person" <?php if($filter_type=='Missing Person') echo 'selected'; ?>>Missing Person</option>
                        <option value="Other" <?php if($filter_type=='Other') echo 'selected'; ?>>Other</option>
                    </select>
                    <select name="status" class="custom-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php if($filter_status=='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Assigned" <?php if($filter_status=='Assigned') echo 'selected'; ?>>Assigned</option>
                        <option value="Investigation Ongoing" <?php if($filter_status=='Investigation Ongoing') echo 'selected'; ?>>Investigation Ongoing</option>
                        <option value="Resolved" <?php if($filter_status=='Resolved') echo 'selected'; ?>>Resolved</option>
                        <option value="Closed" <?php if($filter_status=='Closed') echo 'selected'; ?>>Closed</option>
                    </select>
                    <button type="submit" class="btn-gradient-pol">Filter Records</button>
                    <a href="police_crimes" class="btn-outline-pol">Reset</a>
                </form>
            </div>

            <!-- Desktop Table View -->
            <div class="table-responsive desktop-view">
                <table class="police-table">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Incident Profile</th>
                        <th>Location</th>
                        <th>Reporting Party</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($crimes) > 0): ?>
                        <?php foreach($crimes as $c): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?php echo $c['id']; ?></td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($c['crime_type']); ?></div>
                                <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 2px;">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($c['incident_date'])); ?>
                                </div>
                            </td>
                            <td>
                                <div style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($c['district']); ?></div>
                                <div style="font-size: 0.8rem; color: #94a3b8;"> Tamil Nadu Sector</div>
                            </td>
                            <td>
                                <div style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($c['reporter_name']); ?></div>
                            </td>
                            <td>
                                <?php 
                                    $bg = '#f1f5f9'; $col = '#475569';
                                    switch($c['status']) {
                                        case 'Assigned': $bg='#E0F2FE'; $col='#0369A1'; break;
                                        case 'Investigation Ongoing': $bg='#EEF2FF'; $col='#4338CA'; break;
                                        case 'Action Taken': $bg='#FFEDD5'; $col='#9A3412'; break;
                                        case 'Case Filed in Court': $bg='#F3E8FF'; $col='#6B21A8'; break;
                                        case 'Resolved': 
                                        case 'Closed':
                                        case 'Case Closed': $bg='#DCFCE7'; $col='#166534'; break;
                                        case 'Rejected': $bg='#FEE2E2'; $col='#991B1B'; break;
                                        default: $bg='#f1f5f9'; $col='#475569';
                                    }
                                ?>
                                <span class="status-badge" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                                    <?php echo $c['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="police_view_case?id=<?php echo $c['id']; ?>" class="btn-outline-pol" style="padding: 0.4rem 1rem; font-size: 0.8rem;">EXAMINE FILE</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 4rem; text-align: center; color: #64748b;">
                                <i class="fas fa-database" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <p>No criminal activity records match current search criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-view">
            <?php if(count($crimes) > 0): ?>
                <div style="display: grid; gap: 1rem;">
                <?php foreach($crimes as $c): ?>
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.2rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.8rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.8rem;">
                            <div>
                                <span style="font-family: monospace; font-weight: 700; color: var(--primary); font-size: 0.95rem;">#<?php echo $c['id']; ?></span>
                                <h4 style="margin: 4px 0 0; font-size: 1.15rem; color: var(--text-dark);"><?php echo htmlspecialchars($c['crime_type']); ?></h4>
                            </div>
                            <?php 
                                $bg = '#f1f5f9'; $col = '#475569';
                                switch($c['status']) {
                                    case 'Assigned': $bg='#E0F2FE'; $col='#0369A1'; break;
                                    case 'Investigation Ongoing': $bg='#EEF2FF'; $col='#4338CA'; break;
                                    case 'Action Taken': $bg='#FFEDD5'; $col='#9A3412'; break;
                                    case 'Case Filed in Court': $bg='#F3E8FF'; $col='#6B21A8'; break;
                                    case 'Resolved': 
                                    case 'Closed':
                                    case 'Case Closed': $bg='#DCFCE7'; $col='#166534'; break;
                                    case 'Rejected': $bg='#FEE2E2'; $col='#991B1B'; break;
                                    default: $bg='#f1f5f9'; $col='#475569';
                                }
                            ?>
                            <span class="status-badge" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                                <?php echo $c['status']; ?>
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">
                            <div>
                                <strong style="display: block; font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px;">Incident Date</strong>
                                <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($c['incident_date'])); ?>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px;">Location</strong>
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['district']); ?>
                            </div>
                        </div>
                        
                        <a href="police_view_case?id=<?php echo $c['id']; ?>" class="btn-gradient-pol" style="width: 100%; display: flex; justify-content: center; align-items: center;">
                            EXAMINE CASE FILE
                        </a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #64748b;">No criminal records match criteria.</div>
            <?php endif; ?>
        </div>

        <style>
            .mobile-view { display: none; }
            @media (max-width: 768px) {
                .desktop-view { display: none; }
                .mobile-view { display: block; }
                .record-card { padding: 1rem; background: transparent; box-shadow: none; border: none; } /* Reset card wrapper on mobile to let inner cards shine */
                .filter-header { background: white; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1rem;}
            }
        </style>
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
    </script>

</body>
</html>
