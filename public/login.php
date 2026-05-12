<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SafeCity</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ef4444;
            --primary-dark: #dc2626;
            --secondary: #1e293b;
            --bg-main: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 2rem 1rem;
            background: 
                radial-gradient(circle at 10% 20%, rgba(239, 68, 68, 0.05) 0%, rgba(248, 250, 252, 0) 50%),
                radial-gradient(circle at 90% 80%, rgba(30, 58, 138, 0.05) 0%, rgba(248, 250, 252, 0) 50%);
        }

        /* Ambient background grid */
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(15, 23, 42, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
            pointer-events: none;
        }

        .auth-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 3.5rem;
            border-radius: 28px;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.12);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .professional-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            text-decoration: none;
        }

        .logo-shield {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.6rem;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.25);
            transform: rotate(-4deg);
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--secondary);
            letter-spacing: -0.5px;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .auth-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.6rem;
            color: var(--secondary);
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            background: #f1f5f9;
            border: 2px solid transparent;
            border-radius: 14px;
            padding: 1rem 1.2rem;
            color: var(--secondary);
            transition: all 0.3s;
            font-size: 1rem;
            box-sizing: border-box;
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(239, 68, 68, 0.2);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .btn-modern {
            width: 100%;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 1.1rem;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 12px 24px -6px rgba(239, 68, 68, 0.4);
            margin-top: 1.5rem;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -8px rgba(239, 68, 68, 0.5);
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f1f5f9;
        }

        .auth-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .auth-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 1.5rem;
            transition: 0.2s;
            font-weight: 600;
        }

        .back-link:hover {
            color: var(--secondary);
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-card" data-aos="zoom-in">
            <div class="auth-header">
                <a href="index" class="professional-logo">
                    <div class="logo-shield">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <span class="logo-text">SafeCity</span>
                </a>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Secure access to the Digital Justice Portal</p>
            </div>

            <form action="../app/controllers/auth_login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Email or Identity ID</label>
                    <input type="text" name="email" class="form-control" placeholder="user@gmail.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-modern">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                    Don't have an account? <a href="register" class="auth-link">Initialize Registration</a>
                </p>
                <a href="index" class="back-link">
                    <i class="fas fa-arrow-left"></i> Return to Home
                </a>
            </div>
        </div>
    </div>

</body>
</html>
