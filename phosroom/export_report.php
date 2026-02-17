<?php
// export_report.php - Fixed admin check
session_start();

// Start with basic output buffering
ob_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';

// Enhanced admin check
function isUserAdmin() {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    // Handle serialized objects
    if (is_string($_SESSION['user'])) {
        $_SESSION['user'] = unserialize($_SESSION['user']);
    }
    
    // Handle incomplete classes
    if ($_SESSION['user'] instanceof __PHP_Incomplete_Class) {
        return false;
    }
    
    // Check if it's an Administrateur object
    if ($_SESSION['user'] instanceof Administrateur) {
        return true;
    }
    
    // Additional check: verify in database
    if (isset($_SESSION['user']->id)) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM administrateur WHERE utilisateur_id = ?");
            $stmt->execute([$_SESSION['user']->id]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            error_log("Admin check error: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

// Check admin access


// Rest of your PDF export code continues here...
// [Keep your existing PDF generation code]

// Validate parameters
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("ID de rapport invalide");
}

$reportId = (int)$_GET['id'];

try {
    // Clear buffers before PDF output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Get report data
    $stmt = $pdo->prepare("
        SELECT r.*, CONCAT(u.prenom, ' ', u.nom) as admin_name 
        FROM rapport r
        JOIN administrateur a ON r.administrateur_id = a.id
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        WHERE r.id = ? LIMIT 1
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        throw new Exception("Rapport introuvable");
    }

    // Try DomPDF
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        $html = generateSimpleHTML($report);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream("rapport_phosroom_" . $reportId . ".pdf", [
            "Attachment" => true
        ]);
        exit;
    } else {
        // Fallback
        downloadAsHTML($report, $reportId);
    }

} catch (Exception $e) {
    // Clear any partial output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    die("Erreur: " . $e->getMessage());
}

function generateSimpleHTML($report) {
    $periodStart = date('d/m/Y', strtotime($report['date_debut_periode']));
    $periodEnd = date('d/m/Y', strtotime($report['date_fin_periode']));
    $generationDate = date('d/m/Y H:i', strtotime($report['date_generation']));
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Rapport PhosRoom</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Rapport PhosRoom - " . htmlspecialchars(ucfirst($report['type'])) . "</h1>
        </div>
        
        <table>
            <tr><th>Période:</th><td>$periodStart - $periodEnd</td></tr>
            <tr><th>Généré le:</th><td>$generationDate</td></tr>
            <tr><th>Généré par:</th><td>" . htmlspecialchars($report['admin_name']) . "</td></tr>
            <tr><th>Réservations:</th><td>" . $report['nb_reservations'] . "</td></tr>
            <tr><th>Heures totales:</th><td>" . $report['nb_heures'] . "h</td></tr>
        </table>
    </body>
    </html>";
}

function downloadAsHTML($report, $reportId) {
    $html = generateSimpleHTML($report);
    
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="rapport_' . $reportId . '.html"');
    echo $html;
    exit;
}
?>