<?php




require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';



// Réinitialisez l'objet utilisateur si nécessaire

if (!isset($_SESSION['user'])) {
    session_destroy();
    header("Location: form.php");
    exit;
}

// Gestion de la sérialisation
if (is_string($_SESSION['user'])) {
    $_SESSION['user'] = unserialize($_SESSION['user']);
}

// Gestion des objets incomplets
if ($_SESSION['user'] instanceof __PHP_Incomplete_Class) {
    $userData = serialize($_SESSION['user']);
    $_SESSION['user'] = unserialize($userData);
    
    if ($_SESSION['user'] instanceof __PHP_Incomplete_Class) {
        session_destroy();
        header("Location: form.php");
        exit;
    }
}

// Vérification finale du type d'utilisateur
if (!($_SESSION['user'] instanceof Utilisateur || $_SESSION['user'] instanceof Administrateur)) {
    session_destroy();
    header("Location: form.php");
    exit;
}

$user = $_SESSION['user'];
// ... rest of your existing dashboard.php code ...
if (!$user || !is_object($user)) {
    session_destroy();
    header("Location: form.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhosRoom</title>
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

        /* Sidebar */
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
            gap: 1rem;
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
            border: 2px solid var(--gray);
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
                padding: 2rem 1rem;
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
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 1.25rem;
            }
        }
        /* Styles pour le toggle de la sidebar */
.sidebar-toggle {
    position: fixed;
    left: 280px;
    top: 20px;
    z-index: 1000;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: none;
    justify-content: center;
    align-items: center;
}

.sidebar-toggle:hover {
    background: var(--primary-dark);
}

.sidebar.collapsed {
    transform: translateX(-100%);
    position: fixed;
}

.app-container.sidebar-collapsed {
    grid-template-columns: 1fr;
}

@media (max-width: 1024px) {
    .sidebar-toggle {
        display: flex;
    }
    
    .sidebar:not(.collapsed) {
        position: fixed;
        z-index: 100;
    }
}
    </style>
</head>
<body>
   

   <div class="app-container">
      <!-- Sidebar -->
<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-door-open"></i>
        PhosRoom
    </div>

    <?php if (!($user instanceof Administrateur)): ?>
        <!-- Menu pour les utilisateurs normaux -->
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="new_reservation.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'new_reservation.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-plus"></i>
                <span>Nouvelle réservation</span>
            </a>
            <a href="my_reservations.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'my_reservations.php' ? 'active' : '' ?>">
                <i class="fas fa-list"></i>
                <span>Mes réservations</span>
            </a>
            <a href="salles.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'salles.php' ? 'active' : '' ?>">
                <i class="fas fa-door-open"></i>
                <span>Les salles</span>
            </a>
            <a href="profile.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i>
                <span>Mon profil</span>
            </a>
        </nav>
    <?php else: ?>
        
        <!-- Menu spécifique pour l'administrateur -->
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Tableau de bord</span>
            </a>
            
           
            
            <!-- Section Rapports -->
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--gray);">
                <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.5rem; padding-left: 1rem;">RAPPORTS</p>
                
                
                <a href="reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'occupation_report.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Rapport</span>
                </a>
                <a href="reservation_history.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reservation_history.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Historique complet</span>
                </a>
            </div>
            
            <!-- Section Profil -->
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--gray);">
                <a href="profile.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Mon profil</span>
                </a>
            </div>
        </nav>
    <?php endif; ?>
</aside>

   
              
       

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle du thème
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

        // Toggle de la sidebar
        const sidebarToggle = document.createElement('button');
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        sidebarToggle.style.position = 'fixed';
        sidebarToggle.style.left = '280px';
        sidebarToggle.style.top = '20px';
        sidebarToggle.style.zIndex = '1000';
        sidebarToggle.style.background = 'var(--primary)';
        sidebarToggle.style.color = 'white';
        sidebarToggle.style.border = 'none';
        sidebarToggle.style.borderRadius = '50%';
        sidebarToggle.style.width = '40px';
        sidebarToggle.style.height = '40px';
        sidebarToggle.style.cursor = 'pointer';
        sidebarToggle.style.transition = 'all 0.3s ease';
        sidebarToggle.style.display = 'none'; // Caché par défaut sur grand écran
        
        document.body.appendChild(sidebarToggle);

        const sidebar = document.querySelector('.sidebar');
        const appContainer = document.querySelector('.app-container');

        // Fonction pour basculer la sidebar
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            appContainer.classList.toggle('sidebar-collapsed');
            
            // Sauvegarder l'état dans localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Déplacer le bouton
            if (isCollapsed) {
                sidebarToggle.style.left = '0';
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                sidebarToggle.style.left = '280px';
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }

        sidebarToggle.addEventListener('click', toggleSidebar);

        // Vérifier l'état sauvegardé
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            appContainer.classList.add('sidebar-collapsed');
            sidebarToggle.style.left = '0';
            sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
        }

        // Afficher/masquer le bouton en fonction de la taille de l'écran
        function handleResize() {
            if (window.innerWidth <= 1024) {
                sidebarToggle.style.display = 'block';
                sidebar.classList.add('collapsed');
                appContainer.classList.add('sidebar-collapsed');
                sidebarToggle.style.left = '0';
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                sidebarToggle.style.display = 'none';
                sidebar.classList.remove('collapsed');
                appContainer.classList.remove('sidebar-collapsed');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Appel initial
    });
</script>