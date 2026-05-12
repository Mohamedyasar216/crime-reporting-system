<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'citizen') {
    header("Location: login");
    exit;
}
require_once '../app/config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch User Details for Profile
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$u = $stmtUser->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Crime - SafeCity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
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
            padding: 2.5rem;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            letter-spacing: -0.5px;
        }

        .progress-track {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .progress-track::before {
            content: '';
            position: absolute;
            top: 20px; left: 0; right: 0;
            height: 4px;
            background: #e2e8f0;
            z-index: 0;
        }

        .step-node {
            width: 40px; height: 40px;
            background: white;
            border: 4px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            position: relative;
            z-index: 1;
            color: #94a3b8;
            transition: all 0.3s;
        }

        .step-node.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .step-label {
            position: absolute;
            top: 45px; left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #94a3b8;
        }

        .step-node.active .step-label { color: var(--primary); }

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
            font-family: inherit;
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

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(25, 72, 95, 0.05);
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 80px; }
            .mobile-header { display: flex; position: fixed; top: 0; left: 0; right: 0; height: 64px; background: white; z-index: 1100; padding: 0 1.25rem; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            
            .card { padding: 1.5rem; }
            .btn-group { flex-direction: column-reverse; gap: 1rem; }
            .btn-gradient-cit, .btn-outline-cit { width: 100%; justify-content: center; }
            
            .progress-track { margin-bottom: 2rem; }
            .step-label { display: none; }
            
            div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; gap: 1rem !important; }
        }

        .sidebar-overlay { 
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 900; 
        }
        .sidebar-overlay.active { display: block; }

        .step-container { display: none; }
        .step-container.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
                <a href="report" class="nav-item active">
                    <i class="fas fa-file-circle-plus"></i> Report Crime
                </a>
                <a href="map" class="nav-item">
                    <i class="fas fa-map-location-dot"></i> Crime Map
                </a>
                <a href="my_reports" class="nav-item">
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
            
            <div style="max-width: 800px; margin: 0 auto;">
                <a href="dashboard" class="btn-outline-cit" style="margin-bottom: 2rem; border: none; padding-left: 0;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>

                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">File a New Complaint</h1>
                    <p style="color: var(--text-muted);">Please provide accurate details to help us assist you better.</p>
                </div>

                <!-- Stepper -->
                <div class="progress-track">
                    <div class="step-node active" id="node-1">1 <span class="step-label">Details</span></div>
                    <div class="step-node" id="node-2">2 <span class="step-label">Location</span></div>
                    <div class="step-node" id="node-3">3 <span class="step-label">Verify</span></div>
                </div>

                <div class="card">
                    <form action="../app/controllers/crime_report.php" method="POST" enctype="multipart/form-data" id="reportForm">
                        
                        <!-- STEP 1: Details -->
                        <div class="step-container active" id="step-1">
                            <h3 class="card-title" style="margin-bottom: 1.5rem;">Incident Details</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Crime Type</label>
                                <select name="crime_type" class="form-control" required id="crime_type">
                                    <option value="">Select Category</option>
                                    <option value="Theft">Theft / Burglary</option>
                                    <option value="Assault">Assault / Violence</option>
                                    <option value="Cybercrime">Cybercrime / Fraud</option>
                                    <option value="Harassment">Harassment</option>
                                    <option value="Vandalism">Vandalism</option>
                                    <option value="Missing Person">Missing Person</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Date & Time</label>
                                <input type="datetime-local" name="datetime" id="datetime-input" class="form-control" required>
                                <small style="color: var(--text-muted); display: block; margin-top: 5px;">Auto-set to current time. Update if reporting a past incident.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="6" placeholder="Describe what happened in detail... (Key events, appearance of suspects, vehicle details etc.)" required id="description"></textarea>
                            </div>

                            <div class="form-group" style="background: #fff1f2; padding: 1rem; border-radius: 10px; border: 1px solid #fecdd3;">
                                <label style="display: flex; align-items: center; gap: 12px; font-weight: 700; color: #be123c; cursor: pointer;">
                                    <input type="checkbox" name="is_anonymous" value="1" id="is_anonymous" style="width: 18px; height: 18px;">
                                    <span><i class="fas fa-user-secret"></i> File as Anonymous Report</span>
                                </label>
                                <p style="font-size: 0.85rem; color: #9f1239; margin-top: 8px; margin-left: 30px;">
                                    If selected, your personal identity will be hidden from the investigating officers.
                                </p>
                            </div>

                            <div class="btn-group">
                                <div></div>
                                <button type="button" class="btn-gradient-cit" onclick="nextStep(2)">Next: Location <i class="fas fa-arrow-right" style="margin-left:8px;"></i></button>
                            </div>
                        </div>

                        <!-- STEP 2: Location -->
                        <div class="step-container" id="step-2">
                            <h3 class="card-title" style="margin-bottom: 1.5rem;">Incident Location</h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label class="form-label">District</label>
                                    <select name="district_id" class="form-control" required id="district_selector">
                                        <option value="">Select District</option>
                                        <?php
                                        // We need to re-open PDO connection if not available, mainly for IDE preview but standard include should work
                                        if(isset($pdo)) {
                                            $districts = $pdo->query("SELECT * FROM districts ORDER BY name ASC")->fetchAll();
                                            foreach ($districts as $d) {
                                                echo '<option value="'.$d['id'].'">'.$d['name'].'</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" value="Tamil Nadu" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Area / Locality</label>
                                <input type="text" name="area" class="form-control" placeholder="e.g. Anna Nagar, T. Nagar" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Exact Address / Landmark</label>
                                <textarea name="landmark" class="form-control" rows="2" placeholder="Street address or nearby landmark..." required></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Pinpoint on Map</label>
                                <div id="map-picker" style="height: 350px; border-radius: 10px; border: 2px solid var(--border); z-index: 1;"></div>
                                <div style="background: var(--background); padding: 10px; margin-top: 10px; border-radius: 8px; font-size: 0.9rem; color: var(--secondary); display: flex; justify-content: space-between; align-items: center;">
                                    <span><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> Selected Coordinates:</span>
                                    <span id="coord-display" style="font-family: monospace; font-weight: 700;">Not selected</span>
                                </div>
                                <input type="hidden" name="latitude" id="lat-input">
                                <input type="hidden" name="longitude" id="lng-input">
                            </div>

                            <div class="btn-group">
                                <button type="button" class="btn-outline-cit" onclick="prevStep(1)"><i class="fas fa-arrow-left" style="margin-right:8px;"></i> Previous</button>
                                <button type="button" class="btn-gradient-cit" onclick="nextStep(3)">Next: Verify <i class="fas fa-arrow-right" style="margin-left:8px;"></i></button>
                            </div>
                        </div>

                        <!-- STEP 3: Verify & Upload -->
                        <div class="step-container" id="step-3">
                            <h3 class="card-title" style="margin-bottom: 1.5rem;">Evidence & Verification</h3>

                            <div class="form-group" style="background: var(--background); padding: 1.5rem; border-radius: 12px; border: 1px dashed var(--border);">
                                <label class="form-label"><i class="fas fa-paperclip"></i> Upload Evidence (Optional)</label>
                                <input type="file" name="evidence[]" class="form-control" multiple accept="image/*,video/*,audio/*">
                                <small style="display: block; margin-top: 0.5rem; color: var(--text-muted);">
                                    Supports: JPG, PNG, MP4, MP3. Max Size: 50MB.
                                </small>
                            </div>

                            <div style="margin-top: 2rem; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
                                <h4 style="margin-bottom: 1rem; color: var(--secondary);">Report Summary</h4>
                                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem; color: var(--text-main);">
                                    <li style="margin-bottom: 8px;"><strong>Crime Type:</strong> <span id="summary-type">Not selected</span></li>
                                    <li style="margin-bottom: 8px;"><strong>Date:</strong> <span id="summary-date">Not set</span></li>
                                    <li style="margin-bottom: 8px;"><strong>Anonymous:</strong> <span id="summary-anon">No</span></li>
                                    <li style="margin-bottom: 8px;"><strong>Description:</strong> <br><span id="summary-desc" style="color: var(--text-muted); display: block; margin-top: 4px;">...</span></li>
                                </ul>
                            </div>

                            <div class="form-group" style="margin-top: 2rem;">
                                <label style="display: flex; align-items: start; gap: 12px; font-size: 0.95rem; color: var(--text-main); cursor: pointer;">
                                    <input type="checkbox" required style="margin-top: 4px; width: 18px; height: 18px;">
                                    <span>I hereby declare that the information provided is true to the best of my knowledge. I understand that filing a false report is a punishable offense.</span>
                                </label>
                            </div>

                            <div class="btn-group">
                                <button type="button" class="btn-outline-cit" onclick="prevStep(2)"><i class="fas fa-arrow-left" style="margin-right:8px;"></i> Previous</button>
                                <button type="submit" class="btn-gradient-cit">
                                    <i class="fas fa-check-circle" style="margin-right:8px;"></i> Submit Complaint
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
            
        </main>
    </div>

    <script src="assets/js/map-picker.js"></script>
    <script>
        // Set Default Date Time to Current
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('datetime-input').value = now.toISOString().slice(0, 16);

        function nextStep(step) {
            // Validate current step
            if (step === 2) {
                const type = document.getElementById('crime_type').value;
                const desc = document.getElementById('description').value;
                if (!type || !desc) {
                    alert("Please fill in all details first.");
                    return;
                }
                // Update Summary
                document.getElementById('summary-type').innerText = type;
                document.getElementById('summary-desc').innerText = desc.substring(0, 100) + '...';
                document.getElementById('summary-date').innerText = document.getElementById('datetime-input').value.replace('T', ' ');
                document.getElementById('summary-anon').innerText = document.getElementById('is_anonymous').checked ? 'Yes (Identity Protected)' : 'No';
            }
            if (step === 3) {
                 const area = document.querySelector('input[name="area"]').value;
                 const lat = document.getElementById('lat-input').value;
                 const lng = document.getElementById('lng-input').value;

                 if(!area) {
                     alert("Please enter Area/Locality.");
                     return;
                 }
                 if(!lat || !lng) {
                     alert("Please pinpoint the exact location on the map.");
                     return;
                 }
            }

            showStep(step);
        }

        function prevStep(step) {
            showStep(step);
        }

        function showStep(step) {
            // Hide all
            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
            // Remove active class from nodes
            document.querySelectorAll('.step-node').forEach(el => el.classList.remove('active'));
            
            // Show Target
            document.getElementById('step-' + step).classList.add('active');
            
            // Update Progress
            for(let i=1; i<=step; i++) {
                document.getElementById('node-' + i).classList.add('active');
            }

            // Fix Map Render Issue if entering Step 2
            if(step === 2 && window.reportMap) {
                // Leaflet needs multiple invalidations during CSS transitions on mobile
                const invalidate = () => {
                    window.reportMap.invalidateSize();
                    // If district is already selected, trigger a zoom to ensure correct bounds
                    const selector = document.getElementById('district_selector');
                    if (selector && selector.value && !document.getElementById('lat-input').value) {
                         selector.dispatchEvent(new Event('change'));
                    }
                };

                setTimeout(invalidate, 100);
                setTimeout(invalidate, 300);
                setTimeout(invalidate, 600);
                setTimeout(invalidate, 1000);
            }
        }

        // Sidebar Toggle for Mobile
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
    </script>
</body>
</html>
