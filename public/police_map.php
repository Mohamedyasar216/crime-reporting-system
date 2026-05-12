<?php
session_start();
require_once '../app/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'police') {
    header("Location: login");
    exit;
}

$my_id = $_SESSION['user_id'];

// Fetch Rank & District
$me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$me->execute([$my_id]);
$user = $me->fetch();
$rank = $user['rank'] ?? 'Officer';
$district = $user['district'];

if ($rank == 'DGP') {
    $crimes = $pdo->query("SELECT * FROM crimes WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM crimes WHERE district = ? AND latitude IS NOT NULL AND longitude IS NOT NULL");
    $stmt->execute([$district]);
    $crimes = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligence Map - TN Police</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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
        
        body { margin: 0; background: var(--police-bg); font-family: 'Inter', system-ui, sans-serif; color: var(--text-dark); }
        
        /* Professional Police Sidebar */
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
        .sidebar-brand {
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
        }
        .brand-text { font-size: 1.25rem; font-weight: 900; color: var(--primary); letter-spacing: -0.5px; }
        
        .nav-group { padding: 1rem 0.75rem; }
        .nav-item { 
            display: flex; align-items: center; padding: 0.85rem 1.25rem; color: #64748b; 
            text-decoration: none; transition: 0.3s; border-radius: 12px; margin-bottom: 0.5rem; font-weight: 600;
            font-size: 0.9rem;
        }
        .nav-item:hover { background: var(--accent); color: var(--primary); }
        .nav-item.active { 
            background: var(--gradient-mixed); 
            color: white; 
            box-shadow: 0 8px 20px rgba(193, 18, 31, 0.15); 
        }
        .nav-item i { width: 20px; font-size: 1.1rem; margin-right: 12px; }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            position: relative;
            height: 100vh;
            overflow: hidden;
        }
        #heatmap { width: 100%; height: 100%; z-index: 1; }

        /* Floating Analytics Panel (Police Style) */
        .floating-panel {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 380px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            padding: 2rem;
            z-index: 1100;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .floating-panel.collapsed { transform: translateX(450px); opacity: 0; visibility: hidden; }

        .toggle-panel-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            z-index: 1050;
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            width: 50px; height: 50px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); font-size: 1.2rem;
            transition: 0.3s;
        }
        .toggle-panel-btn:hover { transform: scale(1.05); }

        .panel-header { margin-bottom: 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; }
        .intel-tag { color: var(--primary); font-weight: 800; font-size: 0.7rem; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 6px; display: block; opacity: 0.8; }
        .panel-title { font-size: 1.5rem; font-weight: 900; color: var(--text-dark); margin: 0; }
        
        .filter-group { margin-bottom: 1.5rem; }
        .filter-label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.6rem; display: block; letter-spacing: 0.5px; }
        
        .custom-select, .custom-input {
            width: 100%;
            padding: 0.85rem 1.25rem;
            border-radius: 14px;
            border: 2px solid #f1f5f9;
            background: #ffffff;
            font-size: 0.95rem;
            color: var(--text-dark);
            transition: 0.3s;
            box-sizing: border-box;
            font-weight: 600;
        }
        .custom-select:focus, .custom-input:focus { border-color: var(--accent); outline: none; }
        
        .map-search-btn {
            width: 100%; padding: 1rem; border-radius: 14px; border: none;
            background: var(--gradient-red); color: white; font-weight: 800;
            cursor: pointer; transition: 0.3s; margin-top: 1rem;
            box-shadow: 0 10px 25px rgba(193, 18, 31, 0.2);
        }
        .map-search-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(193, 18, 31, 0.3); }

        @media (max-width: 992px) {
            .floating-panel { width: calc(100% - 50px); right: 25px; left: 25px; top: 140px; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding-top: 70px; height: calc(100vh - 70px); }
            
            .mobile-header { 
                display: flex; 
                position: fixed; top: 0; left: 0; right: 0; 
                height: 70px; background: white; 
                z-index: 3000; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                padding: 0 1.5rem; align-items: center; justify-content: space-between;
                border-bottom: 2px solid var(--primary);
            }
            
            .floating-panel { top: 85px; width: calc(100% - 50px); }
            .toggle-panel-btn { top: 85px; }
        }
    </style>
    </style>
