<?php
session_start();
require_once __DIR__ . '/sidebar.php';

$successMessage = '';
$errorMessage = '';

try {
    // Récupérer les données utilisateur
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user->getId()]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception("Utilisateur non trouvé dans la base de données");
    }

    // Récupérer les statistiques réelles depuis la base de données
    $userStats = [
        'total_reservations' => 0,
        'pending_reservations' => 0,
        'confirmed_reservations' => 0
    ];

    // Si c'est un participant, récupérer ses statistiques de réservation
    if ($user instanceof Participant) {
        // Total des réservations
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservation WHERE utilisateur_id = ?");
        $stmt->execute([$user->getId()]);
        $userStats['total_reservations'] = $stmt->fetchColumn();

        // Réservations en attente
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM reservation WHERE utilisateur_id = ? AND statut = 'En attente'");
        $stmt->execute([$user->getId()]);
        $userStats['pending_reservations'] = $stmt->fetchColumn();

        // Réservations confirmées
        $stmt = $pdo->prepare("SELECT COUNT(*) as confirmed FROM reservation WHERE utilisateur_id = ? AND statut = 'Confirmée'");
        $stmt->execute([$user->getId()]);
        $userStats['confirmed_reservations'] = $stmt->fetchColumn();
    }

    // Si c'est un administrateur, récupérer les statistiques globales
    if ($user instanceof Administrateur) {
        // Total des utilisateurs
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM utilisateurs");
        $stmt->execute();
        $userStats['total_users'] = $stmt->fetchColumn();

        // Total des salles
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM salles");
        $stmt->execute();
        $userStats['total_rooms'] = $stmt->fetchColumn();

        // Total des réservations
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservation");
        $stmt->execute();
        $userStats['total_reservations'] = $stmt->fetchColumn();

        // Réservations en attente (pour l'admin)
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM reservation WHERE statut = 'En attente'");
        $stmt->execute();
        $userStats['pending_reservations'] = $stmt->fetchColumn();
    }

    // Traitement du formulaire de mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = htmlspecialchars($_POST['nom'] ?? '');
        $prenom = htmlspecialchars($_POST['prenom'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation des champs obligatoires
        if (empty($nom) || empty($prenom) || empty($email)) {
            $errorMessage = "Tous les champs obligatoires doivent être remplis.";
        } else {
            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user->getId()]);
            if ($stmt->rowCount() > 0) {
                $errorMessage = "Cet email est déjà utilisé par un autre utilisateur.";
            } else {
                // Mise à jour des informations de base
                $updateFields = "nom = ?, prenom = ?, email = ?";
                $params = [$nom, $prenom, $email, $user->getId()];

                // Vérifier si l'utilisateur veut changer le mot de passe
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $errorMessage = "Veuillez entrer votre mot de passe actuel pour changer le mot de passe.";
                    } elseif (!password_verify($current_password, $userData['mot_de_passe'])) {
                        $errorMessage = "Le mot de passe actuel est incorrect.";
                    } elseif ($new_password !== $confirm_password) {
                        $errorMessage = "Les nouveaux mots de passe ne correspondent pas.";
                    } elseif (strlen($new_password) < 6) {
                        $errorMessage = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                    } else {
                        $updateFields .= ", mot_de_passe = ?";
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $params = [$nom, $prenom, $email, $hashed_password, $user->getId()];
                    }
                }

                if (empty($errorMessage)) {
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET $updateFields WHERE id = ?");
                    if ($stmt->execute($params)) {
                        $successMessage = "Profil mis à jour avec succès!";
                        
                        // Mettre à jour les données de session
                        $user->nom = $nom;
                        $user->prenom = $prenom;
                        $user->email = $email;
                        $_SESSION['user'] = serialize($user);
                        
                        // Recharger les données utilisateur
                        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
                        $stmt->execute([$user->getId()]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $errorMessage = "Erreur lors de la mise à jour du profil.";
                    }
                }
            }
        }
    }

} catch (PDOException $e) {
    $errorMessage = "Erreur de base de données: " . $e->getMessage();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}

