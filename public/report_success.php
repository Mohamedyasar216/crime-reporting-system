<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

$id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Submitted - SafeCity</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
    <style>
        :root {
            --primary: #19485F;
            --accent: #D9E0A4;
            --gradient-main: linear-gradient(135deg, #19485F 0%, #2c6e8f 100%);
            --gradient-accent: linear-gradient(135deg, #D9E0A4 0%, #b8c17d 100%);
            --bg-light: #f4f7f5;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .success-card {
            background: white;
            padding: 3rem;
            border-radius: 24px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .success-icon {
            width: 80px; height: 80px;
            background: var(--gradient-accent);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: 0 4px 15px rgba(217,224,164,0.3);
        }

        .btn-gradient-cit {
            background: var(--gradient-accent);
            color: var(--primary);
            font-weight: 700;
            padding: 1rem;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(217,224,164,0.3);
        }
        .btn-gradient-cit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(217,224,164,0.4); }

        .btn-outline-cit {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.3s;
        }
        .btn-outline-cit:hover { background: var(--primary); color: white; }
    </style>
</head>
<body>

    <div class="success-card">
        
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1 style="color: var(--primary); font-weight: 800; margin-bottom: 1rem;">Report Filed Successfully</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem; line-height: 1.6;">
            Your report has been securely recorded. <br>
            Reference ID: <strong style="color: var(--primary);">#<?php echo $id; ?></strong>
        </p>

        <div style="background: var(--bg-light); border-radius: 15px; padding: 1.5rem; text-align: left; margin-bottom: 2rem; border: 1px solid rgba(0,0,0,0.05);">
            <p style="margin: 0 0 12px; font-size: 0.95rem; color: var(--primary); font-weight: 800;">
                <i class="fas fa-info-circle"></i> What happens next?
            </p>
            <ul style="margin: 0; padding-left: 1.2rem; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0; line-height: 1.6;">
                <li style="margin-bottom: 8px;">Your report has been sent to the relevant district police.</li>
                <li style="margin-bottom: 8px;">An officer will be assigned to review the details shortly.</li>
                <li>You can track the status in your dashboard.</li>
            </ul>
        </div>

        <div style="display: flex; gap: 1rem; flex-direction: column;">
            <a href="view_case?id=<?php echo $id; ?>" class="btn-gradient-cit">
                <i class="fas fa-file-alt"></i> View Report Details
            </a>
            <a href="dashboard" class="btn-outline-cit">
                <i class="fas fa-home"></i> Return to Dashboard
            </a>
        </div>

    </div>

</body>
</html>
