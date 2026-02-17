<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';
require_once 'sidebar.php';

// Vérifier que l'utilisateur est admin
if (!($user instanceof Administrateur)) {
    header("Location: dashboard.php");
    exit;
}

// Initialiser les variables
$success = '';
$error = '';

// Générer un rapport
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $dateFrom = $_POST['date_from'];
    $dateTo = $_POST['date_to'];
    $reportType = $_POST['report_type'];
    
    // CORRECTION : Utiliser date_demande au lieu de date_debut
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reservations,
            SUM(TIMESTAMPDIFF(HOUR, date_demande, date_fin)) as total_hours,
            COUNT(DISTINCT utilisateur_id) as unique_users,
            COUNT(DISTINCT salle_id) as unique_rooms
        FROM reservation
        WHERE date_demande BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get administrator ID for the current user
    $adminStmt = $pdo->prepare("SELECT id FROM administrateur WHERE utilisateur_id = ?");
    $adminStmt->execute([$user->getId()]);
    $adminId = $adminStmt->fetchColumn();
    
    if ($adminId) {
        // Enregistrer le rapport dans la base
        $stmt = $pdo->prepare("
            INSERT INTO rapport 
            (type, date_generation, date_debut_periode, date_fin_periode, nb_reservations, nb_heures, administrateur_id)
            VALUES (?, NOW(), ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $reportType,
            $dateFrom,
            $dateTo,
            $stats['total_reservations'],
            $stats['total_hours'],
            $adminId
        ]);
        
        $reportId = $pdo->lastInsertId();
        $success = "Rapport généré avec succès (ID: $reportId)";
    } else {
        $error = "Erreur: Vous n'avez pas les droits d'administrateur nécessaires";
    }
}

