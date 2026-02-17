<?php
include 'db.php';

// Hasher le mot de passe admin123
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);

// Mettre à jour le mot de passe dans la base de données
$stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
$stmt->execute([$hashed_password, 'admin@ocpgroup.ma']);

echo "Mot de passe mis à jour avec succès !\n";
echo "Email: admin@ocpgroup.ma\n";
echo "Mot de passe: admin123\n";
echo "Hash généré: " . $hashed_password . "\n";
?> 