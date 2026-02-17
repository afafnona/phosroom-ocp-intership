<?php
include 'db.php';
session_start();
ob_start();
require_once "classes.php";

$name = $email = $pass = $nom = $prenom = "";
$nameErr = $emailErr = $passErr = "";

function redirectMsg($type, $text, $show_signup = false) {
    $_SESSION['message'] = ['type' => $type, 'text' => $text];
    if ($show_signup) $_SESSION['show_signup'] = true;
    header('Location: form.php');
    exit;
}

if (isset($_POST['submit'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $pass = $_POST['password'] ?? '';

    if (!empty($email) && !empty($pass)) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && ($pass === $user['mot_de_passe'] || password_verify($pass, $user['mot_de_passe']))) {
            if ($user['role'] === 'administrateur') {
                $userObj = new Administrateur();
            } elseif ($user['role'] === 'formateur') {
                $userObj = new Formateur();
            } else {
                $userObj = new Participant();
            }
            
            $userObj->id = $user['id'];
            $userObj->nom = $user['nom'];
            $userObj->prenom = $user['prenom'];
            $userObj->email = $user['email'];
            
            $_SESSION['user'] = serialize($userObj);
            $_SESSION['user_id'] = $userObj->id;
            header('Location: dashboard.php');
            exit;
        } else {
            redirectMsg('error', 'Mot de passe incorrect.');
        }
    } else {
        redirectMsg('error', 'Veuillez remplir tous les champs.');
    }
}

if (isset($_POST['ok'])) {
    $nom = htmlspecialchars($_POST['signup_name'] ?? '');
    $prenom = htmlspecialchars($_POST['signup_prenom'] ?? '');
    $email = filter_var($_POST['signup_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $pass = $_POST['password'] ?? '';
    $cpass = $_POST['confirm_password'] ?? '';

    if ($pass !== $cpass) {
        redirectMsg('error', 'Les mots de passe ne correspondent pas.', true);
    }

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount()) {
        redirectMsg('error', 'Email déjà utilisé.', true);
    }

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    $insert = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)");
    $insert->execute([$nom, $prenom, $email, $hashed_pass]);

    redirectMsg('success', 'Compte créé avec succès ! Vous pouvez vous connecter.');
}

$participant = new Participant();
$_SESSION['user'] = serialize($participant);
$_SESSION['user_class'] = 'Participant';

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - PhosRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
    
</head>
<body>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?= $_SESSION['message']['type'] ?>">
            <?= $_SESSION['message']['text'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="theme-toggle-container">
    <span class="theme-label active">Clair</span>
    <label class="theme-toggle">
        <input type="checkbox" id="theme-toggle">
        <span class="toggle-slider"></span>
    </label>
    <span class="theme-label">Sombre</span>
</div>

    <div class="container <?= isset($_SESSION['show_signup']) ? 'sign-up-mode' : '' ?>">
        <?php if (isset($_SESSION['show_signup'])) unset($_SESSION['show_signup']); ?>
        
        <div class="forms-container">
            <div class="signin-signup">
                <!-- SIGN IN FORM -->
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="sign-in-form">
                    <h2 class="title">Connexion</h2>
                    
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" required />
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Mot de passe" required />
                    </div>
                    
                    <input type="submit" name="submit" value="Se connecter" class="btn solid" />
                    
                    <p class="social-text">Ou connectez-vous avec</p>
                    <div class="social-media">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-google"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </form>
                
                <!-- SIGN UP FORM -->
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="sign-up-form">
                    <h2 class="title">Inscription</h2>
                    
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="signup_name" value="<?= htmlspecialchars($name) ?>" placeholder="Nom" required />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="signup_prenom" placeholder="Prénom" required />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="signup_email" placeholder="Email" required />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Mot de passe" required />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required />
                    </div>

                    <input type="submit" name="ok" value="S'inscrire" class="btn solid" />

                    <p class="social-text">Ou inscrivez-vous avec</p>
                    <div class="social-media">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-google"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="panels-container">
            <div class="panel left-panel">
                <div class="content">
                    <h3>Nouveau chez PhosRoom?</h3>
                    <p>
                        Optimisez la gestion de vos formations en créant un compte pour accéder à toutes nos fonctionnalités avancées de réservation.
                    </p>
                    <button class="btn transparent" id="sign-up-btn">
                        S'inscrire
                    </button>
                </div>
            </div>

            <div class="panel right-panel">
                <div class="content">
                    <h3>Déjà membre?</h3>
                    <p>
                        Connectez-vous pour accéder à votre espace personnel et gérer vos réservations de salles de formation.
                    </p>
                    <button class="btn transparent" id="sign-in-btn">
                        Se connecter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sign_in_btn = document.querySelector("#sign-in-btn");
            const sign_up_btn = document.querySelector("#sign-up-btn");
            const container = document.querySelector(".container");
            const themeToggle = document.getElementById('theme-toggle');

            // Toggle between sign in and sign up forms
            sign_up_btn.addEventListener("click", () => {
                container.classList.add("sign-up-mode");
            });

            sign_in_btn.addEventListener("click", () => {
                container.classList.remove("sign-up-mode");
            });

            // Hide message after 5 seconds
            const message = document.querySelector('.message');
            if (message) {
                setTimeout(() => {
                    message.style.animation = 'slideOut 0.5s ease-out';
                    setTimeout(() => message.remove(), 500);
                }, 5000);
            }

            // Dark mode toggle functionality
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

            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size/2;
                    const y = e.clientY - rect.top - size/2;
                    
                    ripple.style.width = ripple.style.height = `${size}px`;
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 800);
                });
            });

            // Add ripple effect style
            const style = document.createElement('style');
            style.textContent = `
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.8s ease-out;
                    pointer-events: none;
                }
                
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }

                @keyframes slideOut {
                    from { top: 20px; opacity: 1; }
                    to { top: -100px; opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>