<?php
// Récupérer les documents de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM documents WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$documents = $stmt->fetchAll();
?>