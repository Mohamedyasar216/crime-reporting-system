<?php
session_start();
require_once '../app/config/db.php';
$crimes = $pdo->query("SELECT * FROM crimes WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Crime Map - SafeCity</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root {
            --primary: #19485F;
            --accent: #D9E0A4;
            --accent-light: #f1f4d8;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --sidebar-border: #e2e8f0;
            
            --grad-teal: linear-gradient(135deg, #19485F 0%, #2c6e8f 100%);
            --grad-accent: linear-gradient(135deg, #D9E0A4 0%, #b8c17d 100%);
        }

        * {
            box-sizing: border-box;
        }

        body { 
            overflow: hidden;
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            margin: 0; padding: 0;
            color: var(--text-main);
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 0 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2000;
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            height: 70px;
            border-bottom: 1px solid var(--sidebar-border);
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary); 
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .btn-back-cit {
            background: linear-gradient(135deg, #19485F 0%, #69A481 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 20px rgba(25, 72, 95, 0.2);
        }
        .btn-back-cit:hover { 
            transform: translateY(-3px) scale(1.02); 
            box-shadow: 0 15px 30px rgba(25, 72, 95, 0.3);
            filter: brightness(1.1);
        }

        /* Map Container */
        #heatmap { 
            width: 100%; 
            height: 100vh; 
            z-index: 1; 
            padding-top: 70px;
            box-sizing: border-box;
        }

        /* Floating Panel */
        .floating-panel {
            position: absolute;
            top: 90px;
            left: 20px;
            width: 350px;
            background: white;
            border-radius: 24px;
            padding: 2rem;
            z-index: 1100;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--sidebar-border);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .floating-panel.collapsed { transform: translateX(-400px); }

        .toggle-panel-btn {
            position: absolute;
            top: 90px;
            left: 20px;
            z-index: 1050;
            background: white;
            border: 1px solid var(--sidebar-border);
            width: 50px;
            height: 50px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.3rem;
            transition: 0.2s;
        }
        .toggle-panel-btn:hover { background: #f8fafc; color: var(--primary); }

        .intel-tag { color: var(--text-muted); font-weight: 800; font-size: 0.75rem; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 0.5rem; display: block; }
        .panel-title { font-size: 1.6rem; font-weight: 900; color: var(--primary); margin: 0 0 1.5rem 0; letter-spacing: -1.1px; }
        .filter-label { font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.6rem; display: block; }
        .filter-group { margin-bottom: 1.5rem; }
        
        .custom-select, .custom-input {
            width: 100%;
            padding: 0.85rem 1.1rem;
            border-radius: 14px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: all 0.2s;
            box-sizing: border-box;
            font-family: inherit;
        }
        .custom-select:focus, .custom-input:focus { border-color: var(--primary); outline: none; background: white; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .navbar { padding: 0 1.25rem; height: 60px; }
            .logo-text span { display: none; }
            .logo-text { font-size: 1.2rem; }
            
            .btn-back-cit { padding: 0.6rem 1rem; font-size: 0.8rem; border-radius: 10px; }
            
            #heatmap { padding-top: 60px; }

            .floating-panel {
                width: calc(100% - 20px);
                left: 10px;
                top: 75px;
                max-height: calc(100vh - 90px);
                overflow-y: auto;
                padding: 1.5rem;
                z-index: 3100;
            }
            .floating-panel .panel-title { font-size: 1.4rem; }
            .toggle-panel-btn { top: 75px; left: 10px; z-index: 3101; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index" class="logo-text">
            <i class="fas fa-shield-halved"></i>
            <span>SafeCity</span>
        </a>
        


        <?php 
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            $is_from_index = (strpos($referrer, 'index') !== false || substr(parse_url($referrer, PHP_URL_PATH), -1) == '/' || empty($referrer));
            
            if($is_from_index): ?>
                <a href="index" class="btn-back-cit">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            <?php else: ?>
                <a href="dashboard" class="btn-back-cit">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            <?php endif; ?>
    </nav>

    <!-- Toggle Button for Panel -->
    <button class="toggle-panel-btn" onclick="toggleFilterPanel()" title="Toggle Filters">
        <i class="fas fa-filter"></i>
    </button>

    <!-- Floating Filter Panel -->
    <div class="floating-panel" id="filter-panel">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="intel-tag">Live Intelligence</span>
                <h2 class="panel-title">Crime Map</h2>
            </div>
            <button onclick="toggleFilterPanel()" style="background: #f1f5f9; border: none; color: #64748b; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: 0.2s;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b';">
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
            <label class="filter-label">Area Search</label>
            <input type="text" id="filter-search" class="custom-input" placeholder="Search street or area...">
        </div>
    </div>

    <!-- Map Container -->
    <div id="heatmap"></div>

    <script>
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

                var color = '#3b82f6'; // default
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
                        <div style="padding: 5px; text-align: center;">
                        <b style="font-size: 1.1rem; color: #1e293b;">${c.crime_type}</b><br>
                        <span style="color: #64748b; font-size: 0.9rem;">${c.area}, ${c.district}</span><br>
                        <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 8px 0;">
                        <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">${c.status}</span>
                    </div>
                `);
            });
        }

        document.getElementById('filter-time').addEventListener('change', renderMarkers);
        document.getElementById('filter-type').addEventListener('change', renderMarkers);
        document.getElementById('filter-search').addEventListener('input', renderMarkers);

        renderMarkers();

        // Toggle Panel Function
        function toggleFilterPanel() {
            var p = document.getElementById('filter-panel');
            if(p) p.classList.toggle('collapsed');
        }

        // Mobile Responsive Adjustments
        function checkMobile() {
            if (window.innerWidth <= 768) {
                // Auto collapse on mobile initially if needed, or leave open
                // document.getElementById('filter-panel').classList.add('collapsed');
            } else {
                document.getElementById('filter-panel').classList.remove('collapsed');
            }
        }
        
        // window.addEventListener('resize', checkMobile);
        // checkMobile();
    </script>
</body>
</html>