</head>
<body>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <a href="index" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-halved" style="color: var(--primary);"></i>
            <span style="font-weight: 800; font-size: 1.1rem;">TN POLICE</span>
        </a>
        <button id="sidebarToggle" style="background: none; border: none; font-size: 1.5rem; color: var(--primary); cursor: pointer;">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" style="z-index: 1500;"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-shield-halved" style="font-size: 1.8rem; color: var(--primary);"></i>
            <span class="brand-text">TN POLICE</span>
        </div>
        <div class="nav-group">
            <a href="police_dashboard" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="police_map" class="nav-item active"><i class="fas fa-map-location-dot"></i> Crime Map</a>
            <a href="police_crimes" class="nav-item"><i class="fas fa-folder-tree"></i> Crime Details</a>
            <?php if($rank == 'DGP'): ?>
                <a href="police_analytics" class="nav-item"><i class="fas fa-chart-line"></i> Analytics</a>
            <?php endif; ?>
            <hr style="border:0; border-top:1px solid #f1f5f9; margin: 1.5rem 0;">
            <a href="logout" class="nav-item" style="color: #dc2626;"><i class="fas fa-power-off"></i> Logout</a>
        </div>
    </div>

    <main class="main-content">
        <button class="toggle-panel-btn" onclick="togglePanel()"><i class="fas fa-filter"></i></button>
        
        <div class="floating-panel" id="filterPanel">
            <div class="panel-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span class="intel-tag">Geospatial Intelligence</span>
                    <h3 class="panel-title">Crime Heatmap</h3>
                </div>
                <button onclick="togglePanel()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.2rem; padding: 5px; transition: 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='#94a3b8'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Temporal Range</span>
                <select id="timeFilter" class="custom-select">
                    <option value="all">All Time Records</option>
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
            </div>


            <div class="filter-group">
                <span class="filter-label">Incident Category</span>
                <select id="typeFilter" class="custom-select">
                    <option value="">All Classifications</option>
                    <option value="Theft">Theft</option>
                    <option value="Robbery">Robbery</option>
                    <option value="Assault">Assault</option>
                    <option value="Cybercrime">Cybercrime</option>
                    <option value="Missing Person">Missing Person</option>
                </select>
            </div>

            <div class="filter-group" style="margin-bottom: 0;">
                <span class="filter-label">Area Search</span>
                <input type="text" id="filter-search" class="custom-input" placeholder="Enter neighborhood...">
            </div>

            <button class="map-search-btn" onclick="renderMarkersWithFilter()">Scan Search Area</button>
            <p style="font-size: 0.7rem; color: #94a3b8; text-align: center; margin-top: 1rem; text-transform: uppercase; letter-spacing: 1px;">Authorised Police Access Only</p>
        </div>

        <div id="heatmap"></div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

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

        const map = L.map('heatmap', { zoomControl: false }).setView([13.0827, 80.2707], 13);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const crimeData = <?php echo json_encode($crimes); ?>;
        const markers = L.layerGroup().addTo(map);

        function renderMarkersWithFilter() {
            markers.clearLayers();
            
            const timeRange = document.getElementById('timeFilter').value;
            const type = document.getElementById('typeFilter').value;
            const search = document.getElementById('filter-search').value.toLowerCase();
            
            const now = new Date();
            
            const filtered = crimeData.filter(c => {
                // Time Filtering
                let matchesTime = true;
                if (timeRange !== 'all') {
                    const incidentDate = new Date(c.incident_date);
                    const diffMs = now - incidentDate;
                    if (timeRange === '24h') matchesTime = diffMs <= 24 * 60 * 60 * 1000;
                    else if (timeRange === '7d') matchesTime = diffMs <= 7 * 24 * 60 * 60 * 1000;
                    else if (timeRange === '30d') matchesTime = diffMs <= 30 * 24 * 60 * 60 * 1000;
                }

                const matchesType = !type || c.crime_type === type;
                const matchesSearch = !search || (c.area + ' ' + c.district).toLowerCase().includes(search);
                return matchesTime && matchesType && matchesSearch;
            });


            filtered.forEach(crime => {
                const marker = L.circleMarker([crime.latitude, crime.longitude], {
                    radius: 10,
                    fillColor: crime.status === 'Pending' ? '#C1121F' : '#3b82f6',
                    color: '#fff',
                    weight: 3,
                    opacity: 1,
                    fillOpacity: 0.9
                }).addTo(markers);

                marker.bindPopup(`
                    <div style="font-family: 'Inter', sans-serif; padding: 5px;">
                        <strong style="color: var(--primary); display: block; margin-bottom: 5px; font-size: 1rem;">${crime.crime_type}</strong>
                        <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-location-dot"></i> ${crime.area}<br>
                            <i class="fas fa-calendar"></i> ${crime.incident_date}
                        </div>
                        <span style="background: ${crime.status==='Pending'?'#FEE2E2':'#E0F2FE'}; color: ${crime.status==='Pending'?'#991B1B':'#0369A1'}; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800;">${crime.status}</span>
                        <a href="police_view_case?id=${crime.id}" style="display: block; margin-top: 15px; text-decoration: none; color: var(--primary); font-weight: 800; font-size: 0.8rem; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 10px;">VIEW FULL FILE &rarr;</a>
                    </div>
                `);
            });

            if(filtered.length > 0) {
                const group = new L.FeatureGroup(markers.getLayers());
                if(group.getLayers().length > 0) map.fitBounds(group.getBounds().pad(0.1));
            }

            // Close panel on mobile after search
            if(window.innerWidth < 768) {
                togglePanel();
            }
        }

        renderMarkersWithFilter();

        function togglePanel() {
            document.getElementById('filterPanel').classList.toggle('collapsed');
        }

        // Fix map size check for mobile
        setTimeout(() => { map.invalidateSize(); }, 500);
    </script>
</body>
</html>
