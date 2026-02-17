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

// Actions sur les réservations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo->beginTransaction();
        $reservationId = $_POST['reservation_id'];
        
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE reservation SET statut = 'confirmé' WHERE id = ?");
                $stmt->execute([$reservationId]);
                $_SESSION['success'] = "Réservation confirmée avec succès";
                break;
            case 'reject':
                $stmt = $pdo->prepare("UPDATE reservation SET statut = 'annulé' WHERE id = ?");
                $stmt->execute([$reservationId]);
                $_SESSION['success'] = "Réservation annulée avec succès";
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM reservation WHERE id = ?");
                $stmt->execute([$reservationId]);
                $_SESSION['success'] = "Réservation supprimée avec succès";
                break;
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de l'opération: " . $e->getMessage();
    }
    header("Location: all_reservations.php");
    exit;
}

// Récupérer les paramètres de filtre
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Construire la requête avec filtres
$query = "
    SELECT r.*, 
           s.nom as salle_nom, 
           CONCAT(u.prenom, ' ', u.nom) as utilisateur_nom,
           u.email as utilisateur_email
    FROM reservation r
    JOIN salles s ON r.salle_id = s.id
    JOIN utilisateurs u ON r.utilisateur_id = u.id
";

$where = [];
$params = [];

if ($statusFilter) {
    $where[] = "r.statut = ?";
    $params[] = $statusFilter;
}

if ($dateFrom) {
    $where[] = "r.date_demande >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "r.date_demande <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY r.date_demande DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes les Réservations - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Light Mode - Palette professionnelle */
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
            
            /* Dark Mode - Palette luxueuse */
            --dark-bg: #0F172A;
            --dark-surface: #1E293B;
            --dark-text: #F1F5F9;
            --dark-text-light: #94A3B8;
            --dark-gray: #334155;
            --dark-primary: #5B8FB9;
            --dark-primary-light: #7FB3D5;
            --dark-primary-dark: #1E3A8A;
            --dark-accent: #FBBF24;
            
            /* Commun */
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            --glass: rgba(255, 255, 255, 0.3);
            --dark-glass: rgba(30, 41, 59, 0.5);
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        /* Dark Mode */
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

        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--light);
            color: var(--text);
            margin: 0;
            padding: 0;
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

        h1 {
            color: var(--primary-dark);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dark-mode h1 {
            color: var(--dark-accent);
        }

        h1 i {
            color: var(--primary);
        }

        .dark-mode h1 i {
            color: var(--dark-accent);
        }

        /* Filtres */
        .filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
        }

        .dark-mode .filters {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
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

        /* Boutons */
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
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--gradient);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            animation: gradientShift 3s ease infinite;
            background-size: 200% 200%;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background: var(--success);
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
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

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .dark-mode .btn-outline {
            border-color: var(--dark-accent);
            color: var(--dark-accent);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-3px);
        }

        .dark-mode .btn-outline:hover {
            background: var(--dark-accent);
        }

        /* Table styles */
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

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: rgba(58, 90, 120, 0.05);
        }

        .dark-mode .admin-table tr:hover td {
            background: rgba(91, 143, 185, 0.1);
        }

        .admin-table small {
            font-size: 0.8rem;
            color: var(--text-light);
            display: block;
            margin-top: 0.3rem;
        }

        .dark-mode .admin-table small {
            color: var(--dark-text-light);
        }

        /* Status badges */
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

        /* Actions */
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Theme toggle */
        .theme-toggle-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
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
            background: linear-gradient(to right, var(--primary), var(--accent));
            transition: var(--transition);
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 3px;
            bottom: 3px;
            background-color: var(--white);
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        .theme-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .dark-mode .theme-label {
            color: var(--dark-text-light);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .filter-form {
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
            
           
        }

        /* Messages flash */
        .flash-message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .flash-success {
            background: rgba(90, 141, 90, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .flash-error {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    

    <div class="container">
        <h1><i class="fas fa-calendar-alt"></i> Toutes les réservations</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="flash-message flash-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-message flash-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="status">Statut:</label>
                    <select name="status" id="status">
                        <option value="">Tous</option>
                        <option value="confirmé" <?= $statusFilter === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                        <option value="en attente" <?= $statusFilter === 'en attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="annulé" <?= $statusFilter === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_from">De:</label>
                    <input type="date" name="date_from" id="date_from" value="<?= $dateFrom ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">À:</label>
                    <input type="date" name="date_to" id="date_to" value="<?= $dateTo ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
                <?php if ($statusFilter || $dateFrom || $dateTo): ?>
                    <a href="reservation_history.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Salle</th>
                        <th>Utilisateur</th>
                        <th>Date Demande</th>
                        <th>Date Fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td data-label="ID"><?= $reservation['id'] ?></td>
                        <td data-label="Salle"><?= htmlspecialchars($reservation['salle_nom']) ?></td>
                        <td data-label="Utilisateur">
                            <?= htmlspecialchars($reservation['utilisateur_nom']) ?>
                            <small><?= htmlspecialchars($reservation['utilisateur_email']) ?></small>
                        </td>
                        <td data-label="Date Demande"><?= date('d/m/Y H:i', strtotime($reservation['date_demande'])) ?></td>
                        <td data-label="Date Fin"><?= date('d/m/Y H:i', strtotime($reservation['date_fin'])) ?></td>
                        <td data-label="Statut">
                            <span class="status-badge status-<?= str_replace(' ', '_', $reservation['statut']) ?>">
                                <?= ucfirst($reservation['statut']) ?>
                            </span>
                        </td>
                        <td class="actions" data-label="Actions">
                            <?php if ($reservation['statut'] === 'en attente'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Confirmer
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times"></i> Refuser
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                               
                            </form>
                            <a href="reservation_detail.php?id=<?= $reservation['id'] ?>" class="btn btn-sm btn-outline">
                                <i class="fas fa-eye"></i> Détails
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
       
        
        // Check for saved theme preference or use system preference
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
            document.body.classList.add('dark-mode');
            themeToggle.checked = true;
        }
        
        // Listen for toggle changes
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
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

        // Run on load and resize
        window.addEventListener('load', adaptTableForMobile);
        window.addEventListener('resize', adaptTableForMobile);
    </script>
</body>
</html>