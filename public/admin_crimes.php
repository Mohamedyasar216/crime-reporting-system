<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

// Filters
$filter_district = $_GET['district'] ?? '';
$filter_id = $_GET['id'] ?? '';
$filter_days = $_GET['days'] ?? '';
$filter_group = $_GET['status_group'] ?? 'Total';

$sql = "SELECT c.*, u.full_name, u.mobile FROM crimes c JOIN users u ON c.user_id = u.id WHERE 1=1";
$params = [];

if ($filter_district) {
    $sql .= " AND c.district = ?";
    $params[] = $filter_district;
}

if ($filter_id) {
    $sql .= " AND c.id = ?";
    $params[] = $filter_id;
}

if ($filter_days) {
    $date_limit = date('Y-m-d', strtotime("-$filter_days days"));
    $sql .= " AND c.incident_date >= ?";
    $params[] = $date_limit;
}

if ($filter_group == 'Pending') {
    $sql .= " AND c.status = 'Pending'";
} elseif ($filter_group == 'Active') {
    $sql .= " AND c.status IN ('Assigned', 'Under Investigation', 'Investigation Ongoing')";
} elseif ($filter_group == 'Resolved') {
    $sql .= " AND c.status IN ('Resolved', 'Closed', 'Investigation Completed')";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$crimes = $stmt->fetchAll();

// Calculate Dashboard Stats
$total_cases = count($crimes);
$active_cases = 0;
$resolved_cases = 0;
foreach($crimes as $c) {
    if(in_array($c['status'], ['Pending', 'Assigned', 'Under Investigation', 'Investigation Ongoing'])) {
        $active_cases++;
    } elseif(in_array($c['status'], ['Resolved', 'Closed', 'Investigation Completed'])) {
        $resolved_cases++;
    }
}

// Get Districts for Filter
$districts = $pdo->query("SELECT DISTINCT district FROM crimes WHERE district IS NOT NULL ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Crimes - CRS Admin</title>
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
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(124, 31, 49, 0.3);
            opacity: 0.95;
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
            .sidebar .brand-text, .sidebar .nav-text { display: block !important; }
            
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
            <span class="page-title">Complaints</span>
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
                <a href="admin_crimes" class="nav-item active">
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
                <button class="collapse-btn" id="collapseBtn">
                    <i class="fas fa-angle-double-left" id="collapseIcon"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header Bar -->
            <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; background: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button id="desktopToggle" style="background: var(--color-smoke); border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; color: var(--color-claret); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Case Management</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Operational Oversight</p>
                    </div>
                </div>
                <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                </a>
            </div>

            <!-- Admin Banner -->
            <div class="admin-banner">
                <div class="banner-icon">
                    <i class="fas fa-folder-tree"></i>
                </div>
                <div class="banner-info">
                    <h2>Master Case Ledger</h2>
                    <p>
                        <span class="tag">System Records</span>
                        <span><i class="fas fa-clipboard-list"></i> Tracking: Active</span>
                        <span style="margin-left: 15px;"><i class="fas fa-shield-halved"></i> Audit Verified</span>
                    </p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-clock"></i> Live Status: <?php echo date('H:i'); ?><br>
                    <?php echo date('d M, Y'); ?>
                </div>
            </div>

            <!-- Stats Bar -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border-left: 5px solid #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                    <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Total Reported</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #1e293b;"><?php echo $total_cases; ?></div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border-left: 5px solid #ef4444; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                    <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Active / Pending</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #1e293b;"><?php echo $active_cases; ?></div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border-left: 5px solid #10b981; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                    <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">Resolved / Closed</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #1e293b;"><?php echo $resolved_cases; ?></div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" style="padding: 1rem 0; margin-bottom: 2rem; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end; border-bottom: 1px solid #e2e8f0;">
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Case ID</label>
                    <input type="text" name="id" value="<?php echo htmlspecialchars($filter_id); ?>" class="form-control" placeholder="Search ID..." style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem;">
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">District</label>
                    <select name="district" class="form-control" style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; cursor: pointer;">
                        <option value="">All Districts</option>
                        <?php foreach($districts as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php if($filter_district == $d) echo 'selected'; ?>><?php echo htmlspecialchars($d); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Time Period</label>
                    <select name="days" class="form-control" style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; cursor: pointer;">
                        <option value="">All Time</option>
                        <option value="1" <?php if($filter_days == '1') echo 'selected'; ?>>Last 24 Hours</option>
                        <option value="7" <?php if($filter_days == '7') echo 'selected'; ?>>Last 7 Days</option>
                        <option value="30" <?php if($filter_days == '30') echo 'selected'; ?>>Last 30 Days</option>
                        <option value="90" <?php if($filter_days == '90') echo 'selected'; ?>>Last 3 Months</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 180px;">
                    <label style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Status</label>
                    <select name="status_group" class="form-control" style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; cursor: pointer;">
                        <option value="Total" <?php if($filter_group == 'Total') echo 'selected'; ?>>Total Cases</option>
                        <option value="Pending" <?php if($filter_group == 'Pending') echo 'selected'; ?>>Pending Cases</option>
                        <option value="Active" <?php if($filter_group == 'Active') echo 'selected'; ?>>Active Cases</option>
                        <option value="Resolved" <?php if($filter_group == 'Resolved') echo 'selected'; ?>>Resolved Cases</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="submit" class="btn-mixed" style="padding: 12px 24px; font-size: 1rem;"><i class="fas fa-filter"></i> Apply Filter</button>
                    <a href="admin_crimes" class="btn" style="padding: 12px 20px; border-radius: 10px; border: 1px solid #cbd5e1; color: #64748b; text-decoration: none; font-size: 1rem; font-weight: 600;">Reset</a>
                </div>

            </form>

            <!-- Desktop Table View -->
            <div class="table-responsive desktop-view" style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #e2e8f0; color: var(--text-muted);">
                            <th style="padding: 10px;">Date</th>
                            <th style="padding: 10px;">Case ID</th>
                            <th style="padding: 10px;">Type</th>
                            <th style="padding: 10px;">Description</th>
                            <th style="padding: 10px;">District</th>
                            <th style="padding: 10px;">Reported By</th>
                            <th style="padding: 10px;">Status</th>
                            <th style="padding: 10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($crimes as $c): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 15px 10px; white-space: nowrap;"><?php echo date('d M Y', strtotime($c['incident_date'])); ?></td>
                            <td style="padding: 10px; font-weight: 700; color: var(--primary-green);">#<?php echo $c['id']; ?></td>
                            <td style="padding: 10px;"><strong style="color: var(--primary-green);"><?php echo htmlspecialchars($c['crime_type']); ?></strong></td>
                            <td style="padding: 10px; font-size: 0.9rem; max-width: 300px;">
                                <?php echo htmlspecialchars(substr($c['description'], 0, 80)) . '...'; ?>
                            </td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($c['district']); ?></td>
                            <td style="padding: 10px;">
                                <?php echo htmlspecialchars($c['full_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($c['mobile']); ?></small>
                            </td>
                            <td style="padding: 10px;">
                                <?php 
                                    $bg = '#f1f5f9'; $col = '#475569';
                                    switch($c['status']) {
                                        case 'Pending': $bg='#fff7ed'; $col='#c2410c'; break;
                                        case 'Assigned': $bg='#dbeafe'; $col='#1e40af'; break;
                                        case 'Investigation Ongoing': $bg='#e0e7ff'; $col='#3730a3'; break;
                                        case 'Action Taken': $bg='#ffedd5'; $col='#9a3412'; break;
                                        case 'Resolved': 
                                        case 'Closed':
                                        case 'Case Closed': $bg='#dcfce7'; $col='#166534'; break;
                                        case 'Rejected': $bg='#fee2e2'; $col='#991b1b'; break;
                                        default: $bg='#f1f5f9'; $col='#475569';
                                    }
                                ?>
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                    <?php echo $c['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 10px;">
                                <a href="admin_view_case?id=<?php echo $c['id']; ?>" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="mobile-view">
                <?php if(count($crimes) > 0): ?>
                    <div style="display: grid; gap: 1rem;">
                    <?php foreach($crimes as $c): ?>
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.2rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.8rem;">
                                <div>
                                    <span style="font-family: monospace; font-weight: 700; color: var(--primary-green); font-size: 0.9rem;">#<?php echo $c['id']; ?></span>
                                    <h4 style="margin: 4px 0 0; font-size: 1.1rem; color: #1e293b;"><?php echo htmlspecialchars($c['crime_type']); ?></h4>
                                </div>
                                <?php 
                                    $bg = '#f1f5f9'; $col = '#475569';
                                    switch($c['status']) {
                                        case 'Pending': $bg='#fff7ed'; $col='#c2410c'; break;
                                        case 'Assigned': $bg='#dbeafe'; $col='#1e40af'; break;
                                        case 'Investigation Ongoing': $bg='#e0e7ff'; $col='#3730a3'; break;
                                        case 'Action Taken': $bg='#ffedd5'; $col='#9a3412'; break;
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
                            
                            <div style="display: grid; grid-template-columns: 1fr; gap: 0.8rem; font-size: 0.9rem; color: #64748b; margin-bottom: 1rem;">
                                <div>
                                    <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px;">Incident Date</strong>
                                    <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($c['incident_date'])); ?>
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px;">Location</strong>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['district']); ?>
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px;">Reporter</strong>
                                    <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($c['full_name']); ?> (<?php echo htmlspecialchars($c['mobile']); ?>)
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px;">Description</strong>
                                    <span style="font-size: 0.85rem; display: block; background: #f8fafc; padding: 0.5rem; border-radius: 6px;">
                                        <?php echo htmlspecialchars(substr($c['description'], 0, 100)) . '...'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <a href="admin_view_case?id=<?php echo $c['id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                View Full Case
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
                }
            </style>
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
            if(collapseIcon) collapseIcon.classList.replace('fa-angle-double-left', 'fa-angle-double-right');
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
</body>
</html>
