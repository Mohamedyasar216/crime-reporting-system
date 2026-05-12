<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

// Handle Login As Police
if (isset($_POST['login_as_police'])) {
    $id = $_POST['police_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'police'");
    $stmt->execute([$id]);
    $police = $stmt->fetch();

    if ($police) {
        // Log the action for security audit
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], "Admin Masquerade", "Logged in as Police: " . $police['full_name']]);

        // Transfer session to target user
        $_SESSION['user_id'] = $police['id'];
        $_SESSION['user_name'] = $police['full_name'];
        $_SESSION['user_role'] = $police['role'];
        
        header("Location: police_dashboard");
        exit;
    }
}

// Handle Delete
if (isset($_POST['delete_police'])) {
    $id = $_POST['delete_id'];
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'police'")->execute([$id]);

    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], "Officer Suspended", "Officer ID: $id"]);

    $msg = "Officer removed successfully.";
}

// Handle Edit (Update)
if (isset($_POST['edit_police'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['name'];
    $rank = $_POST['rank'];
    $district = $_POST['district'];
    
    $pdo->prepare("UPDATE users SET full_name=?, rank=?, district=? WHERE id=?")->execute([$name, $rank, $district, $id]);

    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], "Officer Updated", "Name: $name, ID: $id"]);

    $msg = "Officer details updated successfully.";
}

// Handle Add Police
if (isset($_POST['add_police'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];

    // Backend Gmail Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
        $error = "Error: Please provide a valid Gmail account (e.g., ex@gmail.com).";
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rank = $_POST['rank'];
        $district = $_POST['district'];
        $role = 'police';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, rank, district, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $rank, $district, $role]);

            // Log the action
            $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], "Officer Registered", "Name: $name, Email: $email"]);

            $msg = "Officer registered successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch All Police with Case Counts
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM crimes WHERE assigned_to = u.id) as case_count,
        (SELECT COUNT(*) FROM crimes WHERE assigned_to = u.id AND status IN ('Resolved', 'Closed')) as resolved_count
        FROM users u WHERE u.role = 'police' ORDER BY u.created_at DESC";
