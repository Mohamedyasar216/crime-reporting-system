<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'citizen') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch All Reports
$stmt = $pdo->prepare("SELECT * FROM crimes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$crimes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - SafeCity</title>
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
            margin-bottom: 2.5rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            letter-spacing: -0.5px;
        }

        /* Premium Gradient Buttons */
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

            .card-header { flex-direction: column; align-items: stretch; gap: 1rem; text-align: center; }
            .btn-gradient-cit { justify-content: center; width: 100%; }
        }

        .sidebar-overlay { 
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; 
        }
        .sidebar-overlay.active { display: block; }

        .mobile-view { display: none; }
        @media (max-width: 768px) {
            .desktop-view { display: none; }
            .mobile-view { display: block; }
            .card { padding: 1rem; width: 100%; }
        }
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
                <a href="dashboard" class="nav-item">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="report" class="nav-item">
                    <i class="fas fa-file-circle-plus"></i> Report Crime
                </a>
                <a href="map" class="nav-item">
                    <i class="fas fa-map-location-dot"></i> Crime Map
                </a>
                <a href="my_reports" class="nav-item active">
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">My Complaints History</h3>
                    <a href="report" class="btn-gradient-cit">
                        <i class="fas fa-plus"></i> New Report
                    </a>
                </div>
                
                <!-- Desktop Table View -->
                <div class="table-responsive desktop-view">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Incident Date</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($crimes) > 0): ?>
                                <?php foreach($crimes as $c): ?>
                                <tr>
                                    <td><span style="font-family: monospace; font-weight: 700; color: var(--primary);">#<?php echo $c['id']; ?></span></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($c['incident_date'])); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent);"></div>
                                            <?php echo htmlspecialchars($c['crime_type']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['area']); ?></td>
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
                                <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No reports found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-view">
                    <?php if(count($crimes) > 0): ?>
                        <div style="display: grid; gap: 1rem;">
                        <?php foreach($crimes as $c): ?>
                            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 1rem;">
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
                                
                                <div style="display: grid; gap: 8px; font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                                    <div><i class="far fa-calendar-alt" style="width: 20px;"></i> <?php echo date('d M Y, h:i A', strtotime($c['incident_date'])); ?></div>
                                    <div><i class="fas fa-map-marker-alt" style="width: 20px;"></i> <?php echo htmlspecialchars($c['area']); ?></div>
                                </div>
                                
                                <a href="view_case?id=<?php echo $c['id']; ?>" class="btn-gradient-cit" style="width: 100%; justify-content: center;">
                                    View Full Details
                                </a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">No reports found.</div>
                    <?php endif; ?>
                </div>


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
