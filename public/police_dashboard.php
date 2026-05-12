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

// Query Logic
$sql = "SELECT c.*, u.full_name as reporter_name FROM crimes c LEFT JOIN users u ON c.user_id = u.id ";
$params = [];

if ($rank == 'DGP') {
    $sql .= "ORDER BY c.created_at DESC";
    $title = "State-Wide Intelligence Monitor";
} else {
    // Both SP and regular officers are restricted to their assigned district
    $sql .= "WHERE c.district = ? ORDER BY c.created_at DESC";
    $params = [$district];
    $title = ($rank == 'SP') ? "District Command Center ($district)" : "District Case Log ($district)";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$crimes = $stmt->fetchAll();

// Stats
$total = count($crimes);
$pending = 0;
$closed = 0;
foreach($crimes as $c) {
    if($c['status'] == 'Closed' || $c['status'] == 'Resolved') $closed++;
    elseif($c['status'] == 'Pending' || $c['status'] == 'Assigned') $pending++;
}

// Fetch Broadcast Alerts for Police
$stmtNotices = $pdo->prepare("SELECT * FROM public_notices WHERE target_role IN ('all', 'police') ORDER BY created_at DESC LIMIT 3");
$stmtNotices->execute();
$notices = $stmtNotices->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Control Center - CRS</title>
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

        .stat-group {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;
        }
        .stat-card {
            background: #ffffff; padding: 2rem; border-radius: 20px; border: 1px solid rgba(0,0,0,0.03);
            display: flex; flex-direction: column; transition: 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.05); }
        .stat-card::after {
            content: ''; position: absolute; top: -10px; right: -10px;
            width: 60px; height: 60px; background: var(--accent); opacity: 0.2;
            border-radius: 50%;
        }
        
        .stat-val { font-size: 2.5rem; font-weight: 900; color: #1e293b; line-height: 1.2; margin-bottom: 5px; }
        .stat-lab { font-size: 0.85rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }

        /* Tables & Lists */
        .case-list-container {
            background: white; padding: 2rem; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }

        .btn-gradient-pol {
            background: var(--gradient-red);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(193, 18, 31, 0.2);
            border: none;
            cursor: pointer;
        }
        .btn-gradient-pol:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(193, 18, 31, 0.3); }

        .btn-outline-pol {
            border: 2px solid var(--accent);
            color: var(--primary);
            padding: 0.7rem 1.4rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .btn-outline-pol:hover { background: var(--accent); }

        .mobile-header {
            display: none; height: 70px; background: white; border-bottom: 2px solid var(--primary);
            padding: 0 1.5rem; align-items: center; justify-content: space-between; position: fixed;
            top: 0; left: 0; right: 0; z-index: 2000;
        }

        @media (max-width: 992px) {
            .stat-group { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1500; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1.5rem; padding-top: 6rem; }
            .officer-banner { grid-template-columns: 1fr; text-align: center; gap: 1.5rem; padding: 1.5rem; }
            .badge-icon { margin: 0 auto; }
            .stat-group { grid-template-columns: 1fr; }
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
            <a href="police_dashboard" class="nav-item active"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="police_map" class="nav-item"><i class="fas fa-map-location-dot"></i> Crime Map</a>
            <a href="police_crimes" class="nav-item"><i class="fas fa-folder-tree"></i> Crime Details</a>
            <?php if($rank == 'DGP'): ?>
                <a href="police_analytics" class="nav-item"><i class="fas fa-chart-line"></i> Analytics</a>
            <?php endif; ?>
            <hr style="border:0; border-top:1px solid #f1f5f9; margin: 1.5rem 0;">
            <a href="logout" class="nav-item" style="color: #dc2626;"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <main class="main-content">
        <!-- Officer Profile Banner -->
        <div class="officer-banner grid-stack">
            <div class="badge-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="officer-info">
                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p>
                    <span class="rank-tag"><?php echo htmlspecialchars($rank); ?></span>
                    <span style="display: block; margin-top: 5px;"><i class="fas fa-building-shield"></i> <?php echo htmlspecialchars($district); ?> District HQ</span>
                    <span style="display: block; margin-top: 5px;"><i class="fas fa-id-badge"></i> Officer ID: #<?php echo $my_id; ?></span>
                </p>
            </div>
            <div style="color: #64748b; font-size: 0.9rem; font-weight: 600;">
                <i class="fas fa-clock"></i> Logged in: <?php echo date('H:i'); ?><br>
                <?php echo date('d M, Y'); ?>
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <h2 style="font-weight: 800; color: #1e293b;"><?php echo $title; ?></h2>
        </div>

        <!-- Internal Police Alerts Section -->
        <?php if (count($notices) > 0): ?>
        <div style="margin-bottom: 3rem; background: #ffffff; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem;">
                <i class="fas fa-tower-broadcast" style="color: var(--primary);"></i>
                <h4 style="margin: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; font-weight: 800;">HQ Operational Directives</h4>
            </div>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($notices as $notice): ?>
                <div style="display: flex; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;">
                    <div style="width: 4px; border-radius: 4px; background: <?php echo ($notice['priority'] == 'Urgent' || $notice['priority'] == 'High') ? '#dc2626' : '#3b82f6'; ?>;"></div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <strong style="font-size: 1rem; color: #0f172a;"><?php echo htmlspecialchars($notice['title']); ?></strong>
                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d M, H:i', strtotime($notice['created_at'])); ?></small>
                        </div>
                        <p style="margin: 4px 0 0; font-size: 0.9rem; color: #475569; line-height: 1.5;"><?php echo htmlspecialchars($notice['message']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stat-group" style="margin-bottom: 3.5rem;">
            <div class="stat-card" style="border-bottom: 6px solid var(--primary);">
                <span class="stat-lab">Total Assigned</span>
                <span class="stat-val"><?php echo $total; ?></span>
            </div>
            <div class="stat-card" style="border-bottom: 6px solid #f97316;">
                <span class="stat-lab">Action Pending</span>
                <span class="stat-val"><?php echo $pending; ?></span>
            </div>
            <div class="stat-card" style="border-bottom: 6px solid #10b981;">
                <span class="stat-lab">Case Resolved</span>
                <span class="stat-val"><?php echo $closed; ?></span>
            </div>
        </div>


            <!-- Case List -->
            <div class="case-list-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h3 style="margin: 0; font-weight: 900; color: var(--text-dark); font-size: 1.4rem;">Operational Case Feed</h3>
                    <div style="height: 4px; width: 50px; background: var(--accent); border-radius: 10px;"></div>
                </div>
                
                <!-- Desktop Table View -->
                <div class="table-responsive desktop-view">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #e2e8f0; color: var(--text-muted);">
                                <th style="padding: 10px;">ID</th>
                                <th style="padding: 10px;">Date</th>
                                <th style="padding: 10px;">Type</th>
                                <th style="padding: 10px;">Location</th>
                                <th style="padding: 10px;">Status</th>
                                <th style="padding: 10px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($crimes) > 0): ?>
                                <?php foreach($crimes as $c): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px;">#<?php echo $c['id']; ?></td>
                                    <td style="padding: 10px;"><?php echo date('d M', strtotime($c['incident_date'])); ?></td>
                                    <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($c['crime_type']); ?></td>
                                    <td style="padding: 10px;">
                                        <?php echo htmlspecialchars($c['area']); ?>, <?php echo htmlspecialchars($c['district']); ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php 
                                            $bg = '#f1f5f9'; $col = '#475569';
                                            switch($c['status']) {
                                                case 'Assigned': $bg='#dbeafe'; $col='#1e40af'; break;
                                                case 'Investigation Ongoing': $bg='#e0e7ff'; $col='#3730a3'; break;
                                                case 'Action Taken': $bg='#ffedd5'; $col='#9a3412'; break;
                                                case 'Case Filed in Court': $bg='#f3e8ff'; $col='#6b21a8'; break;
                                                case 'Resolved': 
                                                case 'Closed':
                                                case 'Case Closed': $bg='#dcfce7'; $col='#166534'; break;
                                                case 'Rejected': $bg='#fee2e2'; $col='#991b1b'; break;
                                                default: $bg='#f1f5f9'; $col='#475569';
                                            }
                                        ?>
                                        <span style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                                            <?php echo $c['status']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px;">
                                        <a href="police_view_case?id=<?php echo $c['id']; ?>" class="btn-outline-pol" style="padding: 0.4rem 1rem; font-size: 0.8rem;">Open File</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">No cases found.</td>
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
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.8rem;">
                                    <div>
                                        <span style="font-family: monospace; font-weight: 700; color: var(--primary); font-size: 0.9rem;">#<?php echo $c['id']; ?></span>
                                        <h4 style="margin: 4px 0 0; font-size: 1rem; color: #1e293b;"><?php echo htmlspecialchars($c['crime_type']); ?></h4>
                                    </div>
                                    <?php 
                                        $bg = '#f1f5f9'; $col = '#475569';
                                        switch($c['status']) {
                                            case 'Assigned': $bg='#dbeafe'; $col='#1e40af'; break;
                                            case 'Investigation Ongoing': $bg='#e0e7ff'; $col='#3730a3'; break;
                                            case 'Action Taken': $bg='#ffedd5'; $col='#9a3412'; break;
                                            case 'Case Filed in Court': $bg='#f3e8ff'; $col='#6b21a8'; break;
                                            case 'Resolved': 
                                            case 'Closed':
                                            case 'Case Closed': $bg='#dcfce7'; $col='#166534'; break;
                                            case 'Rejected': $bg='#fee2e2'; $col='#991b1b'; break;
                                            default: $bg='#f1f5f9'; $col='#475569';
                                        }
                                    ?>
                                    <span style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">
                                        <?php echo $c['status']; ?>
                                    </span>
                                </div>
                                
                                <div style="display: grid; gap: 8px; font-size: 0.9rem; color: #64748b; margin-bottom: 1rem;">
                                    <div><i class="far fa-calendar-alt" style="width: 20px;"></i> <?php echo date('d M Y, h:i A', strtotime($c['incident_date'])); ?></div>
                                    <div><i class="fas fa-map-marker-alt" style="width: 20px;"></i> <?php echo htmlspecialchars($c['area']); ?>, <?php echo htmlspecialchars($c['district']); ?></div>
                                </div>
                                
                                <a href="police_view_case?id=<?php echo $c['id']; ?>" class="btn-gradient-pol" style="width: 100%; justify-content: center;">
                                    Examine Case File
                                </a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">No cases found.</div>
                    <?php endif; ?>
                </div>

                <style>
                    .mobile-view { display: none; }
                    @media (max-width: 768px) {
                        .desktop-view { display: none; }
                        .mobile-view { display: block; }
                    }
                </style>
            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    </script>
</body>
</html>
