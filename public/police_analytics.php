<?php
session_start();
require_once '../app/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'police') {
    header("Location: login");
    exit;
}

$my_id = $_SESSION['user_id'];
$me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$me->execute([$my_id]);
$user = $me->fetch();
$rank = $user['rank'] ?? 'Officer';

if ($rank != 'DGP') {
    die("Unauthorized: DGP Access only.");
}

// 1. District-wise Stats
$sql_districts = "SELECT district, 
               COUNT(*) as total, 
               SUM(CASE WHEN status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) as resolved,
               SUM(CASE WHEN status IN ('Pending', 'Assigned', 'Investigation Ongoing') THEN 1 ELSE 0 END) as active
        FROM crimes 
        GROUP BY district 
        ORDER BY total DESC";
$district_stats = $pdo->query($sql_districts)->fetchAll();

// 2. Rankings
$most_affected = array_slice($district_stats, 0, 5);
$least_affected = array_reverse(array_slice(array_reverse($district_stats), 0, 5));

// 3. Trend Data (Last 12 Months)
$sql_trends = "SELECT DATE_FORMAT(incident_date, '%b %Y') as month, 
                      COUNT(*) as count,
                      DATE_FORMAT(incident_date, '%Y-%m') as sort_key
               FROM crimes 
               WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
               GROUP BY month, sort_key
               ORDER BY sort_key ASC";
$trends = $pdo->query($sql_trends)->fetchAll();

// 4. Crime Type Distribution
$sql_types = "SELECT crime_type, COUNT(*) as count FROM crimes GROUP BY crime_type ORDER BY count DESC";
$type_stats = $pdo->query($sql_types)->fetchAll();

