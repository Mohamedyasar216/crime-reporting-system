<?php
session_start();
require_once '../app/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_broadcast'])) {
    $title = htmlspecialchars($_POST['title']);
    $message = htmlspecialchars($_POST['message']);
    $priority = $_POST['priority'];
    $target = $_POST['target'];
    $admin_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO public_notices (admin_id, title, message, priority, target_role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$admin_id, $title, $message, $priority, $target])) {
        // Log the action
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$admin_id, "Broadcast Sent", "Title: $title, Target: $target"]);
        $msg = "Broadcast message sent successfully!";
    }
}

// Handle Delete Notice
if (isset($_POST['delete_notice'])) {
    $nid = $_POST['notice_id'];
    $pdo->prepare("DELETE FROM public_notices WHERE id = ?")->execute([$nid]);
    
    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], "Broadcast Deleted", "Notice ID: $nid"]);
    
    $msg = "Notice removed successfully.";
}

// Handle Edit Notice
if (isset($_POST['edit_notice'])) {
    $nid = $_POST['notice_id'];
    $title = htmlspecialchars($_POST['title']);
    $message = htmlspecialchars($_POST['message']);
    $priority = $_POST['priority'];
    $target = $_POST['target'];
    
    $stmt = $pdo->prepare("UPDATE public_notices SET title=?, message=?, priority=?, target_role=? WHERE id=?");
    $stmt->execute([$title, $message, $priority, $target, $nid]);

    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], "Broadcast Updated", "Notice ID: $nid, Title: $title"]);

    $msg = "Notice updated successfully.";
}

// Fetch Previous Notices
$notices = $pdo->query("SELECT * FROM public_notices ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Broadcast - Admin</title>
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

        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem; border: 1px solid #f1f5f9; }
        .form-control { width: 100%; padding: 0.85rem 1rem; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 1.25rem; background: #fff; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: var(--color-mint); box-shadow: 0 0 0 3px rgba(105, 164, 129, 0.1); }
        .notice-item { border-left: 5px solid var(--color-claret); background: #fff; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.25rem; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; }
        .p-Urgent { border-left-color: #ef4444; }
        .p-High { border-left-color: #f97316; }

        /* Standardized Buttons */
        .btn-primary {
            background: var(--btn-gradient) !important;
            color: var(--accent-cream) !important;
            border: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 54, 49, 0.3);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: var(--btn-hover-gradient) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 54, 49, 0.4);
        }

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
            <span class="page-title">Broadcast</span>
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
                <a href="admin_broadcast" class="nav-item active">
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
            <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; background: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button id="desktopToggle" style="background: var(--color-smoke); border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; color: var(--color-claret); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Public Notice</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Mass Communication Hub</p>
                    </div>
                </div>
                <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                </a>
            </div>

            <!-- Admin Banner -->
            <div class="admin-banner">
                <div class="banner-icon">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <div class="banner-info">
                    <h2>Emergency Broadcast</h2>
                    <p>
                        <span class="tag">System Alert</span>
                        <span><i class="fas fa-rss"></i> Status: Ready</span>
                        <span style="margin-left: 15px;"><i class="fas fa-shield-halved"></i> Global Reach</span>
                    </p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-clock"></i> Live Status: <?php echo date('H:i'); ?><br>
                    <?php echo date('d M, Y'); ?>
                </div>
            </div>

            <?php if($msg): ?>
                <div style="background: #f0fdf4; color: #16a34a; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #bbf7d0;">
                    <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <!-- Form -->
                <div class="card">
                    <h3 style="color: var(--primary-green);">New Broadcast</h3>
                    <form method="POST" style="margin-top: 1.5rem;">
                        <label class="text-muted small uppercase" style="font-weight: 700;">Notice Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Cyclone Alert / New System Update" required>
                        
                        <label class="text-muted small uppercase" style="font-weight: 700;">Message Content</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Detailed message..." required></textarea>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <label class="text-muted small uppercase" style="font-weight: 700;">Priority Level</label>
                                <select name="priority" class="form-control">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-muted small uppercase" style="font-weight: 700;">Target Audience</label>
                                <select name="target" class="form-control">
                                    <option value="all">Everyone (Public)</option>
                                    <option value="police">Police Force Only</option>
                                    <option value="citizen">Citizens Only</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" name="send_broadcast" class="btn-mixed" style="width: 100%; justify-content: center;">
                            <i class="fas fa-paper-plane"></i> Send Alert
                        </button>
                    </form>
                </div>

                <!-- History -->
                <div class="card">
                    <h3 style="color: var(--primary-green);">Recent Notices</h3>
                    <div style="margin-top: 1.5rem;">
                        <?php foreach($notices as $n): ?>
                        <div class="notice-item p-<?php echo $n['priority']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <strong style="display: block; color: var(--primary-dark);"><?php echo htmlspecialchars($n['title']); ?></strong>
                                    <span style="font-size: 0.7rem; color: #94a3b8;"><?php echo date('d M, Y', strtotime($n['created_at'])); ?></span>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick='openEdit(<?php echo json_encode($n); ?>)' class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Delete this notice?');" style="display: inline;">
                                        <input type="hidden" name="notice_id" value="<?php echo $n['id']; ?>">
                                        <button type="submit" name="delete_notice" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem; color: #dc2626; border-color: #fecaca;" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <p style="font-size: 0.85rem; color: #64748b; margin: 8px 0; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                                <?php echo htmlspecialchars($n['message']); ?>
                            </p>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span style="font-size: 0.7rem; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">Target: <?php echo ucfirst($n['target_role']); ?></span>
                                <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 600;">Priority: <?php echo $n['priority']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($notices)): ?>
                            <p class="text-muted">No notices sent yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal (Hidden by default) -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; width: 500px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 1.5rem; color: var(--primary-green);">Edit Broadcast Notice</h3>
            <form method="POST">
                <input type="hidden" name="notice_id" id="edit_notice_id">
                <input type="hidden" name="edit_notice" value="1">
                
                <label class="text-muted small uppercase" style="font-weight: 700;">Notice Title</label>
                <input type="text" name="title" id="edit_title" class="form-control" required>
                
                <label class="text-muted small uppercase" style="font-weight: 700;">Message Content</label>
                <textarea name="message" id="edit_message" class="form-control" rows="5" required></textarea>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="text-muted small uppercase" style="font-weight: 700;">Priority Level</label>
                        <select name="priority" id="edit_priority" class="form-control">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-muted small uppercase" style="font-weight: 700;">Target Audience</label>
                        <select name="target" id="edit_target" class="form-control">
                            <option value="all">Everyone (Public)</option>
                            <option value="police">Police Force Only</option>
                            <option value="citizen">Citizens Only</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-outline" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn-mixed" style="flex: 1; justify-content: center;">Update Notice</button>
                </div>
            </form>
        </div>
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
        function openEdit(notice) {
            document.getElementById('edit_notice_id').value = notice.id;
            document.getElementById('edit_title').value = notice.title;
            document.getElementById('edit_message').value = notice.message;
            document.getElementById('edit_priority').value = notice.priority;
            document.getElementById('edit_target').value = notice.target_role;
            
            document.getElementById('editModal').style.display = 'flex';
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>
