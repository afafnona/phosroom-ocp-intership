<?php

require 'db.php';

abstract class Utilisateur {

    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $mot_de_passe;
    public $date_inscription;
    
    //Basic constructor

    public function __construct() {
        
    }

    
    //Checks if the user is an administrator by querying the database
   public function isAdmin() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT 1 FROM administrateur WHERE utilisateur_id = ?");
        $stmt->execute([$this->id]);
        return (bool)$stmt->fetch();
    }

       public function getNom() {
        return $this->nom;
    }

    public function getPrenom() {
        return $this->prenom;
    }

    public function seConnecter($email, $password) {
    global $pdo;
    
    // Vérifiez d'abord dans la table utilisateurs
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $userData = $stmt->fetch();
    
    if ($userData && password_verify($password, $userData['mot_de_passe'])) {
        // Vérifiez si c'est un administrateur
        $stmtAdmin = $pdo->prepare("SELECT * FROM administrateur WHERE utilisateur_id = ?");
        $stmtAdmin->execute([$userData['id']]);
        $adminData = $stmtAdmin->fetch();
        
        if ($adminData) {
            $user = new Administrateur();
            $user->departement = $adminData['departement'];
        } else {
            $user = new Utilisateur();
        }
        
        // Remplissez les propriétés communes
        $user->id = $userData['id'];
        $user->nom = $userData['nom'];
        $user->prenom = $userData['prenom'];
        $user->email = $userData['email'];
        $user->date_inscription = $userData['date_inscription'];
        
        $_SESSION['user'] = $user;
        return true;
    }
    
    return false;
}
    
    public function getId() {
        return $this->id;
    }
}


class Administrateur extends Utilisateur {

    private $departement; //adds department property

    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT u.*, a.departement 
                             FROM utilisateurs u 
                             JOIN administrateur a ON u.id = a.utilisateur_id 
                             WHERE a.id = ?");
        $stmt->execute([$id]);
        $admin = $stmt->fetchObject('Administrateur');
        
        if ($admin) {
            $admin->departement = $admin->departement;
        }
        
        return $admin;
    }
     
    //Getter for department
    public function getDepartement() {
        return $this->departement;
    }
}

class Formateur extends Utilisateur {
    private $specialite;

    public function planifierFormation() {
        // Method for planning formations currently empty for future modificayion on the website
    }
    
    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT u.*, f.specialite FROM utilisateurs u JOIN formateur f ON u.id = f.utilisateur_id WHERE f.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchObject('Formateur');
    }
    
    public function getNomComplet() {
        return $this->prenom . ' ' . $this->nom; //Returns full name
    }
}


// 
class Participant extends Utilisateur {
    private $service;
   
    private $pdo; // Declaration of pdo propriete


    public function __sleep() {
        
        return ['id', 'nom', 'prenom', 'email', 'service'];
    }

    
    //Constructor that initializes service to null
    public function __construct() {
        parent::__construct();
        $this->service = null;
    }
    
    public static function getById($id) {
        global $pdo;
        
        //Checks if ID is numeric
        if (!is_numeric($id)) {
            throw new InvalidArgumentException("Invalid ID provided");
        }

        try {
            $stmt = $pdo->prepare("
                SELECT u.*, p.service 
                FROM utilisateurs u 
                JOIN participant p ON u.id = p.utilisateur_id 
                WHERE p.id = ?
            ");
            
            $stmt->execute([$id]);
            $participant = $stmt->fetchObject('Participant');
            
            if (!$participant) {
                throw new RuntimeException("Participant not found");
            }
            
            return $participant;
        } catch (PDOException $e) {
            throw new RuntimeException("Database error: " . $e->getMessage());
        }
    }
    
    //Getter and setter
    public function getService() {
        if (!isset($this->service)) {
            throw new RuntimeException("Service property not initialized");
        }
        return $this->service;
    }
    
    public function setService($service) {
        if (empty($service)) {
            throw new InvalidArgumentException("Service cannot be empty");
        }
        $this->service = $service;
    }
     public function __wakeup() {
        
        global $pdo;
        $this->pdo = $pdo;
    }
}


class Salle {
    private $id;
    private $nom;
    private $capacite;
    private $localisation;
    private $disponible;
    private $statut;
    
    //Fetches room by ID
    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM salles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchObject('Salle');
    }
    
    public function getNom() {
        return $this->nom;
    }
    // Returns true if room is available for given dates
    public function verifierDisponibilite($date_debut, $date_fin) {
    global $pdo;
    
    // Vérifier d'abord le statut de la salle
    if ($this->statut !== 'disponible') {
        return [
            'disponible' => false,
            'message' => "Cette salle est actuellement en " . $this->statut . "."
        ];
    }
    
    // Vérifier les réservations en conflit
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as conflits_count 
        FROM reservation 
        WHERE salle_id = ? 
        AND statut IN ('En attente', 'Confirmée')
        AND (
            (date_debut < ? AND date_fin > ?) OR
            (date_debut < ? AND date_fin > ?) OR
            (date_debut >= ? AND date_debut < ?) OR
            (date_fin > ? AND date_fin <= ?)
        )
    ");
    
    $stmt->execute([
        $this->id,
        $date_fin, $date_debut,   // Conflit chevauchement partiel début
        $date_fin, $date_debut,   // Conflit chevauchement partiel fin  
        $date_debut, $date_fin,   // Conflit début dans la plage
        $date_debut, $date_fin    // Conflit fin dans la plage
    ]);
    
    $conflits = $stmt->fetchColumn();
    
    return [
        'disponible' => $conflits == 0,
        'message' => $conflits > 0 ? 
            "Cette salle est déjà réservée pour cette plage horaire. Veuillez choisir une autre salle ou modifier vos dates." : 
            "Salle disponible pour réservation"
    ];
}
}

