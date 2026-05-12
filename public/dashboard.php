<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'citizen') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch User Details for Profile
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$u = $stmtUser->fetch();

// Fetch User's Reports
$stmt = $pdo->prepare("SELECT * FROM crimes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$crimes = $stmt->fetchAll();

// Stats for Citizen
$total = count($crimes);
$pending = 0;
$resolved = 0;
foreach($crimes as $c) {
    if($c['status'] == 'Resolved' || $c['status'] == 'Closed') $resolved++;
    else $pending++;
}

// Fetch Broadcast Alerts
$stmtNotices = $pdo->prepare("SELECT * FROM public_notices WHERE target_role IN ('all', 'citizen') ORDER BY created_at DESC LIMIT 3");
$stmtNotices->execute();
$notices = $stmtNotices->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard - SafeCity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #19485F;
            --accent: #D9E0A4;
            --accent-light: #f1f4d8;
            --sidebar-bg: #ffffff;
            --sidebar-width: 240px;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --danger: #ef4444;
            --card-bg: #ffffff;
            --sidebar-border: #e2e8f0;
            
            --grad-teal: linear-gradient(135deg, #19485F 0%, #2c6e8f 100%);
            --grad-accent: linear-gradient(135deg, #D9E0A4 0%, #b8c17d 100%);
            --grad-light: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text-main);
            margin: 0;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Light Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--text-main);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            border-right: 1px solid var(--sidebar-border);
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--primary);
            border-bottom: 1px solid #f1f5f9;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            padding: 1.5rem 0.75rem;
            flex: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.25rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .nav-item i { width: 20px; margin-right: 12px; font-size: 1.1rem; }

        .nav-item:hover {
            color: var(--primary);
            background: #f1f5f9;
            transform: translateX(3px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, #19485F 0%, #69A481 100%);
            color: #ffffff;
            box-shadow: 0 8px 15px rgba(25, 72, 95, 0.2);
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2.5rem;
            transition: all 0.3s;
        }

        /* Light Premium Banner */
        .banner-card {
            background: #ffffff;
            color: var(--text-main);
            padding: 2.5rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .banner-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: var(--accent-light);
            border-radius: 50%;
            z-index: 0;
            opacity: 0.5;
        }

        .banner-info { position: relative; z-index: 1; }

        .banner-icon {
            width: 70px;
            height: 70px;
            background: var(--accent-light);
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-right: 20px;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 15px rgba(217, 224, 164, 0.2);
        }

        /* Stats Card Styling */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.8rem;
            border-radius: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.06);
        }

        .stat-value { font-size: 2.4rem; font-weight: 800; color: var(--primary); letter-spacing: -1px; }
        .stat-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            letter-spacing: -0.5px;
        }

        /* Gradient Buttons */
        .btn-gradient-cit {
            background: linear-gradient(135deg, #19485F 0%, #69A481 100%);
            color: white;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(25, 72, 95, 0.2);
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-gradient-cit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(25, 72, 95, 0.3);
            filter: brightness(1.1);
        }

        .btn-outline-cit {
            background: #ffffff;
            color: var(--primary);
            border: 1px solid var(--sidebar-border);
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .btn-outline-cit:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }

        /* Modern Table */
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 1rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid var(--bg-light); }
        .table td { padding: 1.25rem 1rem; font-size: 0.9rem; border-bottom: 1px solid var(--bg-light); }

        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .badge-pending { background: #fffbeb; color: #b45309; }
        .badge-assigned { background: #eff6ff; color: #1e40af; }
        .badge-resolved { background: #f0fdf4; color: #15803d; }
        .badge-rejected { background: #fef2f2; color: #991b1b; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 80px; }
            .mobile-header { display: flex; position: fixed; top: 0; left: 0; right: 0; height: 64px; background: white; z-index: 1100; padding: 0 1.25rem; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            
            .banner-card { flex-direction: column; text-align: center; padding: 1.5rem; gap: 1rem; }
            .banner-icon { margin-right: 0; margin-bottom: 10px; width: 60px; height: 60px; font-size: 1.5rem; }
            .banner-info h1 { font-size: 1.4rem !important; }
            
            .card-header { flex-direction: column; align-items: stretch; gap: 1rem; text-align: center; }
            .btn-gradient-cit { justify-content: center; width: 100%; }
            
            .stats-grid { grid-template-columns: 1fr; }
            .hide-on-mobile { display: none; }
        }

        .sidebar-overlay { 
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; 
        }
        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <a href="index" style="text-decoration: none; color: var(--primary); display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-shield-halved" style="color: var(--primary); font-size: 1.5rem;"></i>
            <span style="font-weight: 800; font-size: 1.2rem;">SAFECITY</span>
        </a>
        <button id="sidebarToggle" style="background: none; border: none; font-size: 1.4rem; color: var(--secondary); cursor: pointer;">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-shield-halved"></i>
                <span>SAFECITY</span>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard" class="nav-item active">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="report" class="nav-item">
                    <i class="fas fa-file-circle-plus"></i> Report Crime
                </a>
                <a href="map" class="nav-item">
                    <i class="fas fa-map-location-dot"></i> Crime Map
                </a>
                <a href="my_reports" class="nav-item">
                    <i class="fas fa-clipboard-list"></i> My History
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout" class="nav-item" style="color: var(--danger);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Hero Banner -->
            <div class="banner-card">
                <div class="banner-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="banner-info">
                    <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem; font-weight: 900; color: var(--primary);">Welcome back, <?php echo htmlspecialchars(explode(' ', $u['full_name'])[0]); ?>!</h1>
                    <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 500;">
                        <i class="fas fa-location-dot" style="margin-right: 6px; color: var(--accent);"></i> <?php echo htmlspecialchars($u['district'] ?? 'Unknown District'); ?>, Tamil Nadu
                    </p>
                </div>
                <div style="text-align: right;" class="hide-on-mobile">
                    <div style="background: var(--bg-body); padding: 12px 20px; border-radius: 16px; border: 1px solid var(--sidebar-border);">
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted); margin-bottom: 4px;">Active Status</div>
                        <div style="font-weight: 900; font-size: 1rem; color: var(--primary);"><i class="fas fa-check-circle" style="color: #10b981;"></i> Citizen Verified</div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">Total Reports</div>
                    <i class="fas fa-folder-open" style="position: absolute; right: 20px; top: 20px; font-size: 3.5rem; opacity: 0.1; color: var(--primary);"></i>
                    <div style="height: 4px; background: var(--primary); position: absolute; bottom: 0; left: 0; right: 0;"></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #eab308;"><?php echo $pending; ?></div>
                    <div class="stat-label">Pending / In Progress</div>
                    <i class="fas fa-clock" style="position: absolute; right: 20px; top: 20px; font-size: 3.5rem; opacity: 0.1; color: #eab308;"></i>
                    <div style="height: 4px; background: #eab308; position: absolute; bottom: 0; left: 0; right: 0;"></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #10b981;"><?php echo $resolved; ?></div>
                    <div class="stat-label">Successfully Resolved</div>
                    <i class="fas fa-check-circle" style="position: absolute; right: 20px; top: 20px; font-size: 3.5rem; opacity: 0.1; color: #10b981;"></i>
                    <div style="height: 4px; background: #10b981; position: absolute; bottom: 0; left: 0; right: 0;"></div>
                </div>
            </div>

            <!-- Alerts Section -->
            <?php if (count($notices) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bullhorn" style="color: var(--primary); margin-right: 10px;"></i> Public Notices</h3>
                </div>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($notices as $notice): ?>
                        <div style="display: flex; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                            <div style="width: 4px; border-radius: 4px; background: <?php echo ($notice['priority'] == 'Urgent' || $notice['priority'] == 'High') ? 'var(--danger)' : 'var(--primary)'; ?>;"></div>
                            <div>
                                <h4 style="margin: 0 0 4px 0; font-size: 1rem;"><?php echo htmlspecialchars($notice['title']); ?></h4>
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 4px;"><?php echo htmlspecialchars($notice['message']); ?></p>
                                <small style="color: var(--text-muted); font-weight: 600; font-size: 0.75rem;">
                                    <?php echo date('d M, h:i A', strtotime($notice['created_at'])); ?> • <span style="color: var(--text-main);"><?php echo $notice['priority']; ?> Priority</span>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Reports -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Reports</h3>
                    <a href="report" class="btn-gradient-cit">
                        <i class="fas fa-plus"></i> New Report
                    </a>
                </div>
                
                <!-- Desktop View -->
                <div class="table-responsive desktop-view">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Incident Date</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($crimes) > 0): ?>
                                <?php foreach(array_slice($crimes, 0, 5) as $c): ?>
                                <tr>
                                    <td><span style="font-family: monospace; font-weight: 700; color: var(--primary);">#<?php echo $c['id']; ?></span></td>
                                    <td><?php echo date('d M, Y', strtotime($c['incident_date'])); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent);"></div>
                                            <?php echo htmlspecialchars($c['crime_type']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            // Status Badge Logic
                                            $badgeClass = 'badge-pending';
                                            if(in_array($c['status'], ['Assigned', 'Investigation Ongoing'])) $badgeClass = 'badge-assigned';
                                            if(in_array($c['status'], ['Resolved', 'Closed', 'Case Closed'])) $badgeClass = 'badge-resolved';
                                            if($c['status'] == 'Rejected') $badgeClass = 'badge-rejected';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($c['status']); ?></span>
                                    </td>
                                    <td>
                                        <a href="view_case?id=<?php echo $c['id']; ?>" class="btn-gradient-cit" style="padding: 8px 16px; font-size: 0.8rem; border-radius: 10px;">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No recent reports found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-view">
                    <?php if(count($crimes) > 0): ?>
                        <div style="display: grid; gap: 1rem;">
                        <?php foreach(array_slice($crimes, 0, 5) as $c): ?>
                            <div style="background: white; border: 1px solid rgba(0,0,0,0.05); border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.8rem;">
                                    <div>
                                        <span style="font-family: monospace; font-weight: 700; color: var(--primary); font-size: 0.9rem;">#<?php echo $c['id']; ?></span>
                                        <h4 style="margin: 4px 0 0; font-size: 1rem; color: var(--primary);"><?php echo htmlspecialchars($c['crime_type']); ?></h4>
                                    </div>
                                    <?php 
                                        $badgeClass = 'badge-pending';
                                        if(in_array($c['status'], ['Assigned', 'Investigation Ongoing'])) $badgeClass = 'badge-assigned';
                                        if(in_array($c['status'], ['Resolved', 'Closed', 'Case Closed'])) $badgeClass = 'badge-resolved';
                                        if($c['status'] == 'Rejected') $badgeClass = 'badge-rejected';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($c['status']); ?></span>
                                </div>
                                
                                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                                    <i class="far fa-calendar-alt" style="width: 20px;"></i> <?php echo date('d M Y', strtotime($c['incident_date'])); ?>
                                </div>
                                
                                <a href="view_case?id=<?php echo $c['id']; ?>" class="btn-gradient-cit" style="width: 100%; justify-content: center;">
                                    View Full Details
                                </a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">No recent reports found.</div>
                    <?php endif; ?>
                </div>
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
