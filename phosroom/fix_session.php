<?php
// fix_session.php - Script to fix session issues
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/classes.php';

echo "<h1>Fix Session Issues</h1>";

// Destroy current session if it has issues
if (isset($_SESSION['user']) && $_SESSION['user'] instanceof __PHP_Incomplete_Class) {
    echo "<p>Session corrompue détectée. Destruction de la session...</p>";
    session_destroy();
    echo "<p>Session détruite. Veuillez vous reconnecter.</p>";
    echo "<a href='form.php'>Se reconnecter</a>";
    exit;
}

// Test login
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch();
        
        if ($userData && password_verify($password, $userData['mot_de_passe'])) {
            // Check user type
            $stmtAdmin = $pdo->prepare("SELECT * FROM administrateur WHERE utilisateur_id = ?");
            $stmtAdmin->execute([$userData['id']]);
            $adminData = $stmtAdmin->fetch();
            
            if ($adminData) {
                $user = Administrateur::getById($userData['id']);
            } else {
                $stmtFormateur = $pdo->prepare("SELECT * FROM formateur WHERE utilisateur_id = ?");
                $stmtFormateur->execute([$userData['id']]);
                $formateurData = $stmtFormateur->fetch();
                
                if ($formateurData) {
                    $user = Formateur::getById($userData['id']);
                } else {
                    $user = Participant::getById($userData['id']);
                }
            }
            
            if ($user && !($user instanceof __PHP_Incomplete_Class)) {
                $_SESSION['user'] = $user;
                echo "<p style='color: green;'>✓ Connexion réussie! Type: " . get_class($user) . "</p>";
                echo "<p><a href='debug_export.php'>Tester l'export PDF</a></p>";
            } else {
                echo "<p style='color: red;'>✗ Erreur lors de la création de l'objet utilisateur</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Email ou mot de passe incorrect</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Erreur: " . $e->getMessage() . "</p>";
    }
}

// Show login form
echo "
<h2>Test de connexion</h2>
<form method='post'>
    <div>
        <label>Email:</label>
        <input type='email' name='email' value='admin@ocpgroup.ma' required>
    </div>
    <div>
        <label>Mot de passe:</label>
        <input type='password' name='password' value='admin123' required>
    </div>
    <button type='submit'>Se connecter</button>
</form>
";

// Show current session status
echo "
<h2>État de la session</h2>
<pre>";
var_dump($_SESSION);
echo "</pre>";
?>