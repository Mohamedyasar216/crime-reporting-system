<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeCity - Securing The Future</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Global & Reset Overrides */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { 
            width: 100%;
            max-width: 100%;
            overflow-x: hidden; 
            scroll-behavior: smooth; 
            background-color: #0b1120;
            font-family: 'Inter', sans-serif;
        }
        
        /* Navbar */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            background: transparent;
            transition: all 0.4s ease;
        }
        .navbar.scrolled {
            background: rgba(11, 17, 32, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 5%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .logo-text {
            font-size: 1.6rem;
            font-weight: 800;
            color: white; 
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-links { display: flex; gap: 2.5rem; align-items: center; margin-right: auto; margin-left: 4rem; }
        .nav-link { 
            text-decoration: none; color: #94a3b8; font-weight: 500; transition: 0.3s; 
            font-size: 1rem;
        }
        .nav-link:hover, .nav-link.active { color: white; }
        .menu-toggle { display: none; font-size: 1.5rem; color: white; cursor: pointer; }
        @media (max-width: 992px) { 
            .hide-on-mobile { display: none !important; } 
            .show-only-mobile { display: block !important; }
        }
        @media (min-width: 993px) {
            .show-only-mobile { display: none !important; }
        }

        .nav-btn {
            background: #ef4444;
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
            transition: 0.3s;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .nav-btn:hover {
            background: #dc2626;
            box-shadow: 0 15px 30px -5px rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
            color: white;
        }
        .nav-btn-outline {
            background: transparent;
            border: 2px solid #ef4444;
            color: white;
            padding: 0.7rem 1.8rem; /* Adjusted for border */
            box-shadow: none;
        }
        .nav-btn-outline:hover {
            background: #ef4444;
            color: white;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: #0b1120;
            color: white;
            padding: 8rem 5% 12rem;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(30, 58, 138, 0.4) 0%, rgba(11, 17, 32, 0) 50%),
                radial-gradient(circle at 90% 80%, rgba(220, 38, 38, 0.15) 0%, rgba(11, 17, 32, 0) 50%);
            z-index: 1;
        }
        
        .grid-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.5;
            z-index: 1;
            mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
        }

        /* Visual Elements */
        .visual-container { width: 100%; max-width: 500px; aspect-ratio: 1; position: relative; }
        .visual-circle-1 { position: absolute; inset: 0; border: 1px solid rgba(255,255,255,0.1); border-radius: 50%; }
        .visual-circle-2 { position: absolute; inset: 50px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 50%; animation: spin 20s linear infinite; }
        .visual-circle-3 { position: absolute; inset: 100px; border: 1px solid rgba(255,255,255,0.05); border-radius: 50%; }
        
        .visual-shield {
            position: absolute; top:50%; left:50%; transform: translate(-50%, -50%);
            width: 200px; height: 240px;
            background: linear-gradient(180deg, rgba(30, 58, 138, 0.4) 0%, rgba(15, 23, 42, 0.4) 100%);
            border: 1px solid rgba(59,130,246,0.3); backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center;
            clip-path: polygon(50% 0, 100% 20%, 100% 80%, 50% 100%, 0 80%, 0 20%);
            box-shadow: 0 0 50px rgba(59, 130, 246, 0.2);
        }
        
        .visual-card-bars {
            position: absolute; top: 20%; right: 0;
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1);
            padding: 1rem; border-radius: 12px; backdrop-filter: blur(10px);
            animation: float 4s ease-in-out infinite;
        }
        
        .visual-card-alert {
            position: absolute; bottom: 30%; left: -20px;
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1);
            padding: 0.8rem 1.2rem; border-radius: 12px; backdrop-filter: blur(10px);
            animation: float 5s ease-in-out infinite reverse;
            display: flex; align-items: center; gap: 10px;
        }

        .newsletter-input { width: 100%; padding: 1rem; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: white; margin-bottom: 1rem; box-sizing: border-box; } /* Fixed sizing */
        .newsletter-input:focus { outline: none; border-color: var(--primary); } 

        .hero-content {
            flex: 1;
            max-width: 750px;
            z-index: 20; /* High z-index to stay on top */
            position: relative;
        }

        .hero-visual {
            flex: 1;
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(30, 58, 138, 0.3);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 8px 16px;
            border-radius: 30px;
            margin-bottom: 2rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #60a5fa;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .hero-badge i { color: #3b82f6; }

        .hero-title {
            font-size: 4.5rem;
            line-height: 1.1;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1.5px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .highlight-red {
            background: linear-gradient(to right, #ef4444, #f87171);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-subtitle {
            font-size: 1.35rem;
            color: #94a3b8;
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 90%;
            font-weight: 400;
        }

        .btn-cta {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .btn-red-filled {
            background: #ef4444;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.5);
            border: 2px solid #ef4444;
        }
        .btn-red-filled:hover {
            background: #dc2626;
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(239, 68, 68, 0.6);
        }

        .btn-white-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        .btn-white-outline:hover {
            background: white;
            color: #ef4444;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(255, 255, 255, 0.2);
        }
        
        .hero-buttons {
            display: flex; 
            gap: 1.5rem; 
            flex-wrap: wrap; 
            justify-content: flex-start;
            align-items: center;
        }

        /* Stats Strip */
        .stats-strip {
            background: white;
            padding: 4rem 5%;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: -5rem;
            position: relative;
            z-index: 5;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -20px 50px rgba(0,0,0,0.1);
            border-bottom: 1px solid var(--border);
        }
        .stat-item { text-align: center; color: var(--secondary); transition: transform 0.3s; }
        .stat-item:hover { transform: translateY(-5px); }
        .stat-number { font-size: 3rem; font-weight: 900; color: transparent; background: linear-gradient(to right, var(--primary), var(--accent)); -webkit-background-clip: text; display: block; margin-bottom: 0.5rem; }
        .stat-label { color: var(--text-muted); font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; }

        /* Feature Section */
        .section-padding { padding: 8rem 5%; background: #f8fafc; }
        .section-title { text-align: center; max-width: 760px; margin: 0 auto 5rem; }
        .section-label { color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.9rem; margin-bottom: 1rem; display: block; }
        .section-title h2 { font-size: 3rem; font-weight: 900; color: var(--secondary); margin-bottom: 1.5rem; line-height: 1.2; }
        .section-title p { color: var(--text-muted); font-size: 1.2rem; line-height: 1.6; }

        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2.5rem; max-width: 1400px; margin: 0 auto; }
        .feature-box { background: white; padding: 3rem; border-radius: 24px; border: 1px solid #f1f5f9; transition: all 0.4s ease; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); z-index: 1; }
        .feature-box::before { content: ''; position: absolute; top:0; left:0; width: 100%; height: 0%; background: var(--primary-light); z-index: -1; transition: 0.4s; opacity: 0.3; }
        .feature-box:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(0,0,0,0.08); border-color: transparent; }
        .feature-box:hover::before { height: 100%; }
        .feature-icon-lg { width: 80px; height: 80px; background: #f8fafc; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--primary); margin-bottom: 1.5rem; transition: 0.3s; }
        .feature-box:hover .feature-icon-lg { background: white; color: var(--primary-dark); transform: scale(1.1); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        /* Steps */
        .step-card { display: flex; align-items: flex-start; gap: 2rem; background: white; padding: 2.5rem; border-radius: 20px; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9; transition: 0.3s; }
        .step-card:hover { transform: translateX(10px); border-color: var(--primary-light); }
        .step-number { font-size: 4rem; font-weight: 900; color: #e2e8f0; line-height: 1; font-family: 'Inter', sans-serif; flex-shrink: 0; } /* Prevent shrinking */

        /* CTA Section */
        .cta-section { background: linear-gradient(135deg, #1f2937 0%, #030712 100%); color: white; padding: 8rem 5%; text-align: center; position: relative; overflow: hidden; }
        .cta-bg { position: absolute; top:0; left:0; width:100%; height:100%; background-image: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 70%); }

        /* Footer */
        .footer-lg { background: #0b1120; color: #94a3b8; padding: 6rem 5% 2rem; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 4rem; margin-bottom: 4rem; }
        .footer-brand h3 { color: white; margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .footer-header { color: white; font-weight: 700; margin-bottom: 1.5rem; font-size: 1.1rem; }
        .footer-links ul { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 1rem; }
        .footer-links a { color: #94a3b8; text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: white; padding-left: 5px; }
        .newsletter-input { width: 100%; padding: 1rem; border-radius: 8px; border: 1px solid #334155; background: #1e293b; color: white; margin-bottom: 1rem; box-sizing: border-box; } /* Fixed sizing */
        .newsletter-input:focus { outline: none; border-color: var(--primary); }

        @media (max-width: 992px) {
            .navbar { padding: 1rem 5%; }
            .nav-links { 
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100vh;
                background: rgba(11, 17, 32, 0.98);
                backdrop-filter: blur(15px);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 2.5rem;
                margin: 0;
                z-index: 999;
                transform: translateX(100%);
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .nav-links.active { transform: translateX(0); display: flex; }
            .nav-link { font-size: 1.5rem; font-weight: 600; }
            .nav-link[href="register"], .nav-link[href="login"], .nav-link[href="dashboard"] { 
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white !important;
                padding: 0.8rem 2.5rem;
                border-radius: 50px;
                box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
                text-align: center;
                width: 80%;
                max-width: 300px;
            }
            
            .menu-toggle { display: block; z-index: 1001; font-size: 1.8rem; }
            .hero-section { flex-direction: column; text-align: center; padding-top: 9rem; }
            .hero-content { margin-bottom: 6rem; max-width: 100%; } /* Increased margin */
            .hero-title { font-size: 2.8rem; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 3rem; }
            .hero-subtitle { margin-left: auto; margin-right: auto; font-size: 1.1rem; }
            
            /* Adjust visual positioning on mobile */
            .visual-card-alert {
                bottom: 10%; /* Move lower */
                left: 0; /* Align better */
                right: 0; margin: auto; width: max-content; /* Center it */
            }
            .visual-card-bars {
                top: 10%;
                right: 5%;
            }
            
            /* Enhanced Mobile Buttons */
            .hero-buttons { 
                flex-direction: column; 
                align-items: center; 
                gap: 1rem; 
                width: 100%;
            }
            .btn-cta {
                width: 100%;
                max-width: 350px;
                justify-content: center;
                margin: 0 !important; /* Override any potential margins */
            }
        }
        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
            .step-card { flex-direction: column; gap: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; } /* Stack steps vertical on mobile */
            .step-number { font-size: 3rem; margin-bottom: 0.5rem; }
            .feature-grid { grid-template-columns: 1fr; } /* Force 1 column feature grid */
            .hero-title { font-size: 2.5rem; }
            .stats-strip { flex-direction: column; gap: 1rem; padding: 2rem 5%; }
            .navbar { padding: 1rem 1.25rem; }
            .section-padding { padding: 5rem 5%; } /* Reduce padding */
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <a href="index" class="logo-text">
            <i class="fas fa-shield-halved" style="color: #ef4444; font-size: 1.8rem;"></i>
            SafeCity
        </a>
        
        <div class="nav-links" id="navLinks">
            <a href="index" class="nav-link active">Home</a>
            <a href="#services" class="nav-link">Services</a>
            <a href="#impact" class="nav-link">Impact</a>
            <a href="map" class="nav-link">Interactive Map</a>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="login" class="nav-link show-only-mobile">Login</a>
                <a href="register" class="nav-link show-only-mobile">Register</a>
            <?php else: ?>
                <a href="dashboard" class="nav-link">Dashboard</a>
            <?php endif; ?>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="login" class="nav-btn nav-btn-outline hide-on-mobile">
                    Login
                </a>
                <a href="register" class="nav-btn hide-on-mobile">
                    Register
                </a>
            <?php else: ?>
                <a href="dashboard" class="nav-btn hide-on-mobile">
                    Dashboard
                </a>
                <a href="logout" class="nav-link hide-on-mobile" style="margin-left: 1rem; color: #ef4444; font-weight: 700;">
                    Logout
                </a>
            <?php endif; ?>
            <div class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-bg"></div>
        <div class="grid-overlay"></div>
        
        <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
            <div class="hero-badge">
                <i class="fas fa-check-circle"></i>
                OFFICIAL PUBLIC SAFETY PLATFORM
            </div>
            
            <h1 class="hero-title">
                Securing The Future<br>
                Through <span class="highlight-red">Digital Justice.</span>
            </h1>
            
            <p class="hero-subtitle">
                The most advanced Crime Reporting & Tracking System. Powered by real-time intelligence to protect every citizen, every street, every moment.
            </p>
            
            <div class="hero-buttons">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'report' : 'login'; ?>" class="btn-cta btn-red-filled">
                    <i class="fas fa-file-signature"></i> Report a File
                </a>
                <a href="map" class="btn-cta btn-white-outline">
                    <i class="fas fa-map-location-dot"></i> Live Crime Map
                </a>
            </div>
        </div>

        <div class="hero-visual" data-aos="fade-left" data-aos-duration="1200">
            <!-- Map/Tech Visual (CSS Classes) -->
            <div class="visual-container">
                <!-- Abstract Map Circles -->
                <div class="visual-circle-1"></div>
                <div class="visual-circle-2"></div>
                <div class="visual-circle-3"></div>
                
                <!-- Shield Element -->
                <div class="visual-shield">
                    <i class="fas fa-shield-cat" style="font-size: 5rem; color: rgba(255,255,255,0.8);"></i>
                </div>
                
                <!-- Floating Detail Cards -->
                <div class="visual-card-bars">
                    <div style="height: 6px; width: 100px; background: #3b82f6; border-radius: 3px; margin-bottom: 8px;"></div>
                    <div style="height: 6px; width: 60px; background: rgba(255,255,255,0.2); border-radius: 3px;"></div>
                </div>
                
                <div class="visual-card-alert">
                    <div style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444;"></div>
                    <span style="color: white; font-weight: 600; font-size: 0.9rem;">Live Alerts</span>
                </div>
            </div>
        </div>
    </header>

    <style>
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    </style>

    <!-- Stats Strip -->
    <div class="stats-strip">
        <div class="stat-item" data-aos="fade-up" data-aos-delay="100">
            <span class="stat-number">100%</span>
            <span class="stat-label">Anonymous</span>
        </div>
        <div class="stat-item" data-aos="fade-up" data-aos-delay="200">
            <span class="stat-number">15+</span>
            <span class="stat-label">Districts</span>
        </div>
        <div class="stat-item" data-aos="fade-up" data-aos-delay="300">
            <span class="stat-number">24/7</span>
            <span class="stat-label">Monitoring</span>
        </div>
        <div class="stat-item" data-aos="fade-up" data-aos-delay="400">
            <span class="stat-number">5k+</span>
            <span class="stat-label">Reports Filed</span>
        </div>
    </div>

    <!-- Features Section -->
    <section class="section-padding" id="services">
        <div class="section-title" data-aos="fade-up">
            <span class="section-label">Our Impact</span>
            <h2>Empowering Citizens with Technology</h2>
            <p>We've built a robust platform that puts safety back into your hands without compromising your privacy.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-box" data-aos="fade-up">
                <div class="feature-icon-lg"><i class="fas fa-mask"></i></div>
                <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;">Total Anonymity</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">Fear of retaliation prevents 70% of crimes from being reported. Our platform ensures your identity remains completely hidden if you choose.</p>
            </div>
            <div class="feature-box" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon-lg"><i class="fas fa-satellite-dish"></i></div>
                <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;">Real-time Tracking</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">Don't let your complaint get lost in paperwork. Track the status of your report from 'Filed' to 'Resolved' with live updates.</p>
            </div>
            <div class="feature-box" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon-lg"><i class="fas fa-map-marked-alt"></i></div>
                <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;">Hotspot Mapping</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">Our advanced GIS mapping visualizes crime incident data, helping you identify unsafe zones and helping police deploy resources effectively.</p>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="section-padding" id="impact" style="background: white;">
        <div class="section-title" data-aos="fade-up">
            <span class="section-label">Workflow</span>
            <h2>How It Works</h2>
            <p>A streamlined process designed for speed and transparency.</p>
        </div>

        <div style="max-width: 1000px; margin: 0 auto; display: grid; gap: 2rem;">
            <div class="step-card" data-aos="fade-right">
                <div class="step-number">01</div>
                <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.8rem; font-weight: 800; color: var(--secondary);">File a Report</h3>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">Fill out the simplified form. Upload photos/videos as evidence. Pin the location on the map. Select 'Anonymous' to protect your identity.</p>
                </div>
            </div>
            <div class="step-card" data-aos="fade-left">
                <div class="step-number" style="color: var(--primary-light);">02</div>
                <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.8rem; font-weight: 800; color: var(--secondary);">Automatic Escalation</h3>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">The system automatically routes the report to the District Superintendent of Police (SP) based on the location. No manual delays.</p>
                </div>
            </div>
            <div class="step-card" data-aos="fade-right">
                <div class="step-number" style="color: var(--accent);">03</div>
                <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.8rem; font-weight: 800; color: var(--secondary);">Justice Served</h3>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">Police investigate the issue and update the case file. You receive notifications on the resolution. The community gets safer.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-bg"></div>
        <div style="position: relative; z-index: 2; max-width: 800px; margin: 0 auto;" data-aos="zoom-in">
            <div style="background: rgba(239, 68, 68, 0.2); display: inline-block; padding: 0.5rem 1rem; border-radius: 50px; border: 1px solid rgba(255,255,255,0.3); margin-bottom: 2rem; font-weight: 700;">
                <i class="fas fa-exclamation-triangle"></i> EMERGENCY ONLY
            </div>
            <h2 style="font-size: 3rem; font-weight: 900; margin-bottom: 1.5rem;">Immediate Danger?</h2>
            <p style="font-size: 1.25rem; margin-bottom: 3rem; opacity: 0.9; line-height: 1.6;">
                This platform is for non-emergency reporting. If you or someone else is in immediate physical danger, do not wait. Call the emergency services now.
            </p>
            <a href="report" style="background: white; color: var(--danger); font-size: 1.5rem; font-weight: 900; padding: 1.2rem 4rem; border-radius: 50px; text-decoration: none; box-shadow: 0 10px 30px rgba(0,0,0,0.3); display: inline-flex; align-items: center; gap: 15px; transition: 0.3s;">
                <i class="fas fa-file-signature"></i> Report Your Crime
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-lg">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3><i class="fas fa-shield-cat"></i> SafeCity</h3>
                <p style="line-height: 1.8; opacity: 0.7; font-size: 1rem;">
                    SafeCity is an initiative to digitize crime reporting and force a transparent relationship between the citizens and the police force.
                </p>
            </div>
            <div class="footer-links">
                <div class="footer-header">Quick Links</div>
                <ul>
                    <li><a href="index">Home</a></li>
                    <li><a href="dashboard">Dashboard</a></li>
                    <li><a href="report">File Complaint</a></li>
                    <li><a href="map">Live Crime Map</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <div class="footer-header">Resources</div>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Contact Support</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <div class="footer-header">Stay Updated</div>
                <p style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 1rem;">Subscribe to our newsletter for safety alerts.</p>
                <form onsubmit="event.preventDefault(); alert('Subscribed!');">
                    <input type="email" class="newsletter-input" placeholder="Enter your email address">
                    <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 8px;">Subscribe</button>
                </form>
            </div>
        </div>
        <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 2rem; text-align: center; font-size: 0.9rem; color: #64748b;">
            &copy; <?php echo date('Y'); ?> SafeCity Initiative. Built for a safer tomorrow.
        </div>
    </footer>

    <!-- Animations Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Sticky Navbar with Scroll Logic
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                
                // Toggle icon
                const icon = menuToggle.querySelector('i');
                if (navLinks.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });

            // Close menu when clicking a link
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    const icon = menuToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                });
            });
        }
    </script>
</body>
</html>