$officers = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Police - CRS Admin</title>
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

        /* Responsive Visibility */
        .mobile-view { display: none; }
        
        @media (max-width: 768px) {
            .desktop-view { display: none !important; }
            .mobile-view { display: block; }
        }

        /* Page Specific Styles */
        .grid-stack {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
            .main-content { margin-left: 0 !important; padding: 1.5rem; padding-top: 8rem !important; overflow-x: hidden; }
            
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
                width: 100%;
                box-sizing: border-box;
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

            .add-police-btn {
                padding: 0.5rem 0.8rem !important;
                font-size: 0.85rem !important;
            }
            /* Mobile Form Adjustments */
            #addForm {
                padding: 1.25rem !important;
                margin-bottom: 1.5rem !important;
            }
            .grid-stack {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }
            .form-control {
                padding: 0.6rem 0.85rem !important;
                font-size: 0.9rem !important;
            }

            /* Header Section Fix */
            .main-content > div:first-child {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 1rem !important;
                margin-bottom: 1.5rem !important;
            }
            .main-content h2 { font-size: 1.4rem !important; margin: 0 !important; }
            .btn-mixed.add-police-btn { 
                width: auto !important; 
                font-size: 0.75rem !important; 
                padding: 0.6rem 1rem !important;
            }

            /* Card Detail Fixes */
            .mobile-view .card-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.75rem !important;
            }
            .mobile-view .card-actions {
                width: 100% !important;
                justify-content: flex-start !important;
                gap: 12px !important;
            }
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
            <span class="page-title">Police Management</span>
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
                <a href="admin_police" class="nav-item active">
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

        <main class="main-content" id="mainContent">
            <!-- Header Bar -->
            <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; background: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button id="desktopToggle" style="background: var(--color-smoke); border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; color: var(--color-claret); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Personnel Registry</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Manage Law Enforcement Officers</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button onclick="document.getElementById('addForm').style.display='block'" class="btn-mixed add-police-btn">
                        <i class="fas fa-plus"></i> Register Personnel
                    </button>
                    <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: #ef4444 !important;">
                        <i class="fas fa-sign-out-alt"></i> LOGOUT
                    </a>
                </div>
            </div>

            <!-- Admin Banner -->
            <div class="admin-banner">
                <div class="banner-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="banner-info">
                    <h2>Force Management Center</h2>
                    <p>
                        <span class="tag">Security Core</span>
                        <span><i class="fas fa-id-card"></i> active Duty: Enabled</span>
                        <span style="margin-left: 15px;"><i class="fas fa-shield-halved"></i> Badge Verified</span>
                    </p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-clock"></i> System Time: <?php echo date('H:i'); ?><br>
                    <?php echo date('d M, Y'); ?>
                </div>
            </div>

            <?php if(isset($msg)) echo "<div style='padding: 1rem; background: #dcfce7; color: #166534; margin-bottom: 1rem; border-radius: 6px;'>$msg</div>"; ?>
            <?php if(isset($error)) echo "<div style='padding: 1rem; background: #fee2e2; color: #991b1b; margin-bottom: 1rem; border-radius: 6px;'>$error</div>"; ?>

            <!-- Add Form (Hidden by default) -->
            <div id="addForm" style="display: none; background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="margin-bottom: 1rem; color: var(--primary-green);">Register New Officer</h3>
                <form method="POST">
                    <input type="hidden" name="add_police" value="1">
                    <div class="grid-stack" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label>District</label>
                            <select name="district" class="form-control" required>
                                <option value="">Select District</option>
                                <option value="Ariyalur">Ariyalur</option>
                                <option value="Chengalpattu">Chengalpattu</option>
                                <option value="Chennai">Chennai</option>
                                <option value="Coimbatore">Coimbatore</option>
                                <option value="Cuddalore">Cuddalore</option>
                                <option value="Dharmapuri">Dharmapuri</option>
                                <option value="Dindigul">Dindigul</option>
                                <option value="Erode">Erode</option>
                                <option value="Kallakurichi">Kallakurichi</option>
                                <option value="Kanchipuram">Kanchipuram</option>
                                <option value="Kanyakumari">Kanyakumari</option>
                                <option value="Karur">Karur</option>
                                <option value="Krishnagiri">Krishnagiri</option>
                                <option value="Madurai">Madurai</option>
                                <option value="Mayiladuthurai">Mayiladuthurai</option>
                                <option value="Nagapattinam">Nagapattinam</option>
                                <option value="Namakkal">Namakkal</option>
                                <option value="Nilgiris">Nilgiris</option>
                                <option value="Perambalur">Perambalur</option>
                                <option value="Pudukkottai">Pudukkottai</option>
                                <option value="Ramanathapuram">Ramanathapuram</option>
                                <option value="Ranipet">Ranipet</option>
                                <option value="Salem">Salem</option>
                                <option value="Sivaganga">Sivaganga</option>
                                <option value="Tenkasi">Tenkasi</option>
                                <option value="Thanjavur">Thanjavur</option>
                                <option value="Theni">Theni</option>
                                <option value="Thoothukudi">Thoothukudi</option>
                                <option value="Tiruchirappalli">Tiruchirappalli</option>
                                <option value="Tirunelveli">Tirunelveli</option>
                                <option value="Tirupathur">Tirupathur</option>
                                <option value="Tiruppur">Tiruppur</option>
                                <option value="Tiruvallur">Tiruvallur</option>
                                <option value="Tiruvannamalai">Tiruvannamalai</option>
                                <option value="Tiruvarur">Tiruvarur</option>
                                <option value="Vellore">Vellore</option>
                                <option value="Viluppuram">Viluppuram</option>
                                <option value="Virudhunagar">Virudhunagar</option>
                                <option value="Tamil Nadu">Tamil Nadu (State HQ / DGP)</option>
                            </select>
                        </div>
                         <div>
                            <label>Posting Name / Rank</label>
                            <select name="rank" class="form-control">
                                <option>Constable</option>
                                <option>Sub-Inspector</option>
                                <option>Inspector</option>
                                <option>DSP</option>
                                <option>SP</option>
                                <option>DGP</option>
                            </select>
                        </div>
                        <div>
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required 
                                   pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" 
                                   title="Please enter a valid Gmail address (e.g., user@gmail.com)">
                        </div>
                        <div>
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                    </div>
                    <div style="margin-top: 1rem; text-align: right;">
                        <button type="button" onclick="document.getElementById('addForm').style.display='none'" class="btn btn-outline">Cancel</button>
                        <button type="submit" class="btn-mixed">Register Officer</button>
                    </div>
                </form>
            </div>

            <!-- List -->
            <!-- Desktop Table View -->
            <div class="table-responsive desktop-view" style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #e2e8f0; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 1rem;">Officer Details</th>
                            <th style="padding: 1rem;">Jurisdiction</th>
                            <th style="padding: 1rem;">Workload</th>
                            <th style="padding: 1rem;">Performance</th>
                            <th style="padding: 1rem;">Status</th>
                            <th style="padding: 1rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($officers as $off): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: rgba(0, 54, 49, 0.1); color: var(--primary-green); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                        <?php echo substr($off['full_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <strong style="color: var(--primary-green);"><?php echo htmlspecialchars($off['full_name']); ?></strong><br>
                                        <small style="color: #64748b; font-size: 0.8rem;"><?php echo htmlspecialchars($off['rank']); ?></small><br>
                                        <small style="color: #64748b; font-size: 0.8rem;"><i class="fas fa-envelope" style="font-size: 0.7rem;"></i> <?php echo htmlspecialchars($off['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($off['district'] ?? 'N/A'); ?></span>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: 600; font-size: 1.1rem; color: #1e293b;"><?php echo $off['case_count']; ?></span>
                                    <small class="text-muted">Total Cases</small>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <?php 
                                    $rate = ($off['case_count'] > 0) ? round(($off['resolved_count'] / $off['case_count']) * 100) : 0;
                                    $col = $rate > 70 ? '#16a34a' : ($rate > 40 ? '#ca8a04' : '#ef4444');
                                ?>
                                <div style="width: 120px; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; margin-bottom: 4px;">
                                    <div style="width: <?php echo $rate; ?>%; height: 100%; background: <?php echo $col; ?>;"></div>
                                </div>
                                <small style="color: <?php echo $col; ?>; font-weight: 700;"><?php echo $rate; ?>% Resolution</small>
                            </td>
                            <td style="padding: 1rem;"><span style="color: green; background: #dcfce7; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700;">ACTIVE</span></td>
                            <td style="padding: 1rem; display: flex; gap: 8px;">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="police_id" value="<?php echo $off['id']; ?>">
                                    <button type="submit" name="login_as_police" class="btn btn-outline" style="color: var(--primary-green); border-color: var(--sidebar-border); padding: 0.4rem; width: 32px; height: 32px;" title="Login as Officer">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </button>
                                </form>
                                <button onclick='openEdit(<?php echo json_encode($off); ?>)' class="btn btn-outline" style="padding: 0.4rem; width: 32px; height: 32px;" title="Edit Officer">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" onsubmit="return confirm('Suspend this account?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $off['id']; ?>">
                                    <button type="submit" name="delete_police" class="btn btn-outline" style="color: #dc2626; border-color: #fecaca; padding: 0.4rem; width: 32px; height: 32px;" title="Suspend Officer">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="mobile-view">
                <?php if(count($officers) > 0): ?>
                    <div style="display: grid; gap: 1rem;">
                    <?php foreach($officers as $off): ?>
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.2rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.8rem;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <div style="width: 36px; height: 36px; background: rgba(0, 54, 49, 0.1); color: var(--primary-green); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                        <?php echo substr($off['full_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; font-size: 1rem; color: #1e293b;"><?php echo htmlspecialchars($off['full_name']); ?></h4>
                                        <small style="color: #64748b; font-size: 0.8rem;"><?php echo htmlspecialchars($off['rank']); ?></small>
                                    </div>
                                </div>
                                <div class="card-actions" style="display: flex; gap: 6px;">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="police_id" value="<?php echo $off['id']; ?>">
                                        <button type="submit" name="login_as_police" class="btn btn-outline" style="padding: 0.3rem; width: 30px; height: 30px; color: var(--primary-green);" title="Login as Officer">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </button>
                                    </form>
                                    <button onclick='openEdit(<?php echo json_encode($off); ?>)' class="btn btn-outline" style="padding: 0.3rem; width: 30px; height: 30px;" title="Edit Officer">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Suspend this account?');" style="margin:0;">
                                        <input type="hidden" name="delete_id" value="<?php echo $off['id']; ?>">
                                        <button type="submit" name="delete_police" class="btn btn-outline" style="color: #dc2626; border-color: #fecaca; padding: 0.3rem; width: 30px; height: 30px;" title="Suspend Officer">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr; gap: 0.6rem; font-size: 0.9rem; color: #64748b; margin-bottom: 0.8rem;">
                                <div>
                                    <i class="fas fa-map-marker-alt" style="width: 20px;"></i> <?php echo htmlspecialchars($off['district'] ?? 'N/A'); ?>
                                </div>
                                <div>
                                    <i class="fas fa-envelope" style="width: 20px;"></i> <?php echo htmlspecialchars($off['email']); ?>
                                </div>
                            </div>

                            <div style="background: #f8fafc; padding: 0.8rem; border-radius: 8px; font-size: 0.85rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span>Case Load: <strong style="color: #1e293b;"><?php echo $off['case_count']; ?></strong></span>
                                    <?php 
                                        $rate = ($off['case_count'] > 0) ? round(($off['resolved_count'] / $off['case_count']) * 100) : 0;
                                        $col = $rate > 70 ? '#16a34a' : ($rate > 40 ? '#ca8a04' : '#ef4444');
                                    ?>
                                    <span style="color: <?php echo $col; ?>; font-weight: 700;">Rate: <?php echo $rate; ?>%</span>
                                </div>
                                 <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $rate; ?>%; height: 100%; background: <?php echo $col; ?>;"></div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #64748b;">No officers found.</div>
                <?php endif; ?>
            </div>

            <!-- Edit Modal (Hidden) -->
            <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                <div style="background: white; padding: 2rem; border-radius: 10px; width: 500px; max-width: 90%;">
                    <h3 style="margin-bottom: 1rem; color: var(--primary-green);">Edit Officer Details</h3>
                    <form method="POST">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <input type="hidden" name="edit_police" value="1">
                        
                        <div style="display: grid; gap: 1rem;">
                            <div>
                                <label>Full Name</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>

                            <div>
                                <label>Rank</label>
                                <select name="rank" id="edit_rank" class="form-control">
                                    <option>Constable</option>
                                    <option>Sub-Inspector</option>
                                    <option>Inspector</option>
                                    <option>ACP</option>
                                    <option>SP</option>
                                    <option>DGP</option>
                                </select>
                            </div>
                            <div>
                                <label>District</label>
                                <select name="district" id="edit_district" class="form-control" required>
                                    <option value="Chennai">Chennai</option>
                                    <option value="Coimbatore">Coimbatore</option>
                                    <option value="Madurai">Madurai</option>
                                    <option value="Salem">Salem</option>
                                    <option value="Tiruchirappalli">Tiruchirappalli</option>
                                    <!-- Simplified list, can be expanded -->
                                    <option value="Tamil Nadu">Tamil Nadu (State Level)</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right;">
                            <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-outline">Cancel</button>
                            <button type="submit" class="btn btn-primary" style="margin-left: 10px;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openEdit(data) {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.full_name;
                    document.getElementById('edit_rank').value = data.rank;
                    document.getElementById('edit_district').value = data.district;
                    
                    document.getElementById('editModal').style.display = 'flex';
                }
            </script>
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