// 5. Hotspot Data
$sql_map = "SELECT latitude, longitude, crime_type, district, status FROM crimes WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$map_crimes = $pdo->query($sql_map)->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DGP Strategic Command - TN Police</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
        
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--police-bg); font-family: 'Inter', system-ui, sans-serif; color: var(--text-dark); overflow-x: hidden; width: 100%; }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid rgba(0,0,0,0.05);
            color: #1e293b;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 2000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.02);
            transition: transform 0.3s ease;
        }
        .sidebar-brand { padding: 2rem 1.5rem; display: flex; align-items: center; gap: 15px; background: white; }
        .brand-text { font-size: 1.25rem; font-weight: 900; color: var(--primary); letter-spacing: -0.5px; }
        .nav-group { padding: 1rem 0.75rem; }
        .nav-item { 
            display: flex; align-items: center; padding: 0.85rem 1.25rem; color: #64748b; 
            text-decoration: none; transition: 0.3s; border-radius: 12px; margin-bottom: 0.5rem; font-weight: 600;
            font-size: 0.9rem;
        }
        .nav-item:hover { background: var(--accent); color: var(--primary); }
        .nav-item.active { background: var(--gradient-mixed); color: white; box-shadow: 0 8px 20px rgba(193, 18, 31, 0.15); }
        .nav-item i { width: 20px; font-size: 1.1rem; margin-right: 12px; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 2.5rem; transition: 0.3s; }
        
        /* Dashboard Grid */
        .dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 1.5rem; }
        .card { 
            background: white; border-radius: 20px; padding: 1.5rem; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.03);
            display: flex; flex-direction: column;
        }
        .card-title { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .card-title i { color: var(--primary); }

        /* Banner */
        .officer-banner {
            background: #ffffff; border-radius: 20px; padding: 1.5rem; 
            display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 1.5rem;
            margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .badge-icon { width: 60px; height: 60px; background: var(--accent); color: var(--primary); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .rank-tag { background: var(--primary); color: white; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }

        /* Charts & Map */
        #mainMap { height: 400px; border-radius: 15px; z-index: 1; }
        .chart-container { position: relative; height: 300px; width: 100%; }

        /* Tables */
        .rank-table { width: 100%; border-collapse: collapse; }
        .rank-table th { text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; padding: 10px; border-bottom: 2px solid #f1f5f9; }
        .rank-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .rank-badge { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; color: white; }

        /* Mobile Header */
        .mobile-header {
            display: none; height: 60px; background: white; border-bottom: 2px solid var(--primary);
            padding: 0 1rem; align-items: center; justify-content: space-between; position: fixed;
            top: 0; left: 0; right: 0; z-index: 3000;
        }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1500; }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: repeat(6, 1fr); }
            .col-8, .col-4 { grid-column: span 6; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0.75rem; padding-top: 5rem; width: 100%; box-sizing: border-box; }
            .card { padding: 1rem; border-radius: 15px; }
            .officer-banner { grid-template-columns: 1fr; text-align: center; gap: 1rem; padding: 1rem; }
            .badge-icon { margin: 0 auto; width: 50px; height: 50px; font-size: 1.2rem; }
            .officer-info h2 { font-size: 1.2rem; }
            .mobile-header { display: flex; }
            .dashboard-grid { grid-template-columns: 1fr; gap: 1rem; }
            .col-8, .col-4, .col-6, .col-12 { grid-column: span 1 !important; }
            .chart-container { height: 250px; }
            #mainMap { height: 300px; }
            .rank-table { font-size: 0.8rem; }
            .rank-table th, .rank-table td { padding: 8px 5px; }
            
            /* Strategic Insights Fix */
            .insight-grid { grid-template-columns: 1fr !important; }
        }

        /* Grid Utilities */
        .col-12 { grid-column: span 12; }
        .col-8 { grid-column: span 8; }
        .col-4 { grid-column: span 4; }
        .col-6 { grid-column: span 6; }
    </style>
</head>
<body>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <a href="index" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-halved" style="color: var(--primary);"></i>
            <span style="font-weight: 900; font-size: 1rem;">TN POLICE</span>
        </a>
        <button id="sidebarToggle" style="background: none; border: none; font-size: 1.3rem; color: var(--primary); cursor: pointer;">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-shield-halved" style="font-size: 1.5rem; color: var(--primary);"></i>
            <span class="brand-text">TN POLICE</span>
        </div>
        <div class="nav-group">
            <a href="police_dashboard" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="police_map" class="nav-item"><i class="fas fa-map-location-dot"></i> Crime Map</a>
            <a href="police_crimes" class="nav-item"><i class="fas fa-folder-tree"></i> Crime Details</a>
            <a href="police_analytics" class="nav-item active"><i class="fas fa-chart-line"></i> Analytics</a>
            <hr style="border:0; border-top:1px solid #f1f5f9; margin: 1rem 0;">
            <a href="logout" class="nav-item" style="color: #dc2626;"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <main class="main-content">
        <!-- Officer Status Banner -->
        <div class="officer-banner">
            <div class="badge-icon"><i class="fas fa-user-shield"></i></div>
            <div class="officer-info">
                <h2 style="margin: 0; font-size: 1.4rem;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <div style="margin-top: 5px; display: flex; align-items: center; gap: 10px;">
                    <span class="rank-tag">Director General of Police</span>
                    <span style="font-size: 0.85rem; color: #64748b;"><i class="fas fa-map-marker-alt"></i> State Headquarters, Tamil Nadu</span>
                </div>
            </div>
            <div style="text-align: right; color: #64748b; font-size: 0.8rem;">
                <strong>STRATEGIC COMMAND</strong><br>
                <?php echo date('l, d M Y'); ?>
            </div>
        </div>

        <div class="dashboard-grid">

            <!-- Strategic Intelligence Insights -->
            <div class="card col-12">
                <div class="card-title"><i class="fas fa-brain"></i> Strategic Tactical Intelligence</div>
                <div class="insight-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                    <div style="background: #f8fafc; padding: 1.25rem; border-radius: 15px; border-left: 5px solid var(--primary);">
                        <h4 style="margin: 0 0 10px; font-size: 0.9rem; color: #1e293b;">High-Intensity Zones</h4>
                        <p style="margin: 0; font-size: 0.85rem; color: #475569; line-height: 1.5;">
                            <?php 
                                if(!empty($most_affected)) {
                                    echo "<strong>" . $most_affected[0]['district'] . "</strong> remains the highest volume sector with " . $most_affected[0]['total'] . " incidents. ";
                                    if($most_affected[0]['total'] > 10) echo "Immediate resource deployment recommended.";
                                }
                            ?>
                        </p>
                    </div>
                    <div style="background: #f8fafc; padding: 1.25rem; border-radius: 15px; border-left: 5px solid #f97316;">
                        <h4 style="margin: 0 0 10px; font-size: 0.9rem; color: #1e293b;">Emerging Crime Patterns</h4>
                        <p style="margin: 0; font-size: 0.85rem; color: #475569; line-height: 1.5;">
                            <?php 
                                if(!empty($type_stats)) {
                                    echo "<strong>" . $type_stats[0]['crime_type'] . "</strong> is the dominant threat state-wide. ";
                                    echo "Specialized units in " . $most_affected[0]['district'] . " should be briefed on " . strtolower($type_stats[0]['crime_type']) . " prevention.";
                                }
                            ?>
                        </p>
                    </div>
                    <div style="background: #f8fafc; padding: 1.25rem; border-radius: 15px; border-left: 5px solid #10b981;">
                        <h4 style="margin: 0 0 10px; font-size: 0.9rem; color: #1e293b;">Operational Success</h4>
                        <p style="margin: 0; font-size: 0.85rem; color: #475569; line-height: 1.5;">
                            <?php 
                                $best_eff = 0; $best_dist = "";
                                foreach($district_stats as $s) {
                                    $e = ($s['total'] > 0) ? ($s['resolved'] / $s['total']) : 0;
                                    if($e > $best_eff) { $best_eff = $e; $best_dist = $s['district']; }
                                }
                                echo "<strong>$best_dist</strong> shows peak operational efficiency at " . round($best_eff * 100) . "%. Commendation for jurisdictional lead suggested.";
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- 1. District Comparison Dashboard -->
            <div class="card col-8">
                <div class="card-title"><i class="fas fa-chart-simple"></i> District Comparison Dashboard</div>
                <div class="chart-container">
                    <canvas id="districtChart"></canvas>
                </div>
            </div>

            <!-- Ranking Table -->
            <div class="card col-4">
                <div class="card-title"><i class="fas fa-ranking-star"></i> Performance Ranking</div>
                <div style="flex: 1; overflow-y: auto;">
                    <table class="rank-table">
                        <thead>
                            <tr>
                                <th>District</th>
                                <th style="text-align: center;">Crimes</th>
                                <th style="text-align: center;">Eff. %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($most_affected as $index => $stat): ?>
                            <tr>
                                <td>
                                    <span class="rank-badge" style="background: <?php echo $index < 3 ? '#C1121F' : '#94a3b8'; ?>">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <span style="margin-left: 8px; font-weight: 700;"><?php echo $stat['district']; ?></span>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?php echo $stat['total']; ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                        $rate = ($stat['total'] > 0) ? round(($stat['resolved'] / $stat['total']) * 100) : 0;
                                        echo "<span style='color: ".($rate > 70 ? '#10b981' : '#f59e0b')."; font-weight: 800;'>$rate%</span>";
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 15px; background: #fff7ed; padding: 10px; border-radius: 10px; font-size: 0.85rem; color: #9a3412; font-weight: 500;">
                    <i class="fas fa-lightbulb"></i> Note: Rankings based on total crime volume.
                </div>
            </div>

            <!-- 2. Crime Hotspot Intelligence Map -->
            <div class="card col-12">
                <div class="card-title" style="justify-content: space-between;">
                    <div><i class="fas fa-map-marked-alt"></i> Crime Hotspot Intelligence Map</div>
                    <div style="display: flex; gap: 15px; font-size: 0.75rem;">
                        <span><i class="fas fa-circle" style="color: #ef4444;"></i> High Risk</span>
                        <span><i class="fas fa-circle" style="color: #f97316;"></i> Alert Zone</span>
                        <span><i class="fas fa-circle" style="color: #22c55e;"></i> Stable</span>
                    </div>
                </div>
                <div id="mainMap"></div>
            </div>

            <!-- 3. Crime Trend Timeline -->
            <div class="card col-8">
                <div class="card-title"><i class="fas fa-chart-line"></i> State-Level Crime Trend Timeline</div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Crime Type Distribution -->
            <div class="card col-4">
                <div class="card-title"><i class="fas fa-pie-chart"></i> Incident Classification</div>
                <div class="chart-container">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <!-- Least Crime Districts (Footer Ranking) -->
            <div class="card col-12">
                <div class="card-title"><i class="fas fa-shield-check"></i> Safest Districts (Least Crime Recorded)</div>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    <?php foreach($least_affected as $stat): ?>
                    <div style="flex: 1; min-width: 150px; background: #f0fdf4; border: 1px solid #bcf0da; padding: 1rem; border-radius: 15px; text-align: center;">
                        <h4 style="margin: 0; color: #166534;"><?php echo $stat['district']; ?></h4>
                        <div style="font-size: 1.5rem; font-weight: 900; color: #15803d; margin: 10px 0;"><?php echo $stat['total']; ?></div>
                        <small style="color: #166534; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Crimes Logged</small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>

    <script>
        // --- Sidebar Logic ---
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggle = document.getElementById('sidebarToggle');

        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }
        if(sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }

        // --- Charts Initialization ---
        
        // 1. District Comparison Chart
        new Chart(document.getElementById('districtChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($district_stats, 'district')); ?>,
                datasets: [{
                    label: 'Total Crimes',
                    data: <?php echo json_encode(array_column($district_stats, 'total')); ?>,
                    backgroundColor: '#C1121F',
                    borderRadius: 8,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Trend Timeline Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trends, 'month')); ?>,
                datasets: [{
                    label: 'Statewide Incidents',
                    data: <?php echo json_encode(array_column($trends, 'count')); ?>,
                    borderColor: '#C1121F',
                    backgroundColor: 'rgba(193, 18, 31, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#C1121F',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 3. Crime Type Pie Chart
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($type_stats, 'crime_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($type_stats, 'count')); ?>,
                    backgroundColor: ['#C1121F', '#f97316', '#3b82f6', '#10b981', '#6366f1', '#a855f7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { size: 10 } } }
                },
                cutout: '70%'
            }
        });

        // --- Map Initialization ---
        const map = L.map('mainMap', { scrollWheelZoom: true }).setView([10.7870, 78.6506], 7); // Center of TN
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const crimes = <?php echo json_encode($map_crimes); ?>;
        const districtRisk = <?php 
            $risk_lookup = [];
            foreach($district_stats as $ds) $risk_lookup[$ds['district']] = $ds['total'];
            echo json_encode($risk_lookup); 
        ?>;
        
        // Intelligent Heat Markers
        crimes.forEach(c => {
            const count = districtRisk[c.district] || 0;
            let color = '#22c55e'; // Stable (Low)
            if(count >= 5) color = '#ef4444'; // High Risk (Red)
            else if (count >= 3) color = '#f97316'; // Alert Zone (Orange)

            const circle = L.circleMarker([c.latitude, c.longitude], {
                radius: 12,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.6
            }).addTo(map);

            circle.bindPopup(`
                <div style="font-family: inherit; padding: 5px;">
                    <strong style="color: var(--primary); font-size: 1rem;">${c.crime_type}</strong><br>
                    <span style="font-size: 0.8rem; color: #64748b;">${c.district} District</span>
                    <hr style="border:0; border-top:1px solid #f1f5f9; margin: 8px 0;">
                    <div style="font-size: 0.75rem; font-weight: 800; color: ${color}; text-transform: uppercase;">Area Risk: ${count >= 5 ? 'High' : (count >= 3 ? 'Medium' : 'Low')}</div>
                    <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 4px;">Status: ${c.status}</div>
                </div>
            `);
        });

    </script>
</body>
</html>
