# crime-reporting-system# 🚨 Crime Reporting & Tracking System with GIS Map

A full-stack web application that enables citizens to report crimes online,
track case status in real time, and visualize crime incidents on an
interactive map — eliminating the need for physical police station visits.

---

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript, jQuery, Chart.js
- **Backend:** PHP 8.x, PDO
- **Database:** MySQL 8.0
- **Mapping:** Google Maps JavaScript API, Leaflet.js, OpenStreetMap
- **Email:** PHPMailer
- **Server:** Apache (XAMPP)

---

## ✨ Features

- 👤 Multi-role system — Citizen, Police Officer, Administrator
- 📝 Online crime complaint submission with evidence upload
- 📍 Interactive GIS map for precise incident location tagging
- 🔄 Real-time case status tracking (Submitted → Investigating → Closed)
- 🗺️ Crime heatmap and marker visualization for hotspot analysis
- 📊 Crime analytics dashboard with charts and export (PDF/CSV)
- 🔒 Secure authentication with bcrypt, CSRF protection, and RBAC
- 📧 Automated email notifications on every status update

---

## 👥 User Roles

| Role | Capabilities |
|------|-------------|
| Citizen | Register, submit reports, upload evidence, track status |
| Police Officer | View assigned cases, add investigation notes, close cases |
| Administrator | Manage users, assign cases, view analytics, monitor map |

---

## 🚀 How to Run Locally

1. Install [XAMPP](https://www.apachefriends.org/)
2. Clone this repository into `C:/xampp/htdocs/`
```bash
git clone https://github.com/Mohamedyasar216/crime-reporting-tracking-system.git
```
3. Import the database:
   - Open `phpMyAdmin`
   - Create a new database named `crime_reporting`
   - Import `all_users.sql` from the project folder
4. Start **Apache** and **MySQL** in XAMPP
5. Open your browser and go to:
