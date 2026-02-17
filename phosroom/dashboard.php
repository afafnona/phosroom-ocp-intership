
<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';
require_once 'sidebar.php';

// Initialiser les variables avec des valeurs par défaut
$notifications = [];
$reservations = [];
$sallesDisponibles = 0;
$formationsTerminees = 0;

// Variables pour la section admin
$totalUsers = 0;
$totalRooms = 0;
$totalReservations = 0;
$occupationRate = 0;
$latestReservations = [];

// Récupération des données depuis la base
// Récupération des données depuis la base
try {
    // Notifications
    if (isset($user)) {
        $notifications = Notification::getForUser($user->getId());
    }
    if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

    // Statistiques des salles disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM salles WHERE statut = 'disponible'");
    $sallesDisponibles = $stmt->fetchColumn();
    
    // Récupération des statistiques et réservations pour l'utilisateur
    if (isset($user)) {
        // DEBUG: Vérifier TOUTES les réservations de l'utilisateur d'abord
        $stmt = $pdo->prepare("SELECT id, nom, statut, date_demande, date_fin FROM reservation WHERE utilisateur_id = ?");
        $stmt->execute([$user->getId()]);
        $allUserReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("TOUTES les réservations de l'utilisateur " . $user->getId() . ": " . print_r($allUserReservations, true));
        
        // Compter le total des réservations
        $totalUserReservations = count($allUserReservations);
        
        // Compter par statut
        $confirmedCount = 0;
        $pendingCount = 0;
        
        foreach ($allUserReservations as $reservation) {
            $statut = strtolower($reservation['statut']);
            if (strpos($statut, 'confirm') !== false || $statut === 'confirmé') {
                $confirmedCount++;
            } elseif (strpos($statut, 'attente') !== false || $statut === 'en attente') {
                $pendingCount++;
            }
        }
        
        // Récupérer les réservations confirmées avec détails - Recherche large
        $confirmedReservations = [];
        $stmt = $pdo->prepare("
            SELECT r.id, r.nom, r.date_demande, r.date_fin, r.statut, r.utilisateur_id, r.salle_id,
                   s.nom as salle_nom, s.localisation
            FROM reservation r
            JOIN salles s ON r.salle_id = s.id
            WHERE r.utilisateur_id = ? AND (
                r.statut = 'confirmé' OR 
                r.statut = 'confirmé' OR
                LOWER(r.statut) LIKE '%confirm%' OR
                r.statut = 'approved' OR
                r.statut = 'validé'
            )
            ORDER BY r.date_demande DESC
        ");
        $stmt->execute([$user->getId()]);
        $confirmedReservations = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        // Récupérer les réservations en attente avec détails
        $pendingReservations = [];
        $stmt = $pdo->prepare("
            SELECT r.id, r.nom, r.date_demande, r.date_fin, r.statut, r.utilisateur_id, r.salle_id,
                   s.nom as salle_nom, s.localisation
            FROM reservation r
            JOIN salles s ON r.salle_id = s.id
            WHERE r.utilisateur_id = ? AND (
                r.statut = 'En attente' OR
                LOWER(r.statut) LIKE '%attente%' OR
                r.statut = 'pending' OR
                r.statut = 'en_attente'
            )
            ORDER BY r.date_demande DESC
        ");
        $stmt->execute([$user->getId()]);
        $pendingReservations = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        error_log("Après recherche - Confirmées: " . count($confirmedReservations) . ", En attente: " . count($pendingReservations));
        
    } else {
        $totalUserReservations = 0;
        $confirmedCount = 0;
        $pendingCount = 0;
        $confirmedReservations = [];
        $pendingReservations = [];
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $sallesDisponibles = 0;
    $totalUserReservations = 0;
    $confirmedCount = 0;
    $pendingCount = 0;
    $confirmedReservations = [];
    $pendingReservations = [];
}
// Récupération des statistiques pour l'admin
if (isset($user) && $user instanceof Administrateur) {
    try {
        // Nombre total d'utilisateurs - CORRIGÉ
        $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
        $totalUsers = $stmt->fetchColumn();
        
        // Nombre total de salles - CORRIGÉ
        $stmt = $pdo->query("SELECT COUNT(*) FROM salles");
        $totalRooms = $stmt->fetchColumn();
        
        // Nombre total de réservations - CORRIGÉ
        $stmt = $pdo->query("SELECT COUNT(*) FROM reservation");
        $totalReservations = $stmt->fetchColumn();
        
        // Taux d'occupation - CORRIGÉ (adapté à votre structure)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN (SELECT COUNT(*) FROM salles) > 0 THEN
                        ROUND(
                            (SELECT COUNT(*) FROM reservation WHERE statut = 'confirmé') / 
                            (SELECT COUNT(*) FROM salles) * 100, 
                            2
                        )
                    ELSE 0 
                END as occupation_rate
        ");
        $occupationRate = $stmt->fetchColumn();
        $occupationRate = $occupationRate ?: 0;
        
        // Dernières réservations - CORRIGÉ (adapté à votre structure)
        $stmt = $pdo->query("
            SELECT r.*, s.nom as salle_nom, 
                   CONCAT(u.prenom, ' ', u.nom) as user_name
            FROM reservation r
            JOIN salles s ON r.salle_id = s.id
            JOIN utilisateurs u ON r.utilisateur_id = u.id
            ORDER BY r.date_demande DESC
            LIMIT 4
        ");
        $latestReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error in admin section: " . $e->getMessage());
        $totalUsers = 0;
        $totalRooms = 0;
        $totalReservations = 0;
        $occupationRate = 0;
        $latestReservations = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - PhosRoom</title>
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
            color: var(--text);
            line-height: 1.7;
            background-color: var(--light);
            overflow-x: hidden;
            transition: background-color 0.5s ease, color 0.5s ease;
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

        
        .dark-mode .notification-btn {
            background: var(--dark-surface);
            color: var(--dark-accent);
            border-color: var(--dark-gray);
        }

        .notification-btn:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-3px) rotate(10deg);
            box-shadow: var(--shadow-md);
        }

        .dark-mode .notification-btn:hover {
            background: var(--dark-accent);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: var(--white);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        

        /* Welcome Section */
        .welcome-section {
            position: relative;
            border-radius: var(--radius);
            margin-bottom: 2.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            min-height: 350px;
            border: 1px solid var(--gray);
        }

        .dark-mode .welcome-section {
            border-color: var(--dark-gray);
        }

        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            opacity: 0.4;
            filter: brightness(0.8) saturate(1.2);
        }

        .dark-mode .video-background {
            opacity: 0.3;
            filter: brightness(0.6) saturate(1.2);
        }

        .welcome-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(58, 90, 120, 0.2) 0%, rgba(107, 141, 158, 0.1) 100%);
            z-index: 2;
        }

        .dark-mode .welcome-overlay {
            background: linear-gradient(45deg, rgba(15, 23, 42, 0.5) 0%, rgba(30, 41, 59, 0.3) 100%);
        }

        .welcome-card {
            position: relative;
            border-radius: var(--radius);
            padding: 4rem;
            z-index: 3;
            min-height: 350px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--glass);
            backdrop-filter: blur(5px);
        }

        .dark-mode .welcome-card {
            background: var(--dark-glass);
        }

        .welcome-title {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 8px rgba(0,0,0,0.1);
            line-height: 1.2;
        }

        .dark-mode .welcome-title {
            color: var(--dark-accent);
        }

        .welcome-subtitle {
            color: var(--text);
            max-width: 700px;
            font-size: 1.3rem;
            opacity: 0.95;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .dark-mode .welcome-subtitle {
            color: var(--dark-text-light);
        }

        .welcome-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1.8rem;
            border-radius: 50px;
            background: var(--white);
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            max-width: fit-content;
        }

        .dark-mode .welcome-cta {
            background: var(--dark-surface);
            color: var(--dark-accent);
            border-color: var(--dark-gray);
        }

        .welcome-cta:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .dark-mode .welcome-cta:hover {
            background: var(--dark-accent);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.75rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--gray);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .dark-mode .stat-card {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(91, 143, 185, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: -1;
        }

        .dark-mode .stat-card::before {
            background: linear-gradient(45deg, rgba(251, 191, 36, 0.05) 0%, rgba(30, 41, 59, 0) 100%);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .stat-card:hover {
            border-color: var(--dark-accent);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark-mode .stat-value {
            color: var(--dark-accent);
        }

        .stat-value i {
            font-size: 1.8rem;
            opacity: 0.8;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2.5rem 0 1.5rem;
            position: relative;
        }

        .section-title {
            font-size: 1.7rem;
            font-weight: 600;
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
            font-size: 1.5rem;
        }

        .dark-mode .section-title i {
            color: var(--dark-accent);
        }

        /* Reservations Grid */
        .reservations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.75rem;
        }

        .reservation-card {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--gray);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .dark-mode .reservation-card {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(91, 143, 185, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: -1;
        }

        .dark-mode .reservation-card::before {
            background: linear-gradient(45deg, rgba(251, 191, 36, 0.05) 0%, rgba(30, 41, 59, 0) 100%);
        }

        .reservation-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .reservation-card:hover {
            border-color: var(--dark-accent);
        }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--gray);
        }

        .reservation-title {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 1.2rem;
        }

        .dark-mode .reservation-title {
            color: var(--dark-accent);
        }

        .reservation-status {
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .dark-mode .reservation-status {
            background: var(--dark-surface);
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

        .reservation-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .detail-item i {
            color: var(--primary);
            width: 20px;
            text-align: center;
            transition: var(--transition);
            font-size: 1rem;
        }

        .dark-mode .detail-item i {
            color: var(--dark-accent);
        }

        .reservation-card:hover .detail-item i {
            transform: rotate(10deg) scale(1.1);
            color: var(--primary-dark);
        }

        .dark-mode .reservation-card:hover .detail-item i {
            color: var(--dark-accent);
        }

        .reservation-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
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

        /* Admin Section */
        .admin-section {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-top: 3rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .dark-mode .admin-section {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .admin-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--primary-light) 0%, rgba(255,255,255,0) 70%);
            opacity: 0.1;
            transform: translate(50px, -50px);
        }

        .dark-mode .admin-section::before {
            background: radial-gradient(circle, var(--dark-accent) 0%, rgba(30,41,59,0) 70%);
        }

        .admin-title {
            font-size: 1.7rem;
            font-weight: 600;
            margin-bottom: 2rem;
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
            font-size: 1.5rem;
        }

        .dark-mode .admin-title i {
            color: var(--dark-accent);
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.75rem;
        }

        .admin-card {
            background: var(--glass);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid var(--gray);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .dark-mode .admin-card {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .admin-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .admin-card:hover {
            border-color: var(--dark-accent);
        }

        .admin-card i {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .dark-mode .admin-card i {
            color: var(--dark-accent);
        }

        .admin-card:hover i {
            transform: scale(1.2);
            color: var(--primary-dark);
        }

        .dark-mode .admin-card:hover i {
            color: var(--dark-accent);
        }

        .admin-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            color: var(--primary-dark);
            transition: var(--transition);
        }

        .dark-mode .admin-card h3 {
            color: var(--dark-accent);
        }

        .admin-card p {
            font-size: 0.95rem;
            color: var(--text-light);
            opacity: 0.9;
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

        /* Floating Elements */
        .floating {
            position: absolute;
            background: var(--glass);
            border-radius: 50%;
            backdrop-filter: blur(5px);
            animation: float 6s ease-in-out infinite;
            z-index: 1;
        }

        .dark-mode .floating {
            background: var(--dark-glass);
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        /* Toggle Switch */
                /* Theme Toggle - Version Compacte Professionnelle */
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

        .theme-toggle-container:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .theme-toggle-container {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .dark-mode .theme-toggle-container:hover {
            border-color: var(--dark-accent);
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
            border: 1px solid transparent;
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
@media (max-width: 1024px) {
    .app-container {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        height: auto;
        max-height: 60vh; /* Limite la hauteur sur mobile */
        position: static;
        border-right: none;
        border-bottom: 1px solid var(--gray);
        padding: 1.5rem;
        overflow-y: auto; /* Maintient le défilement */
    }
    
    .nav-menu {
        flex-direction: column; /* Conserve la disposition verticale */
        gap: 0.75rem;
        padding-bottom: 0.5rem;
    }
    
    .nav-item {
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }

    .nav-item:hover {
        transform: translateX(8px); /* Animation horizontale au lieu de verticale */
    }
}
/* Table Style */
.reservations-table {
    background: var(--glass);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray);
    overflow: hidden;
    backdrop-filter: blur(5px);
}

.dark-mode .reservations-table {
    background: var(--dark-glass);
    border-color: var(--dark-gray);
}

.table-header {
    background: var(--primary);
    color: var(--white);
    padding: 1.5rem 2rem;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 1rem;
    align-items: center;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dark-mode .table-header {
    background: var(--dark-primary);
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 1rem;
    padding: 1.5rem 2rem;
    align-items: center;
    border-bottom: 1px solid var(--gray);
    transition: var(--transition);
}

.dark-mode .table-row {
    border-bottom-color: var(--dark-gray);
}

.table-row:last-child {
    border-bottom: none;
}

.table-row:hover {
    background: rgba(91, 143, 185, 0.05);
    transform: translateX(5px);
}

.dark-mode .table-row:hover {
    background: rgba(251, 191, 36, 0.05);
}

.table-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.cell-main {
    font-weight: 600;
    color: var(--primary-dark);
    font-size: 0.95rem;
}

.dark-mode .cell-main {
    color: var(--dark-accent);
}

.cell-sub {
    font-size: 0.8rem;
    color: var(--text-light);
    opacity: 0.8;
}

.cell-date {
    font-weight: 500;
    color: var(--text);
    font-size: 0.9rem;
}

.cell-status {
    display: inline-flex;
    padding: 0.35rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    width: fit-content;
}

.status-confirmed {
    background: rgba(90, 141, 90, 0.15);
    color: var(--success);
    border: 1px solid var(--success);
}

.status-pending {
    background: rgba(141, 123, 90, 0.15);
    color: var(--warning);
    border: 1px solid var(--warning);
}

.status-cancelled {
    background: rgba(141, 90, 90, 0.15);
    color: var(--error);
    border: 1px solid var(--error);
}

.table-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.action-btn {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-view {
    background: var(--primary);
    color: var(--white);
}

.btn-view:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.btn-confirm {
    background: var(--success);
    color: var(--white);
}

.btn-confirm:hover {
    background: #4a7a4a;
    transform: translateY(-2px);
}

.btn-reject {
    background: var(--error);
    color: var(--white);
}

.btn-reject:hover {
    background: #7a4a4a;
    transform: translateY(-2px);
}

.btn-cancel {
    background: var(--warning);
    color: var(--white);
}

.btn-cancel:hover {
    background: #7a6a4a;
    transform: translateY(-2px);
}

/* Empty State for Table */
.table-empty-state {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-light);
}

.table-empty-state i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--primary);
}

.dark-mode .table-empty-state i {
    color: var(--dark-accent);
}

/* Responsive Table */
@media (max-width: 1024px) {
    .table-header {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        padding: 1rem;
    }
    
    .table-row {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        padding: 1rem;
    }
    
    .table-header .table-cell:nth-child(n+4),
    .table-row .table-cell:nth-child(n+4) {
        display: none;
    }
    
    .table-actions {
        grid-column: 1 / -1;
        justify-content: center;
        margin-top: 1rem;
    }
}

@media (max-width: 768px) {
    .table-header {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .table-row {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .table-header .table-cell:nth-child(n+2),
    .table-row .table-cell:nth-child(n+2) {
        display: none;
    }
}
    /* Quick Actions Styles */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.quick-action-card {
    background: var(--glass);
    border-radius: var(--radius);
    padding: 2rem;
    text-align: center;
    transition: var(--transition);
    border: 1px solid var(--gray);
    text-decoration: none;
    color: inherit;
    backdrop-filter: blur(5px);
}

.dark-mode .quick-action-card {
    background: var(--dark-glass);
    border-color: var(--dark-gray);
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
    text-decoration: none;
    color: inherit;
}

.dark-mode .quick-action-card:hover {
    border-color: var(--dark-accent);
}

.quick-action-card i {
    font-size: 2.5rem;
    color: var(--primary);
    margin-bottom: 1rem;
    transition: var(--transition);
}

.dark-mode .quick-action-card i {
    color: var(--dark-accent);
}

.quick-action-card:hover i {
    transform: scale(1.1);
    color: var(--primary-dark);
}

.dark-mode .quick-action-card:hover i {
    color: var(--dark-accent);
}

.quick-action-card h3 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: var(--primary-dark);
}

.dark-mode .quick-action-card h3 {
    color: var(--dark-accent);
}

.quick-action-card p {
    font-size: 0.9rem;
    color: var(--text-light);
    opacity: 0.8;
}

/* Status badges for user dashboard */
.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-confirmed {
    background: rgba(90, 141, 90, 0.15);
    color: var(--success);
    border: 1px solid var(--success);
}

.badge-pending {
    background: rgba(141, 123, 90, 0.15);
    color: var(--warning);
    border: 1px solid var(--warning);
}

/* User specific styles */
.user-welcome {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    padding: 2rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
}

.dark-mode .user-welcome {
    background: linear-gradient(135deg, var(--dark-primary) 0%, var(--dark-primary-light) 100%);
}

.user-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
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

   

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="section-title">
                    <i class="fas fa-home"></i>
                    Tableau de bord
                </h1>
                <div class="user-nav">
                    
                 
    <div class="user-avatar">
      
    </div>
</a>
                </div>
            </header>

        <!-- User Dashboard Section -->
<?php if (!($user instanceof Administrateur)): ?>
    <!-- Welcome Section -->
    <section class="welcome-section">
        <video autoplay muted loop class="video-background">
            <source src="./images/afaf.mp4" type="video/mp4">
        </video>
        <div class="welcome-overlay"></div>
        <div class="welcome-card">
            <h2 class="welcome-title">Bonjour, <?= htmlspecialchars($user->getPrenom()) ?> !</h2>
            <p class="welcome-subtitle">Bienvenue sur votre espace PhosRoom, où vous pouvez gérer facilement vos réservations de salles de formation et suivre vos activités.</p>
            <a href="new_reservation.php" class="welcome-cta">
                <i class="fas fa-plus"></i> Nouvelle réservation
            </a>
        </div>
    </section>
<?php if (isset($message)): ?>
    <div class="message <?= $message['type'] ?>">
        <?= htmlspecialchars($message['text']) ?>
    </div>
<?php endif; ?>
    <!-- User Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-calendar-check"></i>
                <?= count($confirmedReservations) ?>
            </div>
            <div class="stat-label">Réservations confirmées</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-clock"></i>
                <?= count($pendingReservations) ?>
            </div>
            <div class="stat-label">Réservations en attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-door-open"></i>
                <?= $sallesDisponibles ?>
            </div>
            <div class="stat-label">Salles disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-chart-line"></i>
                <?= count($confirmedReservations) + count($pendingReservations) ?>
            </div>
            <div class="stat-label">Total réservations</div>
        </div>
    </div>

    <!-- Réservations en attente -->
    <?php if (count($pendingReservations) > 0): ?>
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-clock"></i>
                Réservations en attente de confirmation
            </h2>
        </div>

        <div class="reservations-table">
            <div class="table-header">
                <div class="table-cell">Salle</div>
                <div class="table-cell">Date de début</div>
                <div class="table-cell">Date de fin</div>
                <div class="table-cell">Localisation</div>
                <div class="table-cell">Actions</div>
            </div>

            <?php foreach ($pendingReservations as $reservation): ?>
                <div class="table-row">
                    <div class="table-cell">
                        <span class="cell-main"><?= htmlspecialchars($reservation->salle_nom) ?></span>
                    </div>
                    <div class="table-cell">
                        <span class="cell-date"><?= date('d/m/Y', strtotime($reservation->date_demande)) ?></span>
                    </div>
                    <div class="table-cell">
                        <span class="cell-date"><?= date('d/m/Y', strtotime($reservation->date_fin)) ?></span>
                    </div>
                    <div class="table-cell">
                        <span class="cell-sub"><?= htmlspecialchars($reservation->localisation) ?></span>
                    </div>
                    <div class="table-cell">
                        <div class="table-actions">
                            <a href="reservation_details.php?id=<?= $reservation->id ?>" class="action-btn btn-view">
                                <i class="fas fa-eye"></i> Détails
                            </a>
                            <a href="cancel_reservation.php?id=<?= $reservation->id ?>" class="action-btn btn-cancel">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Prochaines réservations confirmées -->
    <div class="section-header" style="margin-top: 2rem;">
        <h2 class="section-title">
            <i class="fas fa-calendar-check"></i>
            Vos prochaines réservations confirmées
        </h2>
        <a href="my_reservations.php" class="btn btn-outline">
            <i class="fas fa-list"></i> Voir toutes
        </a>
    </div>

    <?php if (count($confirmedReservations) > 0): ?>
        <div class="reservations-table">
            <div class="table-header">
                <div class="table-cell">Salle</div>
                <div class="table-cell">Date de début</div>
                <div class="table-cell">Date de fin</div>
                <div class="table-cell">Localisation</div>
                <div class="table-cell">Actions</div>
            </div>

            <?php foreach ($confirmedReservations as $reservation): ?>
                <div class="table-row">
                    <div class="table-cell">
                        <span class="cell-main"><?= htmlspecialchars($reservation->salle_nom) ?></span>
                        <?php if (!empty($reservation->nom)): ?>
                            <span class="cell-sub"><?= htmlspecialchars($reservation->nom) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="table-cell">
                        <span class="cell-date"><?= date('d/m/Y', strtotime($reservation->date_demande)) ?></span>
                        <span class="cell-sub"><?= date('H:i', strtotime($reservation->date_demande)) ?></span>
                    </div>
                    <div class="table-cell">
                        <span class="cell-date"><?= date('d/m/Y', strtotime($reservation->date_fin)) ?></span>
                        <span class="cell-sub"><?= date('H:i', strtotime($reservation->date_fin)) ?></span>
                    </div>
                    <div class="table-cell">
                        <span class="cell-sub"><?= htmlspecialchars($reservation->localisation) ?></span>
                    </div>
                    <div class="table-cell">
                        <div class="table-actions">
                            <a href="reservation_details.php?id=<?= $reservation->id ?>" class="action-btn btn-view">
                                <i class="fas fa-eye"></i> Détails
                            </a>
                            <a href="cancel_reservation.php?id=<?= $reservation->id ?>" class="action-btn btn-cancel">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Aucune réservation confirmée</h3>
            <p>Vous n'avez pas de réservation confirmée pour le moment.</p>
            <a href="new_reservation.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Créer une réservation
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Admin Section (visible only for admins) -->
<?php if (isset($user) && $user instanceof Administrateur): ?>
    <!-- Admin Stats -->
    <div class="section-header" style="margin-top: 3rem;">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            Statistiques
        </h2>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-users"></i>
                <?= htmlspecialchars($totalUsers) ?>
            </div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-door-open"></i>
                <?= htmlspecialchars($totalRooms) ?>
            </div>
            <div class="stat-label">Salles</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <i class="fas fa-calendar-alt"></i>
                <?= htmlspecialchars($totalReservations) ?>
            </div>
            <div class="stat-label">Réservations</div>
        </div>
        
    </div>

    <!-- Section Gestion -->
    <div class="section-header" style="margin-top: 3rem;">
        <h2 class="section-title">
            <i class="fas fa-cog"></i>
            Gestion
        </h2>
    </div>

    <div class="admin-grid">
        <!-- Gestion des utilisateurs -->
        <a href="manage_users.php" class="admin-card">
            <i class="fas fa-user-cog"></i>
            <h3>Gestion des utilisateurs</h3>
        </a>
        
        <!-- Gestion des salles -->
        <a href="manage_rooms.php" class="admin-card">
            <i class="fas fa-door-open"></i>
            <h3>Gestion des salles</h3>
        </a>
        
        <!-- Gestion des réservations -->
        <a href="admin_reservations.php" class="admin-card">
            <i class="fas fa-calendar-alt"></i>
            <h3>Gestion des réservations</h3>
        </a>

        <!-- Rapports -->
        <a href="reports.php" class="admin-card">
            <i class="fas fa-chart-pie"></i>
            <h3>Rapports et statistiques</h3>
        </a>
    </div>

<!-- Dernières réservations (pour admin) -->
<div class="section-header" style="margin-top: 3rem;">
    <h2 class="section-title">
        <i class="fas fa-clock"></i>
        Dernières réservations
    </h2>
    <a href="reservation_history.php" class="btn btn-outline">
        <i class="fas fa-list"></i> Voir toutes
    </a>
</div>

<?php if (count($latestReservations) > 0): ?>
    <div class="reservations-table">
        <!-- En-tête du tableau -->
        <div class="table-header">
            <div class="table-cell">Salle & Utilisateur</div>
            <div class="table-cell">Date de début</div>
            <div class="table-cell">Date de fin</div>
            <div class="table-cell">Statut</div>
            <div class="table-cell">Actions</div>
        </div>

        <!-- Lignes du tableau -->
        <?php foreach ($latestReservations as $reservation): ?>
            <div class="table-row">
                <!-- Salle & Utilisateur -->
                <div class="table-cell">
                    <span class="cell-main"><?= htmlspecialchars($reservation['salle_nom']) ?></span>
                    <span class="cell-sub"><?= htmlspecialchars($reservation['user_name']) ?></span>
                </div>

                <!-- Date de début -->
                <div class="table-cell">
                    <span class="cell-date"><?= date('d/m/Y', strtotime($reservation['date_demande'])) ?></span>
                </div>

                <!-- Date de fin -->
                <div class="table-cell">
                    <span class="cell-date"><?= date('d/m/Y', strtotime($reservation['date_fin'])) ?></span>
                </div>

                <!-- Statut -->
                <div class="table-cell">
                    <?php
                    $statusClass = 'status-pending';
                    if ($reservation['statut'] === 'confirmé') {
                        $statusClass = 'status-confirmed';
                    } elseif ($reservation['statut'] === 'annulé') {
                        $statusClass = 'status-cancelled';
                    }
                    ?>
                    <span class="cell-status <?= $statusClass ?>">
                        <?= htmlspecialchars($reservation['statut']) ?>
                    </span>
                </div>

                <!-- Actions -->
                <div class="table-cell">
                    <div class="table-actions">
                        <a href="reservation_detail.php?id=<?= $reservation['id'] ?>" class="action-btn btn-view">
                            <i class="fas fa-eye"></i> Détails
                        </a>
                        
                        
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="reservations-table">
        <div class="table-empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Aucune réservation récente</h3>
            <p>Aucune réservation n'a été effectuée récemment.</p>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>


        </main>
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
        
        // Pulse animation for notification badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.addEventListener('animationiteration', () => {
                badge.style.animation = 'none';
                setTimeout(() => {
                    badge.style.animation = 'pulse 2s infinite';
                }, 10);
            });
        }
    </script>
</body>
</html>