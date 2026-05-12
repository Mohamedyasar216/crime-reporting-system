<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';
$crimes = $pdo->query("SELECT * FROM crimes WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Crime Map - CRS Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
            z-index: 2000;
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
            height: 100vh;
            background: var(--bg-body);
            padding: 0;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Mobile Adjustments */
        #heatmap { width: 100%; height: 100%; z-index: 1; }

        /* Floating Panel */
        .floating-panel {
            position: absolute;
            top: 25px;
            left: 25px;
            width: 360px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2.2rem;
            z-index: 1100;
            box-shadow: 0 15px 45px rgba(124, 31, 49, 0.12);
            border: 1px solid rgba(124, 31, 49, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .floating-panel.collapsed { transform: translateX(-400px); }

        .toggle-panel-btn {
            position: absolute;
            top: 25px;
            left: 25px;
            z-index: 1050;
            background: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-claret);
            font-size: 1.3rem;
            transition: 0.3s;
        }
        .toggle-panel-btn:hover { transform: scale(1.05); color: var(--color-mint); }

        .intel-tag { color: var(--color-claret); font-weight: 900; font-size: 0.75rem; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 0.75rem; display: block; }
        .panel-title { font-size: 1.7rem; font-weight: 800; color: #1e293b; margin: 0 0 1.5rem 0; letter-spacing: -1px; }
        .filter-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.5rem; display: block; letter-spacing: 0.5px; }
        .filter-group { margin-bottom: 1.5rem; }
        
        .custom-select, .custom-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 0.95rem;
            color: #1e293b;
            transition: 0.3s;
            box-sizing: border-box;
        }
        .custom-select:focus, .custom-input:focus { border-color: var(--color-mint); outline: none; box-shadow: 0 0 0 4px rgba(105, 164, 129, 0.1); }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 3000;
            }
            .sidebar.active { transform: translateX(0); }
            .sidebar.collapsed { width: 280px; }
            .sidebar.collapsed .brand-text, .sidebar.collapsed .nav-text { display: block; }
            
            .main-content { margin-left: 0 !important; }

            .floating-panel {
                width: calc(100% - 20px);
                left: 10px;
                top: 120px;
                max-height: calc(100vh - 140px);
                overflow-y: auto;
                padding: 1.25rem;
                z-index: 3100;
            }
            .floating-panel .panel-title { font-size: 1.4rem; margin-bottom: 1rem; }
            .floating-panel .intel-tag { font-size: 0.65rem; margin-bottom: 0.2rem; }
            .floating-panel .filter-group { margin-bottom: 1rem; }
            .toggle-panel-btn { top: 120px; left: 10px; z-index: 3101; }
            
            .mobile-header {
                display: flex;
                position: fixed;
                top: 0; left: 0; right: 0;
                background: #ffffff !important;
                padding: 0 1.25rem;
                z-index: 4000;
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
            .main-content { padding-top: 64px; height: calc(100vh - 64px); }
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
            <span class="page-title">Crime Map</span>
        </div>
        <a href="admin_dashboard" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-halved" style="color: var(--color-claret);"></i>
            <span style="font-weight: 800; font-size: 0.9rem; color: var(--color-claret);">CRS</span>
        </a>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-container">
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
                <a href="admin_map" class="nav-item active">
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
            <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0; background: white; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; position: relative; z-index: 2000;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button id="desktopToggle" style="background: var(--color-smoke); border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; color: var(--color-claret); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-claret); margin: 0;">GIS Intelligence Map</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Geospatial Crime Analysis</p>
                    </div>
                </div>
                <a href="logout" class="btn-mixed" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                </a>
            </div>
            <!-- Toggle Button for Panel -->
            <button class="toggle-panel-btn" onclick="toggleFilterPanel()" title="Toggle Filters">
                <i class="fas fa-filter"></i>
            </button>

            <!-- Floating Intel Panel -->
            <div class="floating-panel" id="filter-panel">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span class="intel-tag">GEO-INTEL HUB</span>
                        <h2 class="panel-title">Crime Mapping</h2>
                    </div>
                    <button onclick="toggleFilterPanel()" style="background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
</div>

                <div class="filter-group">
                    <label class="filter-label">Classification Filter</label>
                    <select id="filter-type" class="custom-select">
                        <option value="all">All Classifications</option>
                        <option value="Theft">Theft / Burglary</option>
                        <option value="Assault">Assault / Violence</option>
                        <option value="Cybercrime">Cybercrime / Fraud</option>
                        <option value="Harassment">Harassment</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Temporal Range</label>
                    <select id="filter-time" class="custom-select">
                        <option value="all">Full History</option>
                        <option value="24h">Last 24 Hours</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="year">Past 12 Months</option>
                    </select>
                </div>

                <div class="filter-group" style="margin-bottom: 0;">
                    <label class="filter-label">Area Coordinate Link</label>
                    <input type="text" id="filter-search" class="custom-input" placeholder="Enter street or neighborhood...">
                </div>
            </div>

            <div id="heatmap"></div>

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
                        setTimeout(() => map.invalidateSize(), 300);
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
                        setTimeout(() => map.invalidateSize(), 300);
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
                    map.invalidateSize();
                });

                // Init Map
                var map = L.map('heatmap', {zoomControl: false}).setView([11.1271, 78.6569], 7);
                L.control.zoom({position: 'bottomright'}).addTo(map);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Add Markers
                var allCrimes = <?php echo json_encode($crimes); ?>;
                var markers = L.layerGroup().addTo(map);

                function renderMarkers() {
                    markers.clearLayers();
                    
                    var timeFilter = document.getElementById('filter-time').value;
                    var typeFilter = document.getElementById('filter-type').value;
                    var searchFilter = document.getElementById('filter-search').value.toLowerCase();

                    var now = new Date();

                    allCrimes.forEach(c => {
                        // 1. Time Filter
                        var cDate = new Date(c.incident_date);
                        if (timeFilter !== 'all') {
                            var diffHrs = (now - cDate) / (1000 * 60 * 60);
                            if (timeFilter === '24h' && diffHrs > 24) return;
                            if (timeFilter === '30d' && diffHrs > (24 * 30)) return;
                            if (timeFilter === 'year' && diffHrs > (24 * 365)) return;
                        }

                        // 2. Classification Filter
                        if (typeFilter !== 'all' && c.crime_type !== typeFilter) return;

                        // 3. Location Search
                        if (searchFilter) {
                            var text = (c.area + ' ' + c.district + ' ' + (c.landmark||'')).toLowerCase();
                            if (!text.includes(searchFilter)) return;
                        }

                        var color = '#003631'; // default Green
                        if(c.crime_type == 'Theft' || c.crime_type == 'Robbery') color = '#eab308';
                        if(c.crime_type == 'Assault' || c.crime_type == 'Harassment') color = '#ef4444';
                        if(c.crime_type == 'Cybercrime') color = '#0284c7';
                        
                        L.circleMarker([c.latitude, c.longitude], {
                            radius: 8,
                            fillColor: color,
                            color: "#fff",
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.8
                        }).addTo(markers)
                        .bindPopup(`
                             <div style="padding: 5px;">
                                <b style="font-size: 1.1rem; color: #1e293b;">${c.crime_type}</b><br>
                                <span style="color: #64748b; font-size: 0.9rem;">${c.area}, ${c.district}</span><br>
                                <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 8px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                    <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">#${c.id}</span>
                                    <span style="color: #94a3b8; font-size: 0.8rem;">${c.status}</span>
                                </div>
                                <a href="admin_view_case?id=${c.id}" style="display: block; margin-top: 10px; text-decoration: none; color: #003631; font-weight: 600; font-size: 0.85rem; text-align: center;">View Details &rarr;</a>
                            </div>
                        `);
                    });
                }

                document.getElementById('filter-time').addEventListener('change', renderMarkers);
                document.getElementById('filter-type').addEventListener('change', renderMarkers);
                document.getElementById('filter-search').addEventListener('input', renderMarkers);

                renderMarkers();
                setTimeout(() => map.invalidateSize(), 500);

                function toggleFilterPanel() {
                    var p = document.getElementById('filter-panel');
                    if(p) p.classList.toggle('collapsed');
                }
            </script>
        </main>
    </div>
</body>
</html>
