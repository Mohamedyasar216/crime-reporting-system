<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';

// --- DATA FETCHING ---

// 1. Crimes by District
$distData = $pdo->query("SELECT district, COUNT(*) as count FROM crimes GROUP BY district ORDER BY count DESC")->fetchAll();

// 2. Crimes by Type
$typeData = $pdo->query("SELECT crime_type, COUNT(*) as count FROM crimes GROUP BY crime_type ORDER BY count DESC")->fetchAll();

// 3. Monthly Trend (Last 6 Months)
$trendData = $pdo->query("
    SELECT DATE_FORMAT(incident_date, '%Y-%m') as month, COUNT(*) as count 
    FROM crimes 
    WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
    GROUP BY month 
    ORDER BY month ASC
")->fetchAll();

// 4. Predictive Insight: Fastest Rising District
// Logic: Calculate difference between Current Month and Previous Month counts
$thisMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
// Fallback if no data for current month (e.g. start of month)
if(count($trendData) == 0) $thisMonth = '1970-01'; 

// 4. District Insight: Target District based on latest assignment
// Priority 1: District of the latest complaint that was assigned
// Priority 2: District of the latest complaint reported
$latestTarget = $pdo->query("SELECT district FROM crimes WHERE status = 'Assigned' ORDER BY updated_at DESC LIMIT 1")->fetchColumn();
if(!$latestTarget) {
    $latestTarget = $pdo->query("SELECT district FROM crimes ORDER BY id DESC LIMIT 1")->fetchColumn();
}
$targetDist = $latestTarget ?: 'Vellore'; // Fallback

$insightQuery = "
    SELECT district,
        SUM(CASE WHEN DATE_FORMAT(incident_date, '%Y-%m') = '$thisMonth' THEN 1 ELSE 0 END) as curr,
        SUM(CASE WHEN DATE_FORMAT(incident_date, '%Y-%m') = '$lastMonth' THEN 1 ELSE 0 END) as prev
    FROM crimes
    WHERE district = " . $pdo->quote($targetDist) . "
    GROUP BY district
";
$risingDistrict = $pdo->query($insightQuery)->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Insights - CRS Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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

        /* Chart Cards */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }
        .chart-card.full-width { grid-column: span 2; }
        .chart-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem; }

        /* Insight Card */
        .insight-card {
            background: var(--grad-mixed);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(124, 31, 49, 0.2);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .insight-card::after {
            content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
            background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .insight-badge {
            background: rgba(255,255,255,0.25);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: inline-block;
            margin-bottom: 1.25rem;
            color: #ffffff;
            backdrop-filter: blur(4px);
        }

        /* Mobile Adjustments */
        @media (max-width: 1024px) {
            .chart-grid { grid-template-columns: 1fr; }
            .chart-card.full-width { grid-column: span 1; }
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
                width: 100%;
                box-sizing: border-box;
            }
            .banner-icon { margin: 0 auto; width: 60px; height: 60px; font-size: 1.8rem; }
            .banner-info h2 { font-size: 1.3rem; }
            .banner-info p { display: flex; flex-direction: column; align-items: center; gap: 8px; font-size: 0.85rem; }
            .banner-info p span { margin-left: 0 !important; }
            .admin-banner div:last-child { text-align: center !important; font-size: 0.8rem; }
            
            .main-content { margin-left: 0 !important; padding: 1rem; padding-top: 80px !important; overflow-x: hidden; }
            
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
            .top-bar { display: none !important; }
            .sidebar { z-index: 4000; }
            
            .sidebar-overlay { z-index: 3500; }
            .sidebar-overlay.active { display: block; }

            .insight-card { padding: 1.5rem; text-align: center; }
            .insight-card h2 { font-size: 1.5rem !important; }
            .insight-card p { font-size: 0.95rem !important; }
            .insight-card div[style*="display: flex"] { justify-content: center; gap: 1.5rem !important; }
            
            .chart-grid { gap: 1rem; }
            .chart-card { padding: 1rem; }
            .chart-title { font-size: 1rem; }

            /* Reduce Logout Button Size on Mobile Top-bar (if shown) */
            .top-bar .btn-mixed { padding: 0.4rem 0.8rem !important; font-size: 0.7rem !important; }

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
            <span class="page-title">Analytics</span>
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
                <a href="admin_analytics" class="nav-item active">
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
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">Analytics</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Intelligent Insights</p>
                    </div>
                </div>
                <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                </a>
            </div>

            <!-- Admin Banner -->
            <div class="admin-banner">
                <div class="banner-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="banner-info">
                    <h2>Intelligence Repository</h2>
                    <p>
                        <span class="tag">Analytics Hub</span>
                        <span><i class="fas fa-database"></i> Data Sync: Active</span>
                        <span style="margin-left: 15px;"><i class="fas fa-shield-halved"></i> Audit Verified</span>
                    </p>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-clock"></i> Live Status: <?php echo date('H:i'); ?><br>
                    <?php echo date('d M, Y'); ?>
                </div>
            </div>
            <!-- Predictive Insight Card -->
             <?php if($risingDistrict): ?>
            <div class="insight-card">
                <div style="position: relative; z-index: 1;">
                    <span class="insight-badge"><i class="fas fa-bullseye"></i> Priority Focus</span>
                    <h2 style="font-size: 2rem; margin: 0 0 10px 0; font-weight: 800; color: var(--accent-light);">
                        <?php echo htmlspecialchars($risingDistrict['district']); ?> District
                    </h2>
                    <p style="font-size: 1.1rem; opacity: 0.9; max-width: 600px; line-height: 1.5; color: #e2e8f0;">
                        <i class="fas fa-fingerprint"></i> 
                        Analyzing the latest activity in this district. 
                        <?php if($risingDistrict['curr'] > $risingDistrict['prev']): ?>
                            Detected a <strong>rise</strong> in reported incidents compared to last month. 
                        <?php else: ?>
                            Inflow matches previous patterns. 
                        <?php endif; ?>
                        Recommendation: Evaluate resource allocation for current assignments.
                    </p>
                    <div style="margin-top: 1.5rem; display: flex; gap: 2rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 0.8rem; opacity: 0.7; text-transform: uppercase; font-weight: 700; color: var(--accent-cream);">Previous Month</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: white;"><?php echo $risingDistrict['prev']; ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; opacity: 0.7; text-transform: uppercase; font-weight: 700; color: var(--accent-cream);">Current Month</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: white;"><?php echo $risingDistrict['curr']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="chart-grid">
                
                <!-- Chart 1: District Distribution -->
                <div class="chart-card">
                    <div class="chart-title">Incidents by District</div>
                    <canvas id="districtChart"></canvas>
                </div>

                <!-- Chart 2: Crime Type Breakdown -->
                <div class="chart-card">
                    <div class="chart-title">Crime Type Analysis</div>
                    <canvas id="typeChart"></canvas>
                </div>

                <!-- Chart 3: Temporal Trend -->
                <div class="chart-card full-width">
                    <div class="chart-title">6-Month Trend Analysis</div>
                    <canvas id="trendChart" style="max-height: 300px;"></canvas>
                </div>

            </div>

        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Data from PHP
        const distLabels = <?php echo json_encode(array_column($distData, 'district')); ?>;
        const distCounts = <?php echo json_encode(array_column($distData, 'count')); ?>;

        const typeLabels = <?php echo json_encode(array_column($typeData, 'crime_type')); ?>;
        const typeCounts = <?php echo json_encode(array_column($typeData, 'count')); ?>;

        const trendLabels = <?php echo json_encode(array_column($trendData, 'month')); ?>;
        const trendCounts = <?php echo json_encode(array_column($trendData, 'count')); ?>;

        // Register DataLabels plugin
        Chart.register(ChartDataLabels);

        // Common Options
        const commonOptions = {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: { display: false } // Disable by default for all charts
            }
        };

        // 1. District Chart (Bar)
        new Chart(document.getElementById('districtChart'), {
            type: 'bar',
            data: {
                labels: distLabels,
                datasets: [{
                    label: 'Reported Cases',
                    data: distCounts,
                    backgroundColor: '#005f56',
                    borderRadius: 6
                }]
            },
            options: commonOptions
        });

        // 2. Type Chart (Doughnut)
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeCounts,
                    backgroundColor: [
                        '#003631', '#005f56', '#00463f', '#FFEDA8', '#10b981', '#3b82f6', '#8b5cf6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    datalabels: {
                        display: true,
                        color: (context) => {
                            // Dark text for light background (index 3 is #FFEDA8)
                            return context.dataIndex === 3 ? '#003631' : '#fff';
                        },
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: (value, context) => {
                            const dataset = context.chart.data.datasets[0];
                            const total = dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = ((value / total) * 100).toFixed(0) + "%";
                            return value + " (" + percentage + ")";
                        },
                        anchor: 'center',
                        align: 'center',
                        offset: 0
                    }
                }
            }
        });

        // 3. Trend Chart (Line)
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Total Incidents',
                    data: trendCounts,
                    borderColor: '#003631',
                    backgroundColor: 'rgba(0, 54, 49, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#003631',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Sidebar Logic (Mobile & Collapse)
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
