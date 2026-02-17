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

// Vérifier et initialiser la date d'inscription
$query = "SHOW COLUMNS FROM utilisateurs LIKE 'date_inscription'";
$stmt = $pdo->query($query);
$columnExists = $stmt->fetch();

if (!$columnExists) {
    // Si la colonne n'existe pas, on utilise une valeur par défaut
    $dateColumn = "NOW() as date_inscription";
} else {
    $dateColumn = "COALESCE(date_inscription, created_at, NOW()) as date_inscription";
}

// Actions sur les utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo->beginTransaction();
            
            $userId = $_POST['user_id'];
            
            switch ($_POST['action']) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                    $stmt->execute([$userId]);
                    break;
                    
                case 'promote':
                    $role = $_POST['role'];
                    
                    // D'abord supprimer tous les rôles existants
                    $pdo->prepare("DELETE FROM administrateur WHERE utilisateur_id = ?")->execute([$userId]);
                    $pdo->prepare("DELETE FROM formateur WHERE utilisateur_id = ?")->execute([$userId]);
                    $pdo->prepare("DELETE FROM participants WHERE utilisateur_id = ?")->execute([$userId]);
                    
                    if ($role === 'admin') {
                        $stmt = $pdo->prepare("INSERT INTO administrateur (utilisateur_id, departement) VALUES (?, 'Général')");
                        $stmt->execute([$userId]);
                    } elseif ($role === 'formateur') {
                        $stmt = $pdo->prepare("INSERT INTO formateur (utilisateur_id, specialite) VALUES (?, 'Formation')");
                        $stmt->execute([$userId]);
                    } elseif ($role === 'participant') {
                        $stmt = $pdo->prepare("INSERT INTO participants (utilisateur_id, service) VALUES (?, 'Service')");
                        $stmt->execute([$userId]);
                    }
                    break;
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Action réalisée avec succès";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de l'opération: " . $e->getMessage();
        }
    }
}

// Requête corrigée pour gérer l'absence de colonne date_inscription
$query = "
    SELECT u.*, 
           CASE 
               WHEN a.id IS NOT NULL THEN 'Administrateur'
               WHEN f.id IS NOT NULL THEN 'Formateur'
               WHEN p.id IS NOT NULL THEN 'Participant'
               ELSE 'Utilisateur'
           END as role,
           $dateColumn
    FROM utilisateurs u
    LEFT JOIN administrateur a ON u.id = a.utilisateur_id
    LEFT JOIN formateur f ON u.id = f.utilisateur_id
    LEFT JOIN participants p ON u.id = p.utilisateur_id
    ORDER BY u.nom, u.prenom
";

$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - PhosRoom</title>
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
            max-width: 1000px;
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

        .search-box {
            margin-bottom: 2rem;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 1.2rem;
            padding-left: 3rem;
            border: 1px solid var(--gray);
            border-radius: 50px;
            font-size: 1rem;
            background: var(--white);
            color: var(--text);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .dark-mode .search-box input {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
            color: var(--dark-text);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 90, 120, 0.2);
        }

        .dark-mode .search-box input:focus {
            border-color: var(--dark-accent);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .user-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .user-item {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .dark-mode .user-item {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .user-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .dark-mode .user-item:hover {
            border-color: var(--dark-accent);
        }

        .user-email {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark-mode .user-email {
            color: var(--dark-accent);
        }

        .user-email i {
            color: var(--primary);
        }

        .dark-mode .user-email i {
            color: var(--dark-accent);
        }

        .user-date {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .user-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .change-role {
            background: var(--primary);
            color: var(--white);
        }

        .change-role:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .delete-btn {
            background: var(--white);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .dark-mode .delete-btn {
            background: var(--dark-surface);
        }

        .delete-btn:hover {
            background: var(--error);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .role-selector {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray);
            box-shadow: var(--shadow-sm);
        }

        .dark-mode .role-selector {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .role-option {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .role-option:hover {
            background: var(--gray);
            transform: translateX(5px);
        }

        .dark-mode .role-option:hover {
            background: var(--dark-gray);
        }

        .role-option i {
            width: 20px;
            text-align: center;
            color: var(--primary);
        }

        .dark-mode .role-option i {
            color: var(--dark-accent);
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

        .
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .user-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    

    <div class="container">
        <h1><i class="fas fa-users-cog"></i> Gestion des utilisateurs</h1>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Rechercher un utilisateur...">
        </div>
        
        <ul class="user-list">
            <?php foreach ($users as $user): ?>
            <li class="user-item">
                <div class="user-email">
                    <i class="fas fa-envelope"></i>
                    <?= htmlspecialchars($user['email']) ?>
                </div>
                <div class="user-date">
                    <i class="fas fa-calendar-alt"></i>
                    Inscrit le <?= date('d/m/Y', strtotime($user['date_inscription'])) ?>
                </div>
                
                <div class="user-actions">
                    <button class="action-btn change-role" 
                            onclick="document.getElementById('role-options-<?= $user['id'] ?>').style.display='block'">
                        <i class="fas fa-user-tag"></i> Changer rôle
                    </button>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" name="action" value="delete" 
                                class="action-btn delete-btn"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                            <i class="fas fa-trash-alt"></i> Supprimer
                        </button>
                    </form>
                </div>
                
                <div id="role-options-<?= $user['id'] ?>" class="role-selector">
    <form method="POST">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <input type="hidden" name="action" value="promote">
        
        <div class="role-option" onclick="this.parentNode.submit()">
            <i class="fas fa-user-shield"></i>
            <input type="hidden" name="role" value="admin">
            Administrateur
        </div>
        <div class="role-option" onclick="this.parentNode.submit()">
            <i class="fas fa-chalkboard-teacher"></i>
            <input type="hidden" name="role" value="formateur">
            Formateur
        </div>
        <div class="role-option" onclick="this.parentNode.submit()">
            <i class="fas fa-user-graduate"></i>
            <input type="hidden" name="role" value="participant">
            Participant
        </div>
    </form>
</div>
            </li>
            <?php endforeach; ?>
        </ul>
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

        // Fermer les menus de rôle quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('change-role') && 
                !e.target.closest('.role-selector')) {
                document.querySelectorAll('.role-selector').forEach(el => {
                    el.style.display = 'none';
                });
            }
        });

        // Recherche en temps réel
        const searchInput = document.querySelector('.search-box input');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.user-item').forEach(user => {
                const email = user.querySelector('.user-email').textContent.toLowerCase();
                if (email.includes(searchTerm)) {
                    user.style.display = 'block';
                } else {
                    user.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>