<?php
session_start();
require_once __DIR__ . '/db.php';

// Simple access check
if (!isset($_SESSION['user'])) {
    header("Location: form.php");
    exit;
}

// Get reservation ID from URL
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservation_id <= 0) {
    die("ID de réservation invalide");
}

// Fetch reservation details with proper error handling
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
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        die("Réservation non trouvée dans la base de données");
    }
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

// Handle success/error messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Réservation #<?= $reservation_id ?> - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Poppins", sans-serif; background: #f8f9fa; color: #2D3748; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        h1 { color: #1D2F3F; font-size: 2rem; }
        .btn { padding: 0.8rem 1.5rem; border-radius: 50px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; border: none; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-primary { background: #3A5A78; color: white; }
        .btn-success { background: #5A8D5A; color: white; }
        .btn-danger { background: #8D5A5A; color: white; }
        .btn-warning { background: #8D7B5A; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .section { margin-bottom: 2rem; padding: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid #E2E8F0; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .info-item { display: flex; flex-direction: column; gap: 0.5rem; }
        .info-label { font-weight: 500; color: #718096; font-size: 0.9rem; }
        .action-buttons { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
        .alert { padding: 1rem 1.5rem; border-radius: 16px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; }
        .alert-success { background: rgba(90,141,90,0.2); color: #5A8D5A; border: 1px solid #5A8D5A; }
        .alert-error { background: rgba(141,90,90,0.2); color: #8D5A5A; border: 1px solid #8D5A5A; }
        .debug-info { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #3A5A78; font-family: monospace; font-size: 0.9rem; }
        .status-badge { padding: 0.35rem 0.8rem; border-radius: 50px; font-size: 0.8rem; font-weight: 500; display: inline-block; }
        .status-en_attente { background: rgba(141,123,90,0.2); color: #8D7B5A; border: 1px solid #8D7B5A; }
        .status-confirmé { background: rgba(90,141,90,0.2); color: #5A8D5A; border: 1px solid #5A8D5A; }
        .status-annulé { background: rgba(141,90,90,0.2); color: #8D5A5A; border: 1px solid #8D5A5A; }
        .status-en_attente { background: rgba(141,123,90,0.2); color: #8D7B5A; border: 1px solid #8D7B5A; }
.status-confirmée { background: rgba(90,141,90,0.2); color: #5A8D5A; border: 1px solid #5A8D5A; }
.status-annulée { background: rgba(141,90,90,0.2); color: #8D5A5A; border: 1px solid #8D5A5A; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Détails de la Réservation </h1>
            <a href="admin_reservations.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour aux réservations
            </a>
        </div>

        

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                switch($success) {
                    case 'confirmed': echo 'Réservation confirmée avec succès'; break;
                    case 'cancelled': echo 'Réservation annulée avec succès'; break;
                    case 'deleted': echo 'Réservation supprimée avec succès'; break;
                    default: echo 'Action réussie';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Erreur: 
                <?php 
                switch($error) {
                    case 'invalid_id': echo 'ID de réservation invalide'; break;
                    case 'not_found': echo 'Réservation non trouvée'; break;
                    case 'database': echo 'Erreur de base de données'; break;
                    case 'update_failed': echo 'Échec de la mise à jour'; break;
                    case 'delete_failed': echo 'Échec de la suppression'; break;
                    default: echo $error;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Informations Réservation -->
        <div class="section">
            <h2><i class="fas fa-info-circle"></i> Informations Réservation</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nom de la réservation</span>
                    <div><?= htmlspecialchars($reservation['nom'] ?? 'Non disponible') ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut</span>
                    <span class="status-badge status-<?= str_replace(' ', '_', strtolower($reservation['statut'])) ?>">
                        <?= ucfirst($reservation['statut']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Informations Utilisateur -->
        <div class="section">
            <h2><i class="fas fa-user"></i> Informations Utilisateur</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nom complet</span>
                    <div><?= htmlspecialchars($reservation['user_name'] ?? 'Non disponible') ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <div><?= htmlspecialchars($reservation['user_email'] ?? 'Non disponible') ?></div>
                </div>
            </div>
        </div>

        <!-- Informations Salle -->
        <div class="section">
            <h2><i class="fas fa-door-open"></i> Informations Salle</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Salle</span>
                    <div><?= htmlspecialchars($reservation['salle_nom'] ?? 'Non disponible') ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Localisation</span>
                    <div><?= htmlspecialchars($reservation['localisation'] ?? 'Non disponible') ?></div>
                </div>
            </div>
        </div>

        <!-- Dates et Horaires -->
        <div class="section">
            <h2><i class="fas fa-calendar"></i> Dates et Horaires</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Date de demande</span>
                    <div><?= date('d/m/Y', strtotime($reservation['date_demande'])) ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Date de début</span>
                    <div><?= ($reservation['date_debut'] && $reservation['date_debut'] != '0000-00-00') ? date('d/m/Y', strtotime($reservation['date_debut'])) : 'Non définie' ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Date de fin</span>
                    <div><?= date('d/m/Y', strtotime($reservation['date_fin'])) ?></div>
                </div>
            </div>
        </div>

       
       <!-- Statut et Actions -->
<div class="section">
    <h2><i class="fas fa-cog"></i> Gestion de la Réservation</h2>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Statut actuel</span>
            <span class="status-badge status-<?= str_replace([' ', 'é'], ['_', 'e'], strtolower($reservation['statut'])) ?>">
                <?= $reservation['statut'] ?>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">ID Réservation</span>
            <div>#<?= $reservation['id'] ?></div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="action-buttons">
        <?php 
        $currentStatus = $reservation['statut']; // Use exact value from database
        $isPending = ($currentStatus === 'En attente');
        $isCancelled = ($currentStatus === 'Annulée');
        $isConfirmed = ($currentStatus === 'Confirmée');
        ?>
        
        <?php if ($isPending): ?>
            <form method="POST" action="update_reservation.php" style="display: inline;">
                <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="btn btn-success" onclick="return confirm('Confirmer cette réservation?')">
                    <i class="fas fa-check"></i> Confirmer la réservation
                </button>
            </form>
        <?php endif; ?>
        
        <?php if (!$isCancelled && !$isConfirmed): ?>
            <form method="POST" action="update_reservation.php" style="display: inline;">
                <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?')">
                    <i class="fas fa-times"></i> Annuler la réservation
                </button>
            </form>
        <?php endif; ?>
        
        <form method="POST" action="update_reservation.php" style="display: inline;">
            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette réservation? Cette action est irréversible.')">
                <i class="fas fa-trash"></i> Supprimer la réservation
            </button>
        </form>
    </div>
</div>
    <script>
        // Add loading states to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                button.disabled = true;
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 5000);
            });
        });
    </script>
</body>
</html>