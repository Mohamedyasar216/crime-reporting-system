<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'citizen') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard");
    exit;
}

$report_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Crime Details ensuring it belongs to the user
$stmt = $pdo->prepare("SELECT * FROM crimes WHERE id = ? AND user_id = ?");
$stmt->execute([$report_id, $user_id]);
$crime = $stmt->fetch();

if (!$crime) {
    die("Report not found or access denied.");
}

// Fetch Updates/Timeline
$stmt_updates = $pdo->prepare("SELECT * FROM crime_updates WHERE crime_id = ? ORDER BY updated_at ASC");
$stmt_updates->execute([$report_id]);
$updates = $stmt_updates->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case #<?php echo $crime['id']; ?> - SafeCity</title>
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
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

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

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 80px; }
            .mobile-header { display: flex; position: fixed; top: 0; left: 0; right: 0; height: 64px; background: white; z-index: 1100; padding: 0 1.25rem; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            
            .btn-gradient-cit, .btn-outline-cit { width: 100%; justify-content: center; }
            .timeline-content { padding: 1rem; }
        }

        .sidebar-overlay { 
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; 
        }
        .sidebar-overlay.active { display: block; }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding: 20px 0;
            margin-top: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 1.5rem;
        }

        .timeline-dot {
            position: absolute;
            left: 12px;
            width: 18px;
            height: 18px;
            background: white;
            border: 3px solid var(--primary);
            border-radius: 50%;
            z-index: 1;
        }

        .timeline-item.active .timeline-dot {
            background: var(--accent);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(217, 224, 164, 0.4);
        }

        .timeline-content {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: 0.3s;
        }

        .timeline-content:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .timeline-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .timeline-status {
            font-weight: 800;
            color: var(--primary);
            font-size: 1rem;
            margin-bottom: 8px;
            display: block;
        }

        .timeline-remarks {
            font-size: 0.9rem;
            color: var(--text-dark);
            background: var(--bg-light);
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 8px;
        }

    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            
            <div style="max-width: 900px; margin: 0 auto;">
                <a href="dashboard" class="btn-gradient-cit" style="margin-bottom: 2rem; padding: 10px 20px; font-size: 0.85rem; border-radius: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div style="background: var(--grad-teal); padding: 2.5rem; color: white; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -20px; right: -20px; width: 150px; height: 150px; background: rgba(217, 224, 164, 0.1); border-radius: 50%;"></div>
                        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                    Case File #<?php echo $crime['id']; ?>
                                </span>
                                <h1 style="margin: 10px 0 5px; font-size: 1.8rem;"><?php echo htmlspecialchars($crime['crime_type']); ?></h1>
                                <p style="opacity: 0.9; font-size: 0.95rem;">
                                    <i class="far fa-calendar-alt"></i> Reported on <?php echo date('d M Y, h:i A', strtotime($crime['created_at'])); ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.8; font-weight: 800; color: #D9E0A4;">Current Status</div>
                                <div style="font-size: 1.2rem; font-weight: 800; background: var(--accent); color: var(--primary); padding: 8px 20px; border-radius: 10px; margin-top: 5px; display: inline-block; box-shadow: 0 4px 15px rgba(217,224,164,0.3);">
                                    <?php echo htmlspecialchars($crime['status']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 2rem;">
                        <div class="case-details-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                            
                            <!-- Left Column: Details -->
                            <div>
                                <h3 style="color: var(--primary); margin-bottom: 1.5rem; border-bottom: 2px solid var(--bg-light); padding-bottom: 0.75rem;">Incident Details</h3>
                                <p style="color: var(--text-main); line-height: 1.8; margin-bottom: 2rem; white-space: pre-wrap;"><?php echo htmlspecialchars($crime['description']); ?></p>
                                
                                <h4 style="color: var(--secondary); font-size: 1rem; margin-bottom: 0.8rem;"><i class="fas fa-map-marker-alt" style="color: var(--danger);"></i> Location</h4>
                                <p style="background: var(--bg-light); padding: 1.25rem; border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); font-weight: 500; color: var(--text-dark);">
                                    <i class="fas fa-map-pin" style="color: var(--primary); margin-right: 8px;"></i>
                                    <?php echo htmlspecialchars($crime['landmark']); ?>, 
                                    <?php echo htmlspecialchars($crime['area']); ?>, <?php echo htmlspecialchars($crime['district']); ?>
                                </p>

                                <?php if($crime['latitude'] && $crime['longitude']): ?>
                                <div id="map" style="height: 250px; border-radius: 10px; margin-top: 1rem; border: 1px solid var(--border);"></div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var map = L.map('map').setView([<?php echo $crime['latitude']; ?>, <?php echo $crime['longitude']; ?>], 15);
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                            attribution: '© OpenStreetMap contributors'
                                        }).addTo(map);
                                        L.marker([<?php echo $crime['latitude']; ?>, <?php echo $crime['longitude']; ?>])
                                        .addTo(map)
                                        .bindPopup('Incident Location')
                                        .openPopup();
                                    });
                                </script>
                                <?php endif; ?>

                                <!-- Status Timeline -->
                                <div style="margin-top: 3rem;">
                                    <h3 style="color: var(--primary); margin-bottom: 1.5rem; border-bottom: 2px solid var(--bg-light); padding-bottom: 0.75rem;">
                                        <i class="fas fa-clock-rotate-left" style="margin-right: 10px;"></i>Status Timeline
                                    </h3>
                                    
                                    <div class="timeline">
                                        <!-- Initial Report Entry -->
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <span class="timeline-date"><?php echo date('d M Y, h:i A', strtotime($crime['created_at'])); ?></span>
                                                <span class="timeline-status">Complaint Filed</span>
                                                <div class="timeline-remarks">Case #<?php echo $crime['id']; ?> has been successfully reported and is pending review.</div>
                                            </div>
                                        </div>

                                        <?php 
                                        $last_idx = count($updates) - 1;
                                        foreach($updates as $idx => $update): 
                                        ?>
                                        <div class="timeline-item <?php echo ($idx === $last_idx) ? 'active' : ''; ?>">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <span class="timeline-date"><?php echo date('d M Y, h:i A', strtotime($update['updated_at'])); ?></span>
                                                <span class="timeline-status"><?php echo htmlspecialchars($update['status_to']); ?></span>
                                                <?php if(!empty($update['remarks'])): ?>
                                                    <div class="timeline-remarks"><?php echo htmlspecialchars($update['remarks']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>

                            <!-- Right Column: Meta -->
                            <div>
                                <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 15px; border: 1px solid rgba(0,0,0,0.05);">
                                    <h4 style="color: var(--primary); font-size: 1rem; margin-top: 0; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Case Information</h4>
                                    
                                    <div style="margin-top: 1rem;">
                                        <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Incident Date</label>
                                        <div style="font-weight: 600; color: var(--secondary);"><?php echo date('d M Y', strtotime($crime['incident_date'])); ?></div>
                                        <div style="font-size: 0.9rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($crime['incident_date'])); ?></div>
                                    </div>

                                    <div style="margin-top: 1.5rem;">
                                        <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Report Type</label>
                                        <div style="font-weight: 600; color: var(--secondary);">
                                            <?php echo $crime['is_anonymous'] ? '<i class="fas fa-user-secret"></i> Anonymous' : '<i class="fas fa-user"></i> Standard Report'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
            
            <style>
                @media (max-width: 768px) {
                    .case-details-grid {
                        grid-template-columns: 1fr !important;
                        gap: 1rem !important;
                    }
                    .card > div:first-child {
                        padding: 1.5rem !important;
                    }
                    .card h1 { font-size: 1.5rem !important; }
                }
            </style>
            
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
