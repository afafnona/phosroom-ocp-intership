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

// Actions sur les salles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo->beginTransaction();
            
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO salles (nom, capacite, localisation, statut) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['nom'],
                        $_POST['capacite'],
                        $_POST['localisation'],
                        $_POST['statut']
                    ]);
                    $_SESSION['success'] = "Salle ajoutée avec succès";
                    break;
                case 'update':
                    $stmt = $pdo->prepare("UPDATE salles SET nom = ?, capacite = ?, localisation = ?, statut = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nom'],
                        $_POST['capacite'],
                        $_POST['localisation'],
                        $_POST['statut'],
                        $_POST['id']
                    ]);
                    $_SESSION['success'] = "Salle mise à jour avec succès";
                    break;
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM salles WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = "Salle supprimée avec succès";
                    break;
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de l'opération: " . $e->getMessage();
        }
        header("Location: manage_rooms.php");
        exit;
    }
}

// Récupérer toutes les salles
$salles = $pdo->query("SELECT * FROM salles ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salles - PhosRoom</title>
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
            max-width: 1200px;
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .dark-mode .modal-content {
            background-color: var(--dark-surface);
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .close:hover {
            color: var(--primary-dark);
            transform: rotate(90deg);
        }

        .dark-mode .close:hover {
            color: var(--dark-accent);
        }

        /* Form styles */
        .form-group {
            margin-bottom: 1.5rem;
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

        /* Table styles */
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

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: rgba(58, 90, 120, 0.05);
        }

        .dark-mode .admin-table tr:hover td {
            background: rgba(91, 143, 185, 0.1);
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

        .status-disponible {
            background: rgba(90, 141, 90, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status-reserve {
            background: rgba(141, 123, 90, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status-en_maintenance {
            background: rgba(141, 90, 90, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
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
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
            
            .admin-table td.actions {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
            .form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}


        }
    </style>
</head>
<body>
    
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-door-open"></i> Gestion des salles</h1>
            <button id="addRoomBtn" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter une salle
            </button>
        </div>

        <!-- Modal pour ajouter/modifier une salle -->
<div id="roomModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Ajouter une nouvelle salle</h2>
        <form id="roomForm" method="POST">
            <input type="hidden" name="id" id="roomId">
            <input type="hidden" name="action" id="action" value="add">
            
            <div class="form-group">
                <label for="nom">Nom de la salle:</label>
                <input type="text" id="nom" name="nom" required>
            </div>
            
            <div class="form-group">
                <label for="capacite">Capacité:</label>
                <input type="number" id="capacite" name="capacite" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="localisation">Localisation:</label>
                <input type="text" id="localisation" name="localisation" required>
            </div>
            
            <div class="form-group">
                <label for="statut">Statut:</label>
                <select id="statut" name="statut" required>
                    <option value="disponible">Disponible</option>
                    <option value="reserve">Réservée</option>
                    <option value="en_maintenance">En maintenance</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-outline" onclick="modal.style.display='none'">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Capacité</th>
                        <th>Localisation</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salles as $salle): ?>
                    <tr>
                        <td data-label="ID"><?= $salle['id'] ?></td>
                        <td data-label="Nom"><?= htmlspecialchars($salle['nom']) ?></td>
                        <td data-label="Capacité"><?= $salle['capacite'] ?></td>
                        <td data-label="Localisation"><?= htmlspecialchars($salle['localisation']) ?></td>
                        <td data-label="Statut">
                            <span class="status-badge status-<?= $salle['statut'] ?>">
                                <?= ucfirst($salle['statut']) ?>
                            </span>
                        </td>
                        <td class="actions" data-label="Actions">
                            <button class="btn btn-sm edit-room" 
                                    data-id="<?= $salle['id'] ?>"
                                    data-nom="<?= htmlspecialchars($salle['nom']) ?>"
                                    data-capacite="<?= $salle['capacite'] ?>"
                                    data-localisation="<?= htmlspecialchars($salle['localisation']) ?>"
                                    data-statut="<?= $salle['statut'] ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $salle['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette salle?')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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

        // Gestion de la modal
        const modal = document.getElementById('roomModal');
        const addBtn = document.getElementById('addRoomBtn');
        const closeBtn = document.querySelector('.close');
        const form = document.getElementById('roomForm');
        
        addBtn.onclick = function() {
            document.getElementById('modalTitle').textContent = "Ajouter une nouvelle salle";
            document.getElementById('action').value = "add";
            document.getElementById('roomId').value = "";
            form.reset();
            modal.style.display = "block";
        }
        
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Édition d'une salle existante
        document.querySelectorAll('.edit-room').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalTitle').textContent = "Modifier la salle";
                document.getElementById('action').value = "update";
                document.getElementById('roomId').value = this.dataset.id;
                document.getElementById('nom').value = this.dataset.nom;
                document.getElementById('capacite').value = this.dataset.capacite;
                document.getElementById('localisation').value = this.dataset.localisation;
                document.getElementById('statut').value = this.dataset.statut;
                modal.style.display = "block";
            });
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