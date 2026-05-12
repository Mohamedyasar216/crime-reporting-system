<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

// 1. Fetch Stats
// Total Crimes
$totalCrimes = $pdo->query("SELECT COUNT(*) FROM crimes")->fetchColumn();
// Active Cases (Pending, Assigned, Investigation Ongoing)
$activeCases = $pdo->query("SELECT COUNT(*) FROM crimes WHERE status IN ('Pending', 'Assigned', 'Under Investigation', 'Investigation Ongoing')")->fetchColumn();
// Resolved Cases (Resolved, Closed)
$resolvedCases = $pdo->query("SELECT COUNT(*) FROM crimes WHERE status IN ('Resolved', 'Closed', 'Investigation Completed')")->fetchColumn();
// Total Police
$totalPolice = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'police'")->fetchColumn();

// 2. Fetch Recent Reports
$stmt = $pdo->query("SELECT c.*, u.full_name as reporter_name FROM crimes c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 5");
$recentCrimes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Crime Reporting System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            
            --btn-gradient: var(--grad-mint);
            --btn-hover-gradient: linear-gradient(135deg, #588c6d 0%, #7aa68d 100%);
        }
        
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
        
        body { background: var(--bg-body); }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            background: var(--bg-body);
            padding: 2.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 0;
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }

        .toggle-sidebar-btn {
            background: var(--color-smoke);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--color-claret);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .toggle-sidebar-btn:hover { background: #dce3e1; }

        /* Collapsible Section Styles */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
            padding: 0.5rem 0;
        }
        .section-content {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            max-height: 2000px;
            opacity: 1;
        }
        .section-content.collapsed {
            max-height: 0;
            opacity: 0;
            margin-top: 0;
            margin-bottom: 0;
            pointer-events: none;
        }
        .chevron-icon {
            transition: transform 0.3s;
        }
        .section-content.collapsed + .chevron-icon,
        .collapsed .chevron-icon {
            transform: rotate(-90deg);
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px; height: 60px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            color: white;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .icon-total { background: var(--grad-mixed); }
        .icon-active { background: var(--grad-claret); }
        .icon-resolved { background: var(--grad-mint); }
        .icon-police { background: #475569; }

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

        .grid-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }
        .recent-table th {
            text-align: left;
            padding: 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 2px solid #f1f5f9;
        }
        .recent-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        /* Standardized Buttons */
        .btn-primary {
            background: var(--grad-mint) !important;
            color: white !important;
            border: none;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(105, 164, 129, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(105, 164, 129, 0.4);
            opacity: 0.95;
        }

        .text-primary-theme {
            color: var(--color-claret);
        }

        /* Mobile Adjustments */
        @media (max-width: 1024px) {
            .grid-main { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 2000;
            }
            .sidebar.active { transform: translateX(0); }
            .sidebar.collapsed { width: 280px; }
            .sidebar .brand-text, .sidebar .nav-text { display: block !important; }
            
            .main-content { margin-left: 0 !important; padding: 1.5rem; padding-top: 150px !important; }
            
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
                border-bottom: 2px solid var(--primary-green);
            }
            .mobile-header .page-title {
                font-weight: 800;
                color: var(--color-claret) !important;
                font-size: 1.1rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .top-bar { display: none !important; }
            .sidebar { z-index: 4000; }
            .sidebar-overlay { z-index: 3500; }

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

            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>
<body>


    <!-- Mobile Header -->
    <div class="mobile-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <button id="sidebarToggle" style="background: #f1f5f9; border: none; width: 38px; height: 38px; border-radius: 8px; font-size: 1.1rem; color: var(--primary-green); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title">Dashboard</span>
        </div>
        <a href="admin_dashboard" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-alt" style="color: var(--color-claret);"></i>
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
                <a href="admin_dashboard" class="nav-item active">
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

            <div style="padding: 0 1rem; margin-bottom: 1rem; display: none;" class="logout-btn-sidebar">
                <a href="logout" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; color: inherit; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <div class="sidebar-footer">
                <a href="logout" class="nav-item logout-link" style="color: #ef4444; margin-bottom: 0.5rem; justify-content: center;">
                    <i class="fas fa-sign-out-alt"></i> 
                    <span class="nav-text">Logout</span>
                </a>
                <button class="collapse-btn" id="collapseBtn" title="Toggle Sidebar">
                    <i class="fas fa-angle-double-left" id="collapseIcon"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header Bar -->
            <div class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="toggle-sidebar-btn" id="desktopToggle" title="Toggle Sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Dashboard</h2>
                        <p class="text-muted" style="font-size: 0.8rem; margin: 0;">Central Command</p>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div style="text-align: right; display: none; @media (min-width: 768px) { display: block; }">
                        <p style="font-size: 0.85rem; font-weight: 600; color: var(--color-claret);">Administrator</p>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0;">Main Hub</p>
                    </div>
                    <a href="logout" class="btn btn-outline" style="border-color: #fee2e2; color: #dc2626; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 700;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Admin Banner -->
            <div class="admin-banner">
                <div class="banner-icon">
                    <i class="fas fa-user-gear"></i>
                </div>
                <div class="banner-info">
                    <h2>System Control Center</h2>
                    <p>
                        <span class="tag">Superuser</span>
                        <span><i class="fas fa-microchip"></i> Core Status: Online</span>
                        <span style="margin-left: 15px;"><i class="fas fa-shield-halved"></i> Integrity Verified</span>
                    </p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-clock"></i> Live Status: <?php echo date('H:i'); ?><br>
                    <?php echo date('d M, Y'); ?>
                </div>
            </div>

            <div style="margin-bottom: 2.5rem;">
                <div class="section-header" onclick="toggleSection('overviewSection', 'overviewChevron')">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 8px; height: 28px; background: var(--color-claret); border-radius: 4px;"></div>
                        <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800; color: #1e293b; letter-spacing: -0.5px;">System Overview</h3>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <a href="admin_crimes" class="btn-mixed" style="padding: 0.65rem 1.25rem; border-radius: 10px;">
                            <i class="fas fa-search" style="margin-right: 8px;"></i> Inspect Reports
                        </a>
                        <i class="fas fa-chevron-down chevron-icon" id="overviewChevron" style="font-size: 1.2rem; color: #94a3b8;"></i>
                    </div>
                </div>

                <!-- Stats Grid (Collapsible) -->
                <div class="section-content" id="overviewSection" style="margin-top: 1.5rem;">
                    <div class="stat-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
                        <!-- Total -->
                        <div class="stat-card">
                            <div class="stat-icon icon-total">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.8rem; color: #1e293b; margin: 0;"><?php echo $totalCrimes; ?></h3>
                                <span style="font-size: 0.85rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Reports</span>
                            </div>
                        </div>
                        <!-- Active -->
                        <div class="stat-card">
                            <div class="stat-icon icon-active">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.8rem; color: #1e293b; margin: 0;"><?php echo $activeCases; ?></h3>
                                <span style="font-size: 0.85rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Active</span>
                            </div>
                        </div>
                        <!-- Resolved -->
                        <div class="stat-card">
                            <div class="stat-icon icon-resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.8rem; color: #1e293b; margin: 0;"><?php echo $resolvedCases; ?></h3>
                                <span style="font-size: 0.85rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Resolved</span>
                            </div>
                        </div>
                        <!-- Police -->
                        <div class="stat-card">
                            <div class="stat-icon icon-police">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.8rem; color: #1e293b; margin: 0;"><?php echo $totalPolice; ?></h3>
                                <span style="font-size: 0.85rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Personnel</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-main">
                
                <!-- Recent Table (Collapsible) -->
                <div style="background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; overflow: hidden;">
                    <div class="section-header" style="padding: 1.5rem;" onclick="toggleSection('tableSection', 'tableChevron')">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 8px; height: 24px; background: var(--color-claret); border-radius: 4px;"></div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Detailed Crime Activities</h3>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <a href="admin_crimes" style="color: var(--color-claret); font-weight: 700; font-size: 0.9rem; text-decoration: none;">View All</a>
                            <i class="fas fa-chevron-down chevron-icon" id="tableChevron" style="font-size: 1rem; color: #94a3b8;"></i>
                        </div>
                    </div>

                    <div class="section-content" id="tableSection" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                        <div class="table-responsive desktop-view">
                            <table class="recent-table">
                                <thead>
                                    <tr>
                                        <th>Case ID</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Reporter</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentCrimes as $crime): ?>
                                    <tr>
                                        <td><span style="font-family: monospace; font-weight: 700; color: var(--primary-green);">#<?php echo $crime['id']; ?></span></td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--primary-green);"><?php echo htmlspecialchars($crime['crime_type']); ?></div>
                                            <small style="color: #64748b; font-size: 0.75rem;"><?php echo date('d M Y', strtotime($crime['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem; color: #475569; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-map-marker-alt" style="color: #94a3b8; font-size: 0.8rem;"></i>
                                                <?php echo htmlspecialchars($crime['district']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem; color: #475569;"><?php echo htmlspecialchars($crime['reporter_name']); ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                                $sStyle = 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
                                                
                                                switch($crime['status']) {
                                                    case 'Pending': 
                                                        $sStyle = 'background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;';
                                                        break;
                                                    case 'Assigned': 
                                                        $sStyle = 'background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;'; 
                                                        break;
                                                    case 'Investigation Ongoing': 
                                                        $sStyle = 'background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;';
                                                        break;
                                                    case 'Action Taken':
                                                        $sStyle = 'background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa;';
                                                        break;
                                                    case 'Resolved':
                                                    case 'Closed':
                                                    case 'Case Closed':
                                                        $sStyle = 'background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;';
                                                        break;
                                                    case 'Rejected':
                                                        $sStyle = 'background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;';
                                                        break;
                                                    default:
                                                        $sStyle = 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
                                                }
                                            ?>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; <?php echo $sStyle; ?>">
                                                <?php echo strtoupper($crime['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_view_case?id=<?php echo $crime['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; border-radius: 6px;">
                                                Manage <i class="fas fa-chevron-right" style="margin-left: 4px; font-size: 0.6rem;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-view">
                            <?php if(count($recentCrimes) > 0): ?>
                                <div style="display: grid; gap: 1rem;">
                                <?php foreach($recentCrimes as $crime): ?>
                                    <div class="card" style="padding: 1rem; border: 1px solid #e2e8f0; box-shadow: none;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; padding-bottom: 0.8rem; border-bottom: 1px dashed #e2e8f0;">
                                            <div>
                                                <span style="font-family: monospace; font-weight: 700; color: var(--primary-green); font-size: 0.9rem;">#<?php echo $crime['id']; ?></span>
                                                <div style="font-weight: 700; color: var(--primary-green); font-size: 1rem; margin-top: 4px;"><?php echo htmlspecialchars($crime['crime_type']); ?></div>
                                            </div>
                                            <?php 
                                                $sStyle = 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;';
                                                switch($crime['status']) {
                                                    case 'Pending': $sStyle = 'background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;'; break;
                                                    case 'Assigned': $sStyle = 'background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;'; break;
                                                    case 'Investigation Ongoing': $sStyle = 'background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;'; break;
                                                    case 'Action Taken': $sStyle = 'background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa;'; break;
                                                    case 'Resolved':
                                                    case 'Closed':
                                                    case 'Case Closed': $sStyle = 'background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;'; break;
                                                    case 'Rejected': $sStyle = 'background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;'; break;
                                                }
                                            ?>
                                            <span style="padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; <?php echo $sStyle; ?>">
                                                <?php echo strtoupper($crime['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
                                            <div>
                                                <i class="fas fa-calendar-alt" style="margin-right: 6px;"></i>
                                                <?php echo date('d M Y', strtotime($crime['created_at'])); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-map-marker-alt" style="margin-right: 6px;"></i>
                                                <?php echo htmlspecialchars($crime['district']); ?>
                                            </div>
                                            <div style="grid-column: span 2;">
                                                <i class="fas fa-user" style="margin-right: 6px;"></i>
                                                Reported by: <strong><?php echo htmlspecialchars($crime['reporter_name']); ?></strong>
                                            </div>
                                        </div>

                                        <a href="admin_view_case?id=<?php echo $crime['id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.6rem;">
                                            Manage Case
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No recent activity.</p>
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
                </div>

                <!-- Right Column: Chart & Actions -->
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    
                    <!-- Case Status Distribution Chart -->
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom: 1.5rem;">Case Status Distribution</h3>
                        <?php
                        $statusCounts = [
                            'Pending' => 0,
                            'Assigned' => 0,
                            'Action Taken' => 0,
                            'Resolved' => 0,
                            'Closed' => 0
                        ];
                        $stmtStatus = $pdo->query("SELECT status, COUNT(*) as count FROM crimes GROUP BY status");
                        while($row = $stmtStatus->fetch()) {
                            if(isset($statusCounts[$row['status']])) {
                                $statusCounts[$row['status']] = $row['count'];
                            }
                        }
                        ?>
                        <div style="width: 100%; max-width: 250px; margin: 0 auto;">
                            <canvas id="adminStatusChart"></canvas>
                        </div>
                        <div style="margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem;">
                            <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #f59e0b;"></span> Pending</div>
                            <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #3b82f6;"></span> Assigned</div>
                            <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #f97316;"></span> Action Taken</div>
                            <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #10b981;"></span> Resolved</div>
                            <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #059669;"></span> Closed</div>
                        </div>
                    </div>

                    <!-- Admin Action Card -->
                    <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3>Quick Actions</h3>
                        <div style="display: grid; gap: 1rem; margin-top: 1.5rem;">
                            <a href="admin_logs" style="display: flex; align-items: center; gap: 15px; padding: 1rem; background: #f8fafc; border-radius: 8px; transition: all 0.2s; border: 1px solid transparent;">
                                <div style="background: #64748b; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1rem;">System Logs</h4>
                                    <small class="text-muted">Audit tracker</small>
                                </div>
                            </a>

                            <a href="admin_broadcast" style="display: flex; align-items: center; gap: 15px; padding: 1rem; background: #fef2f2; border-radius: 8px; transition: all 0.2s; border: 1px solid #fee2e2;">
                                <div style="background: #dc2626; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1rem; color: #991b1b;">Emergency Broadcast</h4>
                                    <small class="text-muted">Send alerts</small>
                                </div>
                            </a>
                        </div>
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

        const desktopToggle = document.getElementById('desktopToggle');

        // Toggle Sidebar (Desktop Collapse via Header)
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

        // Toggle Sidebar (Mobile Drawer)
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        // Dashboard Collapsible Sections Logic
        function toggleSection(sectionId, chevronId) {
            const section = document.getElementById(sectionId);
            const chevron = document.getElementById(chevronId);
            
            section.classList.toggle('collapsed');
            
            if (section.classList.contains('collapsed')) {
                chevron.style.transform = 'rotate(-90deg)';
            } else {
                chevron.style.transform = 'rotate(0deg)';
            }
        }

        // Close mobile sidebar on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('adminStatusChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Assigned', 'Action Taken', 'Resolved', 'Closed'],
                    datasets: [{
                        data: [
                            <?php echo $statusCounts['Pending']; ?>,
                            <?php echo $statusCounts['Assigned']; ?>,
                            <?php echo $statusCounts['Action Taken']; ?>,
                            <?php echo $statusCounts['Resolved']; ?>,
                            <?php echo $statusCounts['Closed']; ?>
                        ],
                        backgroundColor: ['#f59e0b', '#3b82f6', '#f97316', '#10b981', '#059669'],
                        hoverOffset: 10,
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</body>
</html>
