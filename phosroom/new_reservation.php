<?php
session_start();
ob_start(); 
require_once __DIR__ . '/db.php'; 
require_once __DIR__ . '/classes.php';
require_once __DIR__ . '/sidebar.php';

// Session handling and user verification
if (isset($_SESSION['user'])) {
    if (is_string($_SESSION['user'])) {
        $_SESSION['user'] = unserialize($_SESSION['user']);
    }
    
    if ($_SESSION['user'] instanceof __PHP_Incomplete_Class && isset($_SESSION['user_class'])) {
        $user = unserialize(serialize($_SESSION['user']));
        if (method_exists($user, '__wakeup')) {
            $user->__wakeup();
        }
        $_SESSION['user'] = $user;
    }
}

if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof Utilisateur)) {
    session_destroy();
    header('Location: form.php');
    exit;
}

$user = $_SESSION['user'];
$userId = $user->getId();

try {
    // Get available rooms
    $salles = $pdo->query("SELECT * FROM salles WHERE statut = 'disponible'")->fetchAll(PDO::FETCH_ASSOC);

    // Get trainers
    $formateurs = $pdo->query("
        SELECT f.id, u.nom, u.prenom, f.specialite 
        FROM formateur f 
        JOIN utilisateurs u ON f.utilisateur_id = u.id
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get available equipment
    $equipements = $pdo->query("SELECT * FROM equipement WHERE (salle_id IS NULL OR salle_id = '') AND quantite > 0")->fetchAll(PDO::FETCH_ASSOC);

    // Form processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $salleId = filter_input(INPUT_POST, 'salle', FILTER_VALIDATE_INT);
        $formateurId = filter_input(INPUT_POST, 'formateur', FILTER_VALIDATE_INT);
        $dateDebut = htmlspecialchars(trim($_POST['date_debut'] ?? ''), ENT_QUOTES, 'UTF-8');
        $dateFin = htmlspecialchars(trim($_POST['date_fin'] ?? ''), ENT_QUOTES, 'UTF-8');
        $heureDebut = htmlspecialchars(trim($_POST['heure_debut'] ?? ''), ENT_QUOTES, 'UTF-8');
        $heureFin = htmlspecialchars(trim($_POST['heure_fin'] ?? ''), ENT_QUOTES, 'UTF-8');
        $theme = htmlspecialchars(trim($_POST['theme'] ?? ''), ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars(trim($_POST['type'] ?? ''), ENT_QUOTES, 'UTF-8');
        $equipementsSelectionnes = $_POST['equipements'] ?? [];

        // Basic validation
        if (empty($salleId) || empty($formateurId) || empty($dateDebut) || empty($dateFin) ||
            empty($heureDebut) || empty($heureFin) || empty($theme) || empty($type)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis.");
        }

        // Validate dates
        $fullDateDebut = "$dateDebut $heureDebut";
        $fullDateFin = "$dateFin $heureFin";
        
        if (strtotime($fullDateDebut) >= strtotime($fullDateFin)) {
            throw new Exception("La date de fin doit être postérieure à la date de début.");
        }

        // Check room availability using Salle class
        $salle = Salle::getById($salleId);
        if (!$salle) {
            throw new Exception("Salle non trouvée.");
        }

        // Enhanced availability check
        $disponibilite = $salle->verifierDisponibilite($fullDateDebut, $fullDateFin);
        if (!$disponibilite['disponible']) {
            throw new Exception($disponibilite['message']);
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Create reservation first
            $stmt = $pdo->prepare("
                INSERT INTO reservation (nom, date_demande, date_debut, date_fin, statut, utilisateur_id, salle_id, formateur_id)
                VALUES (?, NOW(), ?, ?, 'En attente', ?, ?, ?)
            ");
            $stmt->execute([
                $theme,
                $fullDateDebut,
                $fullDateFin,
                $userId,
                $salleId,
                $formateurId
            ]);
            $reservationId = $pdo->lastInsertId();

            // Create event linked to reservation
            $stmt = $pdo->prepare("
                INSERT INTO evenement (type, theme, date_debut, date_fin, heure_debut, heure_fin, statut, formateur_id, salle_id, reservation_id)
                VALUES (?, ?, ?, ?, ?, ?, 'Planifié', ?, ?, ?)
            ");
            $stmt->execute([
                $type,
                $theme,
                $dateDebut,
                $dateFin,
                $heureDebut,
                $heureFin,
                $formateurId,
                $salleId,
                $reservationId
            ]);

            // Assign selected equipment
            if (!empty($equipementsSelectionnes)) {
                foreach ($equipementsSelectionnes as $equipementId) {
                    $stmt = $pdo->prepare("
                        UPDATE equipement 
                        SET salle_id = ?, quantite = quantite - 1 
                        WHERE id = ? AND quantite > 0
                    ");
                    $stmt->execute([$salleId, $equipementId]);
                }
            }

            $pdo->commit();

            $_SESSION['message'] = [
                'type' => 'success', 
                'text' => 'Réservation créée avec succès et en attente de confirmation'
            ];
            header('Location: dashboard.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de la création de la réservation: " . $e->getMessage());
        }
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorMessage = "Erreur de base de données : " . $e->getMessage();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Réservation - PhosRoom</title>
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
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        body.dark-mode {
            --primary: #5B8FB9;
            --light: var(--dark-bg);
            --text: var(--dark-text);
            --text-light: var(--dark-text-light);
            --white: var(--dark-surface);
            --gray: var(--dark-gray);
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
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        .app-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .main-content {
            padding: 2.5rem;
            background-color: var(--light);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
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
            color: var(--accent);
        }

        
      

        .reservation-form {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
        }

        .dark-mode .reservation-form {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

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
            color: var(--accent);
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
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .date-time-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .equipements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .equipement-card {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border: 1px solid var(--gray);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            background: var(--white);
        }

        .dark-mode .equipement-card {
            background: var(--dark-surface);
            border-color: var(--dark-gray);
        }

        .equipement-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .equipement-card input[type="checkbox"] {
            margin-top: 0.25rem;
        }

        .equipement-info {
            flex: 1;
        }

        .equipement-name {
            font-weight: 500;
            display: block;
        }

        .stock-info {
            font-size: 0.85rem;
            color: var(--success);
        }

        .stock-warning {
            font-size: 0.85rem;
            color: var(--error);
        }

        .equipement-desc {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        .salle-info {
            background: var(--light);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray);
        }

        .dark-mode .salle-info {
            background: var(--dark-bg);
            border-color: var(--dark-gray);
        }

        .salle-info h3 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dark-mode .salle-info h3 {
            color: var(--accent);
        }

        .salle-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-item i {
            color: var(--accent);
            width: 20px;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .dark-mode .btn-outline {
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
        }

        .dark-mode .btn-outline:hover {
            background: var(--accent);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            font-weight: 500;
            background: var(--light);
            border: 1px solid var(--gray);
        }

        .message.error {
            background-color: rgba(141, 90, 90, 0.1);
            color: var(--error);
            border-color: var(--error);
        }

        .availability-status {
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-weight: 500;
            margin-top: 0.5rem;
            display: none;
        }

        .availability-available {
            background-color: rgba(90, 141, 90, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .availability-unavailable {
            background-color: rgba(141, 90, 90, 0.1);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .theme-toggle-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
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
            border-radius: 30px;
            transition: var(--transition);
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
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        @media (max-width: 1024px) {
            .app-container {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .date-time-group {
                grid-template-columns: 1fr;
            }
            
            .salle-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
        <main class="main-content">
            <header class="header">
                <h1 class="section-title">
                    <i class="fas fa-calendar-plus"></i>
                    Nouvelle Réservation
                </h1>
                <div class="user-nav">
                    <div class="user-avatar">
                        
                    </div>
                </div>
            </header>

            <?php if (isset($errorMessage)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="reservation-form" id="reservationForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="theme">Thème de la réservation *</label>
                        <input type="text" id="theme" name="theme" class="form-control" required 
                               placeholder="Ex: Formation PHP avancé">
                    </div>

                    <div class="form-group">
                        <label for="type">Type d'événement *</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="Formation">Formation</option>
                            <option value="Réunion">Réunion</option>
                            <option value="Séminaire">Séminaire</option>
                            <option value="Présentation">Présentation</option>
                            <option value="Conférence">Conférence</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="salle">Salle *</label>
                        <select id="salle" name="salle" class="form-control" required>
                            <option value="">Sélectionnez une salle</option>
                            <?php foreach ($salles as $salle): ?>
                                <option value="<?= $salle['id'] ?>" 
                                        data-capacite="<?= $salle['capacite'] ?>"
                                        data-localisation="<?= htmlspecialchars($salle['localisation']) ?>">
                                    <?= htmlspecialchars($salle['nom']) ?> (Capacité: <?= $salle['capacite'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="availability-status" class="availability-status"></div>
                    </div>

                    <div class="form-group">
                        <label for="formateur">Formateur *</label>
                        <select id="formateur" name="formateur" class="form-control" required>
                            <option value="">Sélectionnez un formateur</option>
                            <?php foreach ($formateurs as $formateur): ?>
                                <option value="<?= $formateur['id'] ?>">
                                    <?= htmlspecialchars($formateur['prenom'] . ' ' . $formateur['nom']) ?> - <?= htmlspecialchars($formateur['specialite']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dates *</label>
                        <div class="date-time-group">
                            <div>
                                <label for="date_debut">Date de début</label>
                                <input type="date" id="date_debut" name="date_debut" class="form-control" required
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label for="date_fin">Date de fin</label>
                                <input type="date" id="date_fin" name="date_fin" class="form-control" required
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Heures *</label>
                        <div class="date-time-group">
                            <div>
                                <label for="heure_debut">Heure de début</label>
                                <input type="time" id="heure_debut" name="heure_debut" class="form-control" required>
                            </div>
                            <div>
                                <label for="heure_fin">Heure de fin</label>
                                <input type="time" id="heure_fin" name="heure_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="salle-info" id="salle-info" style="display: none;">
                    <h3><i class="fas fa-info-circle"></i> Informations sur la salle</h3>
                    <div class="salle-details">
                        <div class="detail-item">
                            <i class="fas fa-users"></i>
                            <span>Capacité: <span id="info-capacite">0</span> personnes</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Localisation: <span id="info-localisation">Non spécifiée</span></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-door-open"></i>
                            <span>Statut: <span id="info-statut">Disponible</span></span>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 2rem; text-align: right;">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="fas fa-save"></i> Enregistrer la réservation
                    </button>
                </div>
            </form>
        </main>
    </div>

    

    <script>
        // Dark mode functionality
        const themeToggle = document.getElementById('theme-toggle');
        const currentTheme = localStorage.getItem('theme');
        
        if (currentTheme === 'dark') {
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

        // Room information display
        document.getElementById('salle').addEventListener('change', function() {
            const salleInfo = document.getElementById('salle-info');
            const selectedOption = this.options[this.selectedIndex];
            const statusDiv = document.getElementById('availability-status');
            
            if (this.value) {
                salleInfo.style.display = 'block';
                document.getElementById('info-capacite').textContent = selectedOption.dataset.capacite;
                document.getElementById('info-localisation').textContent = selectedOption.dataset.localisation;
                
                // Clear previous status
                statusDiv.style.display = 'none';
                statusDiv.className = 'availability-status';
            } else {
                salleInfo.style.display = 'none';
                statusDiv.style.display = 'none';
            }
        });

        // Date validation
        document.getElementById('date_debut').addEventListener('change', function() {
            const dateFin = document.getElementById('date_fin');
            if (dateFin.value && new Date(dateFin.value) < new Date(this.value)) {
                dateFin.value = this.value;
            }
            dateFin.min = this.value;
            checkAvailability();
        });

        document.getElementById('date_fin').addEventListener('change', checkAvailability);
        document.getElementById('heure_debut').addEventListener('change', checkAvailability);
        document.getElementById('heure_fin').addEventListener('change', checkAvailability);

        // Real-time availability check
        async function checkAvailability() {
            const salleId = document.getElementById('salle').value;
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;
            const heureDebut = document.getElementById('heure_debut').value;
            const heureFin = document.getElementById('heure_fin').value;
            const statusDiv = document.getElementById('availability-status');
            const submitBtn = document.getElementById('submit-btn');

            if (salleId && dateDebut && dateFin && heureDebut && heureFin) {
                try {
                    const response = await fetch('check_availability.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `salle_id=${salleId}&date_debut=${dateDebut}&date_fin=${dateFin}&heure_debut=${heureDebut}&heure_fin=${heureFin}`
                    });
                    
                    const data = await response.json();
                    
                    statusDiv.style.display = 'block';
                    statusDiv.textContent = data.message;
                    
                    if (data.disponible) {
                        statusDiv.className = 'availability-status availability-available';
                        submitBtn.disabled = false;
                    } else {
                        statusDiv.className = 'availability-status availability-unavailable';
                        submitBtn.disabled = true;
                    }
                } catch (error) {
                    console.error('Error checking availability:', error);
                }
            }
        }

        // Form submission validation
        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            const salleSelect = document.getElementById('salle');
            if (!salleSelect.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une salle.');
                return;
            }
            
            const statusDiv = document.getElementById('availability-status');
            if (statusDiv.style.display === 'block' && statusDiv.classList.contains('availability-unavailable')) {
                e.preventDefault();
                alert('Impossible de soumettre la réservation. La salle n\'est pas disponible.');
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>