// Traitement de la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: form.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Utilisez les mêmes styles CSS que dashboard.php */
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
            color: var(--text);
            line-height: 1.7;
            background-color: var(--light);
            overflow-x: hidden;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            border: 1px solid;
            animation: slideIn 0.5s ease-out;
        }

        .message.success {
            background: rgba(90, 141, 90, 0.1);
            color: var(--success);
            border-color: var(--success);
        }

        .message.error {
            background: rgba(141, 90, 90, 0.1);
            color: var(--error);
            border-color: var(--error);
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Layout */
        .app-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar - Identique au dashboard */
        .sidebar {
            background: var(--white);
            border-right: 1px solid var(--gray);
            padding: 2rem 1.5rem;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .dark-mode .sidebar {
            background: var(--dark-surface);
            border-right-color: var(--dark-gray);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            padding: 0 1rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 1.5rem;
        }

        /* Nav Menu */
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: var(--radius);
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            background: var(--glass);
            backdrop-filter: blur(5px);
            border: 1px solid var(--gray);
        }

        .dark-mode .nav-item {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(91, 143, 185, 0.1);
            color: var(--primary-dark);
            transform: translateX(8px);
            border-color: var(--primary-light);
        }

        .dark-mode .nav-item:hover, 
        .dark-mode .nav-item.active {
            background: rgba(91, 143, 185, 0.2);
            color: var(--dark-accent);
            border-color: var(--dark-accent);
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(91, 143, 185, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
            color: var(--primary-dark);
            font-weight: 500;
        }

        .dark-mode .nav-item.active {
            background: linear-gradient(90deg, rgba(91, 143, 185, 0.2) 0%, rgba(30, 41, 59, 0) 100%);
        }

        .nav-item i {
            width: 24px;
            text-align: center;
            transition: var(--transition);
            color: var(--primary);
        }

        .dark-mode .nav-item i {
            color: var(--dark-accent);
        }

        /* Main Content */
        .main-content {
            padding: 2.5rem;
            position: relative;
            background-color: var(--light);
            transition: background-color 0.3s ease;
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

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        /* Section Header */
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

        /* Profile Section */
        .profile-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .profile-card {
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

        .dark-mode .profile-card {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
            box-shadow: var(--shadow-md);
            border: 3px solid var(--white);
        }

        .admin-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--accent);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-name {
            font-size: 1.5rem;
            text-align: center;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .dark-mode .profile-name {
            color: var(--dark-accent);
        }

        .profile-email {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .profile-role {
            text-align: center;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-transform: capitalize;
        }

        .dark-mode .profile-role {
            color: var(--dark-accent);
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .stat-item {
            background: var(--glass);
            padding: 1rem;
            border-radius: var(--radius);
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
            transition: var(--transition);
        }

        .dark-mode .stat-item {
            background: var(--dark-glass);
            border-color: var(--dark-gray);
        }

        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .dark-mode .stat-number {
            color: var(--dark-accent);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-dark);
            font-weight: 500;
        }

        .dark-mode .form-group label {
            color: var(--dark-accent);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
        }

        .dark-mode .form-control {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
            color: var(--dark-text);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(136, 192, 208, 0.2);
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
        }

        .password-toggle i:hover {
            color: var(--primary);
        }

        .dark-mode .password-toggle i:hover {
            color: var(--dark-accent);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 1rem;
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
            transform: translateY(-2px);
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

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--error);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #7a4a4a;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .app-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid var(--gray);
                padding: 1.5rem;
            }
            
            .nav-menu {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding-bottom: 0.5rem;
            }
            
            .nav-item {
                padding: 0.75rem 1rem;
                white-space: nowrap;
            }

            .nav-item:hover {
                transform: translateY(-3px);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1.75rem;
            }

            .profile-stats,
            .admin-stats {
                grid-template-columns: 1fr;
            }
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
        }
    </style>
</head>
<body>
    
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="section-title">
                    <i class="fas fa-user-cog"></i>
                    Mon Profil
                </h1>
                <div class="header-actions">
                    <a href="?logout=true" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter?')">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </header>

            <!-- Messages -->
            <?php if ($successMessage): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <div class="profile-section">
                <!-- Profile Card -->
                <div class="profile-card">
                    <?php if ($user instanceof Administrateur): ?>
                        <div class="admin-badge">
                            <i class="fas fa-crown"></i> Administrateur
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-avatar"><?= substr($user->getPrenom(), 0, 1) . substr($user->getNom(), 0, 1) ?></div>
                    <h2 class="profile-name"><?= htmlspecialchars($user->getPrenom()) ?> <?= htmlspecialchars($user->getNom()) ?></h2>
                    <p class="profile-email"><?= htmlspecialchars($userData['email']) ?></p>
                    <p class="profile-role">
                        <i class="fas fa-user-tag"></i>
                        <?= htmlspecialchars($userData['role']) ?>
                    </p>
                    
                    <?php if ($user instanceof Administrateur): ?>
                        <!-- Statistiques Admin -->
                        <div class="admin-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $userStats['total_users'] ?></div>
                                <div class="stat-label">Utilisateurs</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $userStats['total_rooms'] ?></div>
                                <div class="stat-label">Salles</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $userStats['total_reservations'] ?></div>
                                <div class="stat-label">Réservations</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Statistiques Participant/Formateur -->
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= $userStats['total_reservations'] ?></div>
                                <div class="stat-label">Réservations</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= $userStats['pending_reservations'] ?></div>
                                <div class="stat-label">En attente</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Card -->
                <div class="profile-card">
                    <form method="POST">
                        <h2 class="section-title">
                            <i class="fas fa-user-edit"></i>
                            Modifier le profil
                        </h2>
                        
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   value="<?= htmlspecialchars($userData['nom']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" class="form-control" 
                                   value="<?= htmlspecialchars($userData['prenom']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($userData['email']) ?>" required>
                        </div>

                        <h2 class="section-title" style="margin-top: 2rem;">
                            <i class="fas fa-lock"></i>
                            Changer le mot de passe
                        </h2>
                        
                        <div class="form-group password-toggle">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Laissez vide si pas de changement">
                            <i class="fas fa-eye" onclick="togglePassword('current_password')"></i>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="new_password">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Laissez vide si pas de changement">
                            <i class="fas fa-eye" onclick="togglePassword('new_password')"></i>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Laissez vide si pas de changement">
                            <i class="fas fa-eye" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        
                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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

        // Fonction pour basculer la visibilité du mot de passe
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.animation = 'slideOut 0.5s ease-out';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>