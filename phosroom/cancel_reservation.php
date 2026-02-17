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
    // Vérifier que la réservation appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM reservation WHERE id = ? AND utilisateur_id = ? AND statut = 'En attente'");
    $stmt->execute([$reservationId, $user->getId()]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        $_SESSION['error_message'] = "Réservation non trouvée ou déjà confirmée/annulée";
        header('Location: my_reservations.php');
        exit;
    }

    // Annuler la réservation
    $stmt = $pdo->prepare("UPDATE reservation SET statut = 'Annulé' WHERE id = ?");
    $stmt->execute([$reservationId]);

    // Envoyer une notification
    $notificationMessage = "Votre réservation #$reservationId a été annulée avec succès.";
    $stmt = $pdo->prepare("INSERT INTO notifications (type, message, date_envoi, destinataire_id) VALUES (?, ?, NOW(), ?)");
    $stmt->execute(['annulation', $notificationMessage, $user->getId()]);

    $_SESSION['success_message'] = "La réservation a été annulée avec succès";
    header('Location: my_reservations.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de l'annulation : " . $e->getMessage();
    header('Location: my_reservations.php');
    exit;
}