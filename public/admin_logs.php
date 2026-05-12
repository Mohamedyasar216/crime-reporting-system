<?php
session_start();
require_once '../app/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}

// Fetch Logs
$stmt = $pdo->query("SELECT l.*, u.full_name, u.role FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50");
$logs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin</title>
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

        .log-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; overflow-x: auto; }
        .log-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .log-table th, .log-table td { padding: 1.25rem 1rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
        .log-table th { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        
        /* Updated Badges to match theme */
        .badge-admin { background: #fee2e2; color: #7C1F31; border: 1px solid #fecaca; }
        .badge-police { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-citizen { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .badge-system { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }

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

    <!-- Mobile Header -->
    <div class="mobile-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <button id="sidebarToggle" style="background: #f1f5f9; border: none; width: 38px; height: 38px; border-radius: 8px; font-size: 1.1rem; color: var(--color-claret); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title">System Logs</span>
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
                <a href="admin_logs" class="nav-item active">
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
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Audit Logs</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">System Traceability</p>
                    </div>
                </div>
                <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                </a>
            </div>

            <!-- Admin Banner -->
            <div class="admin-banner">
                <div class="banner-icon">
                    <i class="fas fa-terminal"></i>
                </div>
                <div class="banner-info">
                    <h2>Operational History</h2>
                    <p>
                        <span class="tag">Security Audit</span>
                        <span><i class="fas fa-user-secret"></i> Logging: Enabled</span>
                        <span style="margin-left: 15px;"><i class="fas fa-shield-halved"></i> Integrity Verified</span>
                    </p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-clock"></i> Live Status: <?php echo date('H:i'); ?><br>
                    <?php echo date('d M, Y'); ?>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="log-card desktop-view">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('d M, H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></strong></td>
                            <td><span class="badge badge-<?php echo $log['role'] ?? 'system'; ?>"><?php echo $log['role'] ?? 'System'; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                            <td style="color: #64748b; font-size: 0.9rem;"><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($logs)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 2rem; color: #94a3b8;">No audit logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="mobile-view">
                <?php if(count($logs) > 0): ?>
                    <div style="display: grid; gap: 1rem;">
                    <?php foreach($logs as $log): ?>
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span class="badge badge-<?php echo $log['role'] ?? 'system'; ?>"><?php echo $log['role'] ?? 'System'; ?></span>
                                <small style="color: #64748b; font-size: 0.8rem;"><?php echo date('d M, H:i', strtotime($log['created_at'])); ?></small>
                            </div>
                            <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #1e293b;"><?php echo htmlspecialchars($log['action']); ?></h4>
                            <div style="font-size: 0.85rem; color: #475569; margin-bottom: 0.8rem; line-height: 1.4;"><?php echo htmlspecialchars($log['details']); ?></div>
                             <div style="font-size: 0.8rem; color: #94a3b8; border-top: 1px dashed #e2e8f0; padding-top: 8px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-user-circle"></i>
                                <strong><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></strong>
                             </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                     <div style="text-align: center; padding: 2rem; color: #64748b;">No logs found.</div>
                <?php endif; ?>
            </div>

            <style>
                .mobile-view { display: none; }
                @media (max-width: 768px) {
                    .desktop-view { display: none !important; }
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
