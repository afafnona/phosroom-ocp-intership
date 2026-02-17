
<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';

// Correction pour les objets sérialisés
if (isset($_SESSION['user'])) {
    if (is_string($_SESSION['user'])) {
        $_SESSION['user'] = unserialize($_SESSION['user']);
    }
    
    // Si c'est une classe incomplète
    if ($_SESSION['user'] instanceof __PHP_Incomplete_Class) {
        $user = unserialize(serialize($_SESSION['user']));
        if (method_exists($user, '__wakeup')) {
            $user->__wakeup();
        }
        $_SESSION['user'] = $user;
    }
}

// Vérification de session
if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof Utilisateur)) {
    header('Location: form.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_reservations.php');
    exit;
}

// ... le reste du code reste inchangé ...
$reservationId = (int)$_GET['id'];
$user = $_SESSION['user'];

try {
    // Récupérer les détails de la réservation
    $stmt = $pdo->prepare("
        SELECT r.*, s.nom as salle_nom, s.localisation, s.capacite,
               IFNULL(e.theme, 'Non spécifié') as evenement_theme,
               IFNULL(CONCAT(u.prenom, ' ', u.nom), 'Non attribué') as formateur_nom
        FROM reservation r
        JOIN salles s ON r.salle_id = s.id
        LEFT JOIN evenement e ON e.salle_id = r.salle_id 
            AND e.date_debut <= r.date_fin 
            AND e.date_fin >= r.date_demande
        LEFT JOIN formateur f ON e.formateur_id = f.id
        LEFT JOIN utilisateurs u ON f.utilisateur_id = u.id
        WHERE r.id = ? AND r.utilisateur_id = ?
    ");
    $stmt->execute([$reservationId, $user->getId()]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        header('Location: my_reservations.php');
        exit;
    }

    // Récupérer les participants
    $stmtParticipants = $pdo->prepare("
        SELECT p.id, u.nom, u.prenom, p.service
        FROM evenement_participants ep
        JOIN participants p ON ep.participant_id = p.id
        JOIN utilisateurs u ON p.utilisateur_id = u.id
        JOIN evenement e ON ep.evenement_id = e.id
        WHERE e.salle_id = ? AND e.date_debut <= ? AND e.date_fin >= ?
    ");
    $stmtParticipants->execute([
        $reservation['salle_id'],
        $reservation['date_fin'],
        $reservation['date_demande']
    ]);
    $participants = $stmtParticipants->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'équipement
    $stmtEquipement = $pdo->prepare("
        SELECT nom, description, quantite 
        FROM equipement 
        WHERE salle_id = ?
    ");
    $stmtEquipement->execute([$reservation['salle_id']]);
    $equipement = $stmtEquipement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorMessage = "Erreur de base de données : " . $e->getMessage();
}
if (isset($_GET['success'])) {
    $message = '';
    $type = 'success';
    
    switch($_GET['success']) {
        case 'confirmed':
            $message = '✅ Réservation confirmée avec succès';
            break;
        case 'cancelled':
            $message = '✅ Réservation annulée avec succès';
            break;
        default:
            $message = '✅ Action réalisée avec succès';
    }
    
    echo "<div class='alert alert-success'>$message</div>";
}

// Display error messages
if (isset($_GET['error'])) {
    $message = '';
    $type = 'error';
    
    switch($_GET['error']) {
        case 'invalid_id':
            $message = '❌ ID de réservation invalide';
            break;
        case 'invalid_action':
            $message = '❌ Action non autorisée';
            break;
        case 'not_found':
            $message = '❌ Réservation non trouvée';
            break;
        case 'database':
            $message = '❌ Erreur de base de données';
            break;
        case 'general':
            $message = '❌ Une erreur est survenue';
            break;
        default:
            $message = '❌ Erreur lors de la mise à jour';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Réservation - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Utilisez le même CSS que dans my_reservations.php */
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--light);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dark-mode .page-title {
            color: var(--dark-accent);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .back-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .detail-card {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            transition: var(--transition);
        }

        .dark-mode .detail-card {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .detail-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .detail-card:hover {
            border-color: var(--dark-accent);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .detail-item {
            margin-bottom: 1.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: block;
        }

        .dark-mode .detail-label {
            color: var(--dark-accent);
        }

        .detail-value {
            font-size: 1.1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-confirmed {
            background: rgba(90, 141, 90, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status-pending {
            background: rgba(141, 123, 90, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status-cancelled {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .section-title {
            font-size: 1.5rem;
            margin: 2rem 0 1rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dark-mode .section-title {
            color: var(--dark-accent);
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .dark-mode th, 
        .dark-mode td {
            border-bottom-color: var(--dark-gray);
        }

        th {
            background: rgba(91, 143, 185, 0.1);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .dark-mode th {
            background: rgba(91, 143, 185, 0.2);
            color: var(--dark-accent);
        }

        tr:hover {
            background: rgba(91, 143, 185, 0.05);
        }

        .dark-mode tr:hover {
            background: rgba(91, 143, 185, 0.1);
        }

        .action-btns {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
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

        .btn-danger {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .btn-danger:hover {
            background: var(--error);
            color: white;
            transform: translateY(-3px);
        }

        /* Theme Toggle */
        .theme-toggle-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--glass);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            backdrop-filter: blur(5px);
            border: 1px solid var(--gray);
        }

        .dark-mode .theme-toggle-container {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
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

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .action-btns {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <span class="theme-label">Mode clair</span>
        <label class="theme-toggle">
            <input type="checkbox" id="theme-toggle">
            <span class="toggle-slider"></span>
        </label>
        <span class="theme-label">Mode sombre</span>
    </div>

    <div class="container">
        <a href="my_reservations.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Retour
        </a>

        <h1 class="page-title">
            <i class="fas fa-info-circle"></i>
            Détails de la réservation
        </h1>

        <?php if (isset($errorMessage)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="detail-card">
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Salle</span>
                    <p class="detail-value"><?= htmlspecialchars($reservation['salle_nom']) ?></p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Localisation</span>
                    <p class="detail-value"><?= htmlspecialchars($reservation['localisation']) ?></p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Capacité</span>
                    <p class="detail-value"><?= htmlspecialchars($reservation['capacite']) ?> personnes</p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Thème</span>
                    <p class="detail-value"><?= htmlspecialchars($reservation['evenement_theme']) ?></p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Formateur</span>
                    <p class="detail-value"><?= htmlspecialchars($reservation['formateur_nom']) ?></p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date de demande</span>
                    <p class="detail-value"><?= date('d/m/Y', strtotime($reservation['date_demande'])) ?></p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date de fin</span>
                    <p class="detail-value"><?= date('d/m/Y', strtotime($reservation['date_fin'])) ?></p>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Statut</span>
                    <p class="detail-value">
                        <span class="status-badge status-<?= strtolower($reservation['statut']) ?>">
                            <?= htmlspecialchars($reservation['statut']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <h2 class="section-title">
            <i class="fas fa-users"></i>
            Participants
        </h2>

        <?php if (!empty($participants)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td><?= htmlspecialchars($participant['nom']) ?></td>
                                <td><?= htmlspecialchars($participant['prenom']) ?></td>
                                <td><?= htmlspecialchars($participant['service']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun participant enregistré pour cet événement.</p>
        <?php endif; ?>

        <h2 class="section-title">
            <i class="fas fa-tools"></i>
            Équipement disponible
        </h2>

        <?php if (!empty($equipement)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Équipement</th>
                            <th>Description</th>
                            <th>Quantité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipement as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nom']) ?></td>
                                <td><?= htmlspecialchars($item['description']) ?></td>
                                <td><?= htmlspecialchars($item['quantite']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucun équipement spécifique n'est disponible dans cette salle.</p>
        <?php endif; ?>

        <div class="action-btns">
            <?php if ($reservation['statut'] == 'En attente'): ?>
                <a href="cancel_reservation.php?id=<?= $reservation['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-times"></i> Annuler la réservation
                </a>
            <?php endif; ?>
            <a href="my_reservations.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour aux réservations
            </a>
        </div>
    </div>

    <script>
        // Script pour le thème sombre/clair (identique à my_reservations.php)
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
                document.body.classList.add('dark-mode');
                themeToggle.checked = true;
            }
            
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        });
    </script>
</body>
</html>