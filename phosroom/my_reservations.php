<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';
require_once __DIR__ . '/sidebar.php';



if (isset($_SESSION['user'])) {
    if (is_string($_SESSION['user'])) {
        $_SESSION['user'] = unserialize($_SESSION['user']);
    }
    
    // Si c'est toujours une classe incomplète
    if ($_SESSION['user'] instanceof __PHP_Incomplete_Class && isset($_SESSION['user_class'])) {
        $user = unserialize(serialize($_SESSION['user']));
        if (method_exists($user, '__wakeup')) {
            $user->__wakeup();
        }
        $_SESSION['user'] = $user;
    }
}

// Vérification de session
if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof Utilisateur)) {
    session_destroy();
    header('Location: form.php');
    exit;
}

$user = $_SESSION['user'];
$userId = $user->getId();

// Récupérer les réservations de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT r.*, s.nom as salle_nom, 
               IFNULL(e.theme, 'Non spécifié') as evenement_theme,
               IFNULL(CONCAT(u.prenom, ' ', u.nom), 'Non attribué') as formateur_nom
        FROM reservation r
        JOIN salles s ON r.salle_id = s.id
        LEFT JOIN evenement e ON e.salle_id = r.salle_id 
            AND e.date_debut <= r.date_fin 
            AND e.date_fin >= r.date_demande
        LEFT JOIN formateur f ON e.formateur_id = f.id
        LEFT JOIN utilisateurs u ON f.utilisateur_id = u.id
        WHERE r.utilisateur_id = ?
        ORDER BY r.date_demande DESC
    ");
    $stmt->execute([$userId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur de base de données : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Light Mode - Palette professionnelle */
            --primary: #3A5A78; /* Bleu profond */
            --primary-light: #6B8D9E; /* Bleu clair */
            --primary-dark: #1D2F3F; /* Bleu nuit */
            --accent: #D4AF37; /* Or */
            --light: #F8F9FA; /* Gris très clair */
            --text: #2D3748; /* Gris foncé */
            --text-light: #718096; /* Gris moyen */
            --white: #FFFFFF;
            --gray: #E2E8F0; /* Gris clair */
            --success: #5A8D5A; /* Vert discret */
            --warning: #8D7B5A; /* Marron clair */
            --error: #8D5A5A; /* Rouge terreux */
            
            /* Dark Mode - Palette luxueuse */
            --dark-bg: #0F172A; /* Bleu nuit profond */
            --dark-surface: #1E293B; /* Bleu surface */
            --dark-text: #F1F5F9; /* Blanc cassé */
            --dark-text-light: #94A3B8; /* Gris bleuté */
            --dark-gray: #334155; /* Gris bleu */
            --dark-primary: #5B8FB9; /* Bleu clair */
            --dark-primary-light: #7FB3D5; /* Bleu très clair */
            --dark-primary-dark: #1E3A8A; /* Bleu royal */
            --dark-accent: #FBBF24; /* Or chaud */
            
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

        /* Layout */
        .app-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

       

      

        /* Page Title */
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

        .page-title i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .dark-mode .page-title i {
            color: var(--dark-accent);
        }

        /* Message d'erreur */
        .message {
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            border-radius: var(--radius);
            font-weight: 500;
            background: var(--glass);
            backdrop-filter: blur(5px);
            border: 1px solid var(--gray);
            box-shadow: var(--shadow-sm);
        }

        .dark-mode .message {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .message.error {
            background: rgba(191, 97, 106, 0.2);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .dark-mode .message.error {
            background: rgba(191, 97, 106, 0.3);
        }

        /* Réservations */
        .reservations-container {
            background: var(--glass);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            backdrop-filter: blur(5px);
            border: 1px solid var(--gray);
            transition: var(--transition);
        }

        .dark-mode .reservations-container {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .reservations-container:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .reservations-container:hover {
            border-color: var(--dark-accent);
        }

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservations-table th, 
        .reservations-table td {
            padding: 1.25rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .dark-mode .reservations-table th, 
        .dark-mode .reservations-table td {
            border-bottom-color: var(--dark-gray);
        }

        .reservations-table th {
            background: rgba(91, 143, 185, 0.1);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .dark-mode .reservations-table th {
            background: rgba(91, 143, 185, 0.2);
            color: var(--dark-accent);
        }

        .reservations-table tr:hover {
            background: rgba(91, 143, 185, 0.05);
        }

        .dark-mode .reservations-table tr:hover {
            background: rgba(91, 143, 185, 0.1);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: rgba(90, 141, 90, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .dark-mode .status-confirmed {
            background: rgba(90, 141, 90, 0.3);
        }

        .status-pending {
            background: rgba(141, 123, 90, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .dark-mode .status-pending {
            background: rgba(141, 123, 90, 0.3);
        }

        .status-cancelled {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .dark-mode .status-cancelled {
            background: rgba(141, 90, 90, 0.3);
        }

        .action-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
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

        /* Empty State */
        .empty-state {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 3rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 2px dashed var(--gray);
            transition: var(--transition);
            margin: 2rem 0;
            backdrop-filter: blur(5px);
        }

        .dark-mode .empty-state {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .empty-state:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .dark-mode .empty-state:hover {
            border-color: var(--dark-accent);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .dark-mode .empty-state i {
            color: var(--dark-accent);
        }

        .empty-state:hover i {
            transform: scale(1.2) rotate(15deg);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--primary-dark);
        }

        .dark-mode .empty-state h3 {
            color: var(--dark-accent);
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 1.75rem;
            font-size: 1rem;
        }

        .dark-mode .empty-state p {
            color: var(--dark-text-light);
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

        /* Responsive */
        @media (max-width: 1024px) {
            .app-container {
                grid-template-columns: 1fr;
            }
            
           

        @media (max-width: 480px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.25rem;
            }
            
            .user-nav {
                width: 100%;
                justify-content: space-between;
            }
            
            .reservations-table th, 
            .reservations-table td {
                padding: 1rem;
            }
            
            .action-btn {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
   

    

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="page-title">
                    <i class="fas fa-list"></i>
                    Mes Réservations
                </h1>
                <div class="user-nav">
                    <button class="notification-btn">
                       
                        <?php if (isset($notifications) && count($notifications) > 0): ?>
                           
                        <?php endif; ?>
                    </button>
                  
                </div>
            </header>

            <?php if (isset($errorMessage)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Aucune réservation trouvée</h3>
                    <p>Vous n'avez pas encore effectué de réservation.</p>
                    <a href="new_reservation.php" class="action-btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Nouvelle réservation
                    </a>
                </div>
            <?php else: ?>
                <div class="reservations-container">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>Salle</th>
                                <th>Thème</th>
                                <th>Formateur</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reservation['salle_nom']) ?></td>
                                    <td><?= htmlspecialchars($reservation['evenement_theme'] ?? 'Non spécifié') ?></td>
                                    <td><?= htmlspecialchars($reservation['formateur_nom'] ?? 'Non attribué') ?></td>
                                    <td><?= date('d/m/Y', strtotime($reservation['date_demande'])) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($reservation['statut']) ?>">
                                            <?= htmlspecialchars($reservation['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="reservation_details.php?id=<?= $reservation['id'] ?>" class="action-btn btn-primary">
                                            <i class="fas fa-eye"></i> Détails
                                        </a>
                                        <?php if ($reservation['statut'] == 'En attente'): ?>
                                            <a href="cancel_reservation.php?id=<?= $reservation['id'] ?>" class="action-btn btn-outline">
                                                <i class="fas fa-times"></i> Annuler
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            
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

            // Animation pour les boutons (effet ripple)
            const buttons = document.querySelectorAll('.action-btn');
            
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size/2;
                    const y = e.clientY - rect.top - size/2;
                    
                    ripple.style.width = ripple.style.height = `${size}px`;
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 800);
                });
            });

            // Ajout du style pour l'effet ripple
            const style = document.createElement('style');
            style.textContent = `
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.8s ease-out;
                    pointer-events: none;
                }
                
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>