class Reservation {
    public $id;
    public $nom;
    public $date_demande;
    public $date_fin;
    public $statut;
    public $utilisateur_id;
    public $salle_id;
    public $salle_nom;
    public $localisation;
    private $salle;
    private $formateur;
    private $participants = [];
    private $evenement;
  
    //Fetches reservation with room and user details
    public static function getWithDetails($id) {
    global $pdo;
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
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting reservation details: " . $e->getMessage());
        return null;
    }
}
    //Gets upcoming confirmed reservations for a user
    public static function getUpcomingForUser($userId, $role = 'participant') {
        global $pdo;

        if ($role === 'formateur') {
            $stmt = $pdo->prepare("
                SELECT r.*, s.nom as salle_nom, s.localisation
                FROM reservation r
                JOIN salles s ON r.salle_id = s.id
                WHERE r.utilisateur_id = ? AND r.statut = 'confirmé'
                ORDER BY r.date_fin DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT r.*, s.nom as salle_nom, s.localisation
                FROM reservation r
                JOIN salles s ON r.salle_id = s.id
                WHERE r.utilisateur_id = ? AND r.statut = 'confirmé'
                ORDER BY r.date_fin DESC
            ");
        }

        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir en objets Reservation
        $reservations = [];
        foreach ($results as $row) {
            $reservation = new Reservation();
            $reservation->id = $row['id'];
            $reservation->nom = $row['nom'];
            $reservation->date_demande = $row['date_demande'];
            $reservation->date_fin = $row['date_fin'];
            $reservation->statut = $row['statut'];
            $reservation->utilisateur_id = $row['utilisateur_id'];
            $reservation->salle_id = $row['salle_id'];
            $reservation->salle_nom = $row['salle_nom'];
            $reservation->localisation = $row['localisation'];
            $reservations[] = $reservation;
        }
        
        return $reservations;
    }
  //Creates reservation with transaction handling
    public function creerReservation() {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO reservation 
            (date_debut, date_fin, salle_id, formateur_id, evenement_id, utilisateur_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->date_debut,
            $this->date_fin,
            $this->salle->getId(),
            $this->formateur->getId(),
            $this->evenement->getId(),
            $this->utilisateur_id // Ajouter cette propriété à la classe
        ]);
        
        // ... reste du code ...
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

    // Getters for dashboard display
    public function getId() {
        return $this->id;
    }
    
    public function getStatut() {
        return $this->statut;
    }
    
    public function getSalleNom() {
        return $this->salle_nom;
    }
    
    public function getSalleBatiment() {
        return $this->localisation;
    }
    
    public function getDateDebut() {
        return new DateTime($this->date_demande);
    }
    
    public function getDateFin() {
        return new DateTime($this->date_fin);
    }
    
    public function getNbParticipants() {
        return count($this->participants);
    }

    //Gets reservations by time period with details
    public static function getAllWithDetails($period = 'week') {
        global $pdo;
        
        $dateConditions = [
            'week' => "r.date_debut >= CURDATE() AND r.date_debut <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            'month' => "r.date_debut >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND r.date_debut <= LAST_DAY(CURDATE())",
            'year' => "YEAR(r.date_debut) = YEAR(CURDATE())",
            'all' => "1=1"
        ];
        
        $condition = $dateConditions[$period] ?? $dateConditions['week'];
        
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   s.nom as salle_nom, 
                   s.localisation,
                   CONCAT(u.prenom, ' ', u.nom) as user_name,
                   u.email as user_email
            FROM reservation r
            JOIN salles s ON r.salle_id = s.id
            JOIN utilisateurs u ON r.utilisateur_id = u.id
            WHERE $condition
            ORDER BY r.date_debut DESC, r.date_fin DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getById($id) {
        global $pdo;
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
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function updateStatus($id, $status) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE reservation SET statut = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public static function delete($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM reservation WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
//Basic event class with type and theme
class Evenement {
    private $id;
    private $type;
    private $theme;
    
    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM evenement WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchObject('Evenement');
    }
    
    public function getTheme() {
        return $this->theme;
    }
}

class Notification {
    private $id;
    private $type;
    private $message;
    private $date_envoi;
    private $destinataire;

    public function envoyer() {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO notifications
            (type, message, date_envoi, utilisateur_id)
            VALUES (?, ?, NOW(), ?)
        ");
        return $stmt->execute([
            $this->type,
            $this->message,
            $this->destinataire->getId()
        ]);
    }

    public static function getForUser($userId) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM notifications
            WHERE utilisateur_id = ? AND vue = 0
            ORDER BY date_envoi DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
//Counts reservations and calculates total hours
class Rapport {
    private $id;
    private $type;
    private $date_generation;
    private $periode_debut;
    private $periode_fin;
    private $nb_reservation;
    private $nb_heures;

    public function generer() {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as nb_reservation, 
                   SUM(TIMESTAMPDIFF(HOUR, date_debut, date_fin)) as nb_heures
            FROM reservation
            WHERE date_debut BETWEEN ? AND ?
        ");
        $stmt->execute([$this->periode_debut, $this->periode_fin]);
        $data = $stmt->fetch();
        
        $this->nb_reservation = $data['nb_reservation'];
        $this->nb_heures = $data['nb_heures'];
        $this->date_generation = date('Y-m-d H:i:s');
        
        return $this;
    }

    public function exporterPDF() {
        // Logique pour exporter en PDF
    }
}
?>