// Récupérer tous les rapports générés
$reports = $pdo->query("
    SELECT r.*, CONCAT(u.prenom, ' ', u.nom) as admin_name
    FROM rapport r
    JOIN administrateur a ON r.administrateur_id = a.id
    JOIN utilisateurs u ON a.utilisateur_id = u.id
    ORDER BY r.date_generation DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports et Statistiques - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3A5A78;
            --primary-light: #6B8D9E;
            --primary-dark: #1D2F3F;
            --accent: #D4AF37;
            --light: #F8F9FA;
            --text: #2D3748;
            --text-light: #718096;
            --white: #FFFFFF;
            --gray: #E2E8F0;
            --success: #5A8D5A;
            --warning: #8D7B5A;
            --error: #8D5A5A;
            --dark-bg: #0F172A;
            --dark-surface: #1E293B;
            --dark-text: #F1F5F9;
            --dark-text-light: #94A3B8;
            --dark-gray: #334155;
            --dark-primary: #5B8FB9;
            --dark-accent: #FBBF24;
            
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            --glass: rgba(255, 255, 255, 0.3);
            --dark-glass: rgba(30, 41, 59, 0.5);
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode {
            --primary: var(--dark-primary);
            --primary-light: var(--dark-primary-light);
            --primary-dark: var(--dark-primary-dark);
            --accent: var(--dark-accent);
            --light: var(--dark-bg);
            --text: var(--dark-text);
            --text-light: var(--dark-text-light);
            --white: var(--dark-surface);
            --gray: var(--dark-gray);
            --glass: var(--dark-glass);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--light);
            color: var(--text);
            line-height: 1.7;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .main-content {
            padding: 2.5rem;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dark-mode .section-title {
            color: var(--dark-accent);
        }

        .section-title i {
            color: var(--primary);
        }

        .dark-mode .section-title i {
            color: var(--dark-accent);
        }

        .admin-section {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            backdrop-filter: blur(5px);
        }

        .dark-mode .admin-section {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .admin-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dark-mode .admin-title {
            color: var(--dark-accent);
        }

        .admin-title i {
            color: var(--primary);
        }

        .dark-mode .admin-title i {
            color: var(--dark-accent);
        }

        /* Form Styles */
        .report-form {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            margin-bottom: 2rem;
        }

        .dark-mode .report-form {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
        }

        .dark-mode .form-group label {
            color: var(--dark-text);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray);
            border-radius: var(--radius);
            background: var(--white);
            color: var(--text);
            transition: var(--transition);
            font-family: "Poppins", sans-serif;
        }

        .dark-mode .form-group input,
        .dark-mode .form-group select {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
            color: var(--dark-text);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 90, 120, 0.2);
        }

        .dark-mode .form-group input:focus,
        .dark-mode .form-group select:focus {
            border-color: var(--dark-accent);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-danger {
            background: var(--error);
            color: var(--white);
        }

        .btn-danger:hover {
            background: var(--error);
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .dark-mode .admin-table {
            background: var(--dark-surface);
        }

        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .dark-mode .admin-table th,
        .dark-mode .admin-table td {
            border-bottom-color: var(--dark-gray);
        }

        .admin-table th {
            background: var(--primary);
            color: var(--white);
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        .admin-table tr:hover td {
            background: rgba(58, 90, 120, 0.05);
        }

        .dark-mode .admin-table tr:hover td {
            background: rgba(91, 143, 185, 0.1);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: rgba(90, 141, 90, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .alert-error {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        /* Stats Cards */
        .stats-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            text-align: center;
            transition: var(--transition);
        }

        .dark-mode .stat-card {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .dark-mode .stat-value {
            color: var(--dark-accent);
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Theme Toggle */
        .theme-toggle-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--white);
            padding: 0.5rem 0.8rem;
            border-radius: 20px;
            border: 1px solid var(--gray);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .dark-mode .theme-toggle-container {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .theme-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }

        .theme-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray);
            transition: var(--transition);
            border-radius: 22px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: var(--white);
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        input:checked + .toggle-slider {
            background-color: var(--primary);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }

        .dark-mode input:checked + .toggle-slider {
            background-color: var(--dark-accent);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .admin-table {
                display: block;
            }
            
            .admin-table thead {
                display: none;
            }
            
            .admin-table tr {
                display: block;
                margin-bottom: 1rem;
                border-radius: var(--radius);
                box-shadow: var(--shadow-sm);
                overflow: hidden;
            }
            
            .admin-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.8rem 1rem;
                border-bottom: 1px solid var(--gray);
            }
            
            .admin-table td::before {
                content: attr(data-label);
                font-weight: 500;
                color: var(--primary);
                margin-right: 1rem;
            }
            
            .dark-mode .admin-table td::before {
                color: var(--dark-accent);
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <span class="theme-label active" id="label-light">Clair</span>
        <label class="theme-toggle">
            <input type="checkbox" id="theme-toggle">
            <span class="toggle-slider"></span>
        </label>
        <span class="theme-label" id="label-dark">Sombre</span>
    </div>

    <main class="main-content">
        <header class="header">
            <h1 class="section-title">
                <i class="fas fa-chart-pie"></i>
                Rapports et Statistiques
            </h1>
        </header>

        <!-- Messages d'alerte -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h2 class="admin-title">
                <i class="fas fa-file-export"></i>
                Générer un nouveau rapport
            </h2>
            
            <form method="POST" class="report-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="report_type">Type de rapport:</label>
                        <select name="report_type" id="report_type" required>
                            <option value="mensuel">Mensuel</option>
                            <option value="trimestriel">Trimestriel</option>
                            <option value="annuel">Annuel</option>
                            <option value="personnalisé">Personnalisé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">Période de:</label>
                        <input type="date" name="date_from" id="date_from" required 
                               value="<?= date('Y-m-01') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">À:</label>
                        <input type="date" name="date_to" id="date_to" required
                               value="<?= date('Y-m-t') ?>">
                    </div>
                </div>
                
                <button type="submit" name="generate_report" class="btn btn-primary">
                    <i class="fas fa-file-export"></i> Générer le rapport
                </button>
            </form>

            <!-- Aperçu des statistiques -->
            <div class="stats-preview">
                <div class="stat-card">
                    <div class="stat-value"><?= count($reports) ?></div>
                    <div class="stat-label">Rapports générés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?= array_sum(array_column($reports, 'nb_reservations')) ?>
                    </div>
                    <div class="stat-label">Réservations totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?= array_sum(array_column($reports, 'nb_heures')) ?>h
                    </div>
                    <div class="stat-label">Heures totales</div>
                </div>
            </div>
            
            <h2 class="admin-title" style="margin-top: 2rem;">
                <i class="fas fa-history"></i>
                Rapports précédents
            </h2>
            
            <?php if (count($reports) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Date Génération</th>
                                <th>Réservations</th>
                                <th>Heures</th>
                                <th>Généré par</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td data-label="ID"><?= $report['id'] ?></td>
                                <td data-label="Type"><?= ucfirst($report['type']) ?></td>
                                <td data-label="Période">
                                    <?= date('d/m/Y', strtotime($report['date_debut_periode'])) ?>
                                    - 
                                    <?= date('d/m/Y', strtotime($report['date_fin_periode'])) ?>
                                </td>
                                <td data-label="Date Génération">
                                    <?= date('d/m/Y H:i', strtotime($report['date_generation'])) ?>
                                </td>
                                <td data-label="Réservations"><?= $report['nb_reservations'] ?></td>
                                <td data-label="Heures"><?= $report['nb_heures'] ?>h</td>
                                <td data-label="Généré par"><?= htmlspecialchars($report['admin_name']) ?></td>
                                <td class="actions" data-label="Actions">
                                    <a href="export_report.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                        <button type="submit" name="delete_report" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport?')">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-light);">
                    <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>Aucun rapport généré</h3>
                    <p>Utilisez le formulaire ci-dessus pour générer votre premier rapport.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Dark mode toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Check for saved theme preference or use system preference
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
            document.body.classList.add('dark-mode');
            themeToggle.checked = true;
            document.getElementById('label-light').classList.remove('active');
            document.getElementById('label-dark').classList.add('active');
        }
        
        // Listen for toggle changes
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                document.getElementById('label-light').classList.remove('active');
                document.getElementById('label-dark').classList.add('active');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
                document.getElementById('label-light').classList.add('active');
                document.getElementById('label-dark').classList.remove('active');
            }
        });

        // Set default dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            document.getElementById('date_from').value = formatDate(firstDay);
            document.getElementById('date_to').value = formatDate(lastDay);
        });

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Adapt table for mobile view
        function adaptTableForMobile() {
            if (window.innerWidth <= 768) {
                document.querySelectorAll('.admin-table td').forEach(cell => {
                    const header = cell.parentNode.querySelector('th:nth-child(' + (cell.cellIndex + 1) + ')');
                    if (header) {
                        cell.setAttribute('data-label', header.textContent);
                    }
                });
            }
        }

        window.addEventListener('load', adaptTableForMobile);
        window.addEventListener('resize', adaptTableForMobile);
    </script>
</body>
</html>