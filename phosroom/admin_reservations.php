<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';

// Enhanced admin check function
function isUserAdmin() {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    // Handle serialized objects
    if (is_string($_SESSION['user'])) {
        $_SESSION['user'] = unserialize($_SESSION['user']);
    }
    
    // Handle incomplete classes
    if ($_SESSION['user'] instanceof __PHP_Incomplete_Class) {
        return false;
    }
    
    // Check if it's an Administrateur object
    if ($_SESSION['user'] instanceof Administrateur) {
        return true;
    }
    
    // Additional check: verify in database
    if (isset($_SESSION['user']->id)) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM administrateur WHERE utilisateur_id = ?");
            $stmt->execute([$_SESSION['user']->id]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            error_log("Admin check error: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

// Check admin access


// Get user object
$user = $_SESSION['user'];

// Enhanced Reservation class methods
class EnhancedReservation {
    public static function getAllWithDetails($period = 'week') {
        global $pdo;
        
        $dateConditions = [
            'week' => "r.date_debut >= CURDATE() AND r.date_debut <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            'month' => "r.date_debut >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND r.date_debut <= LAST_DAY(CURDATE())",
            'year' => "YEAR(r.date_debut) = YEAR(CURDATE())",
            'all' => "1=1"
        ];
        
        $condition = $dateConditions[$period] ?? $dateConditions['week'];
        
        // First check if date_debut column exists
        $checkColumn = $pdo->query("SHOW COLUMNS FROM reservation LIKE 'date_debut'");
        if (!$checkColumn->fetch()) {
            // If column doesn't exist, use date_demande as fallback
            $condition = str_replace('r.date_debut', 'r.date_demande', $condition);
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT r.*, 
                       s.nom as salle_nom, 
                       s.localisation,
                       CONCAT(u.prenom, ' ', u.nom) as user_name,
                       u.email as user_email
                FROM reservation r
                JOIN salles s ON r.salle_id = s.id
                JOIN utilisateurs u ON r.utilisateur_id = u.id
                WHERE $condition
                ORDER BY r.date_demande DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getById($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT r.*, 
                       s.nom as salle_nom, 
                       s.localisation,
                       CONCAT(u.prenom, ' ', u.nom) as user_name,
                       u.email as user_email
                FROM reservation r
                JOIN salles s ON r.salle_id = s.id
                JOIN utilisateurs u ON r.utilisateur_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function updateStatus($id, $status) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("UPDATE reservation SET statut = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function delete($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
}

// Gérer les actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
        $reservationId = $_POST['reservation_id'];
        
        try {
            switch ($_POST['action']) {
                case 'confirm':
                    if (EnhancedReservation::updateStatus($reservationId, 'confirmé')) {
                        $success = "Réservation confirmée avec succès";
                    } else {
                        $error = "Erreur lors de la confirmation";
                    }
                    break;
                    
                case 'cancel':
                    if (EnhancedReservation::updateStatus($reservationId, 'annulé')) {
                        $success = "Réservation annulée avec succès";
                    } else {
                        $error = "Erreur lors de l'annulation";
                    }
                    break;
                    
                case 'delete':
                    if (EnhancedReservation::delete($reservationId)) {
                        $success = "Réservation supprimée avec succès";
                    } else {
                        $error = "Erreur lors de la suppression";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

// Récupérer les réservations selon la période
$period = $_GET['period'] ?? 'week';
$reservations = EnhancedReservation::getAllWithDetails($period);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - PhosRoom</title>
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

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
        }

        .dark-mode .container {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        h1 {
            color: var(--primary-dark);
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dark-mode h1 {
            color: var(--dark-accent);
        }

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

        .period-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 0.8rem 1.5rem;
            border: 1px solid var(--gray);
            background: var(--white);
            color: var(--text);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark-mode .period-btn {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
            color: var(--dark-text);
        }

        .period-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .period-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 2rem;
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

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-confirmé {
            background: rgba(90, 141, 90, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status-en_attente {
            background: rgba(141, 123, 90, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status-annulé {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-danger {
            background: var(--error);
            color: var(--white);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--white);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            opacity: 0.9;
        }

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

        .theme-label {
            color: var(--text-light);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: var(--transition);
        }

        .theme-label.active {
            color: var(--primary);
        }

        .dark-mode .theme-label.active {
            color: var(--dark-accent);
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .period-filter {
                flex-direction: column;
            }
            
            .period-btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Gestion des Réservations</h1>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>

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

        <!-- Filtres par période -->
        <div class="period-filter">
            <a href="?period=week" class="period-btn <?= $period === 'week' ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> Cette semaine
            </a>
            <a href="?period=month" class="period-btn <?= $period === 'month' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Ce mois
            </a>
            <a href="?period=year" class="period-btn <?= $period === 'year' ? 'active' : '' ?>">
                <i class="fas fa-calendar"></i> Cette année
            </a>
            <a href="?period=all" class="period-btn <?= $period === 'all' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Toutes
            </a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Salle</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Statut</th>
                        <th>Date Demande</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reservations) > 0): ?>
                        <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td data-label="ID"><?= $reservation['id'] ?></td>
                            <td data-label="Utilisateur">
                                <strong><?= htmlspecialchars($reservation['user_name']) ?></strong><br>
                                <small><?= htmlspecialchars($reservation['user_email']) ?></small>
                            </td>
                            <td data-label="Salle">
                                <strong><?= htmlspecialchars($reservation['salle_nom']) ?></strong><br>
                                <small><?= htmlspecialchars($reservation['localisation']) ?></small>
                            </td>
                            <td data-label="Date Début">
                                <?= isset($reservation['date_debut']) ? date('d/m/Y H:i', strtotime($reservation['date_debut'])) : date('d/m/Y', strtotime($reservation['date_demande'])) ?>
                            </td>
                            <td data-label="Date Fin">
                                <?= date('d/m/Y', strtotime($reservation['date_fin'])) ?>
                            </td>
                            <td data-label="Statut">
                                <span class="status-badge status-<?= $reservation['statut'] ?>">
                                    <?= $reservation['statut'] ?>
                                </span>
                            </td>
                            <td data-label="Date Demande">
                                <?= date('d/m/Y', strtotime($reservation['date_demande'])) ?>
                            </td>
                            <td data-label="Actions" class="actions">
                                <a href="reservation_detail.php?id=<?= $reservation['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Détails
                                </a>
                                
                                <?php if ($reservation['statut'] === 'en attente'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Confirmer
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($reservation['statut'] !== 'annulé'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <button type="submit" name="action" value="cancel" class="btn btn-warning btn-sm"
                                                onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?')">
                                            <i class="fas fa-times"></i> Annuler
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation?')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-calendar-times" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                                <h3>Aucune réservation trouvée</h3>
                                <p>Aucune réservation ne correspond à la période sélectionnée.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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