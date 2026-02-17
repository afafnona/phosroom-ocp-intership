<?php
session_start();
require_once __DIR__ . '/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple access check
if (!isset($_SESSION['user'])) {
    header("Location: form.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    error_log("Update Reservation: ID=$reservation_id, Action=$action");
    
    if ($reservation_id <= 0) {
        header('Location: reservation_detail.php?id=' . $reservation_id . '&error=invalid_id');
        exit;
    }

    try {
        // Check if reservation exists
        $check_stmt = $pdo->prepare("SELECT id, statut FROM reservation WHERE id = ?");
        $check_stmt->execute([$reservation_id]);
        $reservation = $check_stmt->fetch();
        
        if (!$reservation) {
            header('Location: reservation_detail.php?id=' . $reservation_id . '&error=not_found');
            exit;
        }
        
        error_log("Current status: " . $reservation['statut']);
        
        // Use EXACT enum values from your database
        switch($action) {
            case 'confirm':
                $new_status = 'Confirmée'; // With 'e' at the end
                $update_stmt = $pdo->prepare("UPDATE reservation SET statut = ? WHERE id = ?");
                $result = $update_stmt->execute([$new_status, $reservation_id]);
                $affected_rows = $update_stmt->rowCount();
                error_log("Confirm result: success=$result, affected_rows=$affected_rows, new_status=$new_status");
                
                if ($result && $affected_rows > 0) {
                    header('Location: reservation_detail.php?id=' . $reservation_id . '&success=confirmed');
                } else {
                    header('Location: reservation_detail.php?id=' . $reservation_id . '&error=update_failed');
                }
                break;
                
            case 'cancel':
                $new_status = 'Annulée'; // With 'e' at the end
                $update_stmt = $pdo->prepare("UPDATE reservation SET statut = ? WHERE id = ?");
                $result = $update_stmt->execute([$new_status, $reservation_id]);
                $affected_rows = $update_stmt->rowCount();
                error_log("Cancel result: success=$result, affected_rows=$affected_rows, new_status=$new_status");
                
                if ($result && $affected_rows > 0) {
                    header('Location: reservation_detail.php?id=' . $reservation_id . '&success=cancelled');
                } else {
                    header('Location: reservation_detail.php?id=' . $reservation_id . '&error=update_failed');
                }
                break;
                
            case 'delete':
                $delete_stmt = $pdo->prepare("DELETE FROM reservation WHERE id = ?");
                $result = $delete_stmt->execute([$reservation_id]);
                $affected_rows = $delete_stmt->rowCount();
                error_log("Delete result: success=$result, affected_rows=$affected_rows");
                
                if ($result && $affected_rows > 0) {
                    header('Location: admin_reservations.php?success=deleted');
                } else {
                    header('Location: reservation_detail.php?id=' . $reservation_id . '&error=delete_failed');
                }
                break;
                
            default:
                header('Location: reservation_detail.php?id=' . $reservation_id . '&error=invalid_action');
        }
        
    } catch (PDOException $e) {
        error_log("Database error in update_reservation: " . $e->getMessage());
        header('Location: reservation_detail.php?id=' . $reservation_id . '&error=database_error&message=' . urlencode($e->getMessage()));
    }
    
} else {
    header('Location: admin_reservations.php');
}
exit;
?>