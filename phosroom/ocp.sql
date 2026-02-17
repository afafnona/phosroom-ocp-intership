-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 08 nov. 2025 à 16:36
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ocp`
--

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

CREATE TABLE `administrateur` (
  `id` int(11) NOT NULL,
  `departement` varchar(100) NOT NULL,
  `utilisateur_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `administrateur`
--

INSERT INTO `administrateur` (`id`, `departement`, `utilisateur_id`) VALUES
(3, '', 4);

-- --------------------------------------------------------

--
-- Structure de la table `equipement`
--

CREATE TABLE `equipement` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `salle_id` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `equipement`
--

INSERT INTO `equipement` (`id`, `nom`, `description`, `salle_id`, `quantite`) VALUES
(1, 'Projecteur HD', 'Projecteur haute définition', 6, 13),
(2, 'Système audio', 'Système de sonorisation', 2, 15),
(3, 'Tableau interactif', 'Tableau numérique interactif', 1, 8),
(4, 'Écran de projection', 'Écran motorisé 3m x 2m', 1, 8),
(5, 'Microphone sans fil', 'Système de micros HF', 2, 10),
(6, 'Webcam 4K', 'Caméra haute résolution pour visioconférence', 2, 10),
(7, 'Ordinateur portable', 'Laptop pour présentations', 1, 11),
(8, 'Télécommande de présentation', 'Télécommande laser avec pointeur', 1, 15),
(9, 'Paperboard', 'Tableau blanc mobile avec feuilles', 9, 15),
(10, 'Marqueurs', 'Set de marqueurs couleurs', 3, 20),
(11, 'Rallonge électrique', 'Multiprise 10 prises avec câble 5m', 1, 10),
(12, 'Adaptateurs HDMI', 'Kit d\'adaptateurs pour connexions diverses', 1, 20),
(13, 'Tableau blanc', 'Tableau effaçable fixe', 3, 10),
(14, 'Chaises supplémentaires', 'Lot de 10 chaises pliantes', 2, 30);

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

CREATE TABLE `evenement` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `theme` varchar(200) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `statut` varchar(50) DEFAULT 'Planifié',
  `formateur_id` int(11) DEFAULT NULL,
  `salle_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id`, `type`, `theme`, `date_debut`, `date_fin`, `heure_debut`, `heure_fin`, `statut`, `formateur_id`, `salle_id`, `reservation_id`) VALUES
(11, 'Réunion', 'aafaf', '2025-08-01', '2025-08-02', '06:00:00', '07:00:00', 'Planifié', NULL, 12, NULL),
(12, 'Réunion', 'aafaf', '2025-08-01', '2025-08-02', '04:00:00', '06:00:00', 'Planifié', NULL, 11, NULL),
(13, 'Réunion', 'gg', '2025-07-17', '2025-07-18', '08:00:00', '09:00:00', 'Planifié', NULL, 13, NULL),
(14, 'Réunion', 'lll', '2025-07-25', '2025-07-25', '06:00:00', '09:00:00', 'Planifié', NULL, 13, NULL),
(15, 'Réunion', 'hh', '2025-07-31', '2025-08-01', '08:00:00', '09:00:00', 'Planifié', NULL, 6, NULL),
(16, 'Formation', 'java', '2025-10-25', '2025-10-25', '13:03:00', '15:57:00', 'Planifié', 1, 11, NULL),
(17, 'Formation', 'testing', '2025-10-16', '2025-10-16', '12:24:00', '16:24:00', 'Planifié', 1, 6, NULL),
(18, 'Formation', 'kls\\', '2025-10-24', '2025-10-24', '07:05:00', '09:07:00', 'Planifié', 1, 2, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `evenement_participants`
--

CREATE TABLE `evenement_participants` (
  `evenement_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `date_inscription` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `formateur`
--

CREATE TABLE `formateur` (
  `id` int(11) NOT NULL,
  `specialite` varchar(100) NOT NULL,
  `utilisateur_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formateur`
--

INSERT INTO `formateur` (`id`, `specialite`, `utilisateur_id`) VALUES
(1, 'jJLL', 2);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` date DEFAULT curdate(),
  `destinataire_id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `vue` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `message`, `date_envoi`, `destinataire_id`, `utilisateur_id`, `vue`) VALUES
(1, 'annulation', 'Votre réservation #6 a été annulée avec succès.', '2025-07-16', 2, NULL, 0),
(2, 'annulation', 'Votre réservation #3 a été annulée avec succès.', '2025-07-16', 2, NULL, 0),
(3, 'annulation', 'Votre réservation #5 a été annulée avec succès.', '2025-07-16', 2, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `service` varchar(100) NOT NULL,
  `utilisateur_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `participants`
--

INSERT INTO `participants` (`id`, `service`, `utilisateur_id`) VALUES
(1, 'Service', 2),
(3, 'Service', 6),
(4, 'informatique', 7),
(5, 'Service', 8);

-- --------------------------------------------------------

--
-- Structure de la table `rapport`
--

CREATE TABLE `rapport` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `date_generation` date DEFAULT curdate(),
  `date_debut_periode` date NOT NULL,
  `date_fin_periode` date NOT NULL,
  `nb_reservations` int(11) DEFAULT 0,
  `nb_heures` int(11) DEFAULT 0,
  `contenu` text DEFAULT NULL,
  `administrateur_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rapport`
--

INSERT INTO `rapport` (`id`, `type`, `date_generation`, `date_debut_periode`, `date_fin_periode`, `nb_reservations`, `nb_heures`, `contenu`, `administrateur_id`) VALUES
(5, 'mensuel', '2025-07-25', '2025-07-24', '2025-07-26', 0, NULL, NULL, 3),
(6, 'mensuel', '2025-07-25', '2025-07-01', '2025-07-25', 5, 2376, NULL, 3),
(7, 'trimestriel', '2025-10-27', '2025-09-25', '2025-09-30', 0, 0, NULL, 3),
(8, 'mensuel', '2025-10-27', '2025-09-30', '2025-09-30', 0, 0, NULL, 3),
(9, 'mensuel', '2025-10-27', '2025-09-30', '2025-09-30', 0, NULL, NULL, 3),
(10, 'mensuel', '2025-10-27', '2025-10-01', '2025-10-31', 2, 360, NULL, 3),
(11, 'mensuel', '2025-10-27', '2025-10-01', '2025-10-31', 2, 360, NULL, 3),
(12, 'mensuel', '2025-10-27', '2025-10-01', '2025-10-31', 2, 360, NULL, 3),
(13, 'mensuel', '2025-10-27', '2025-10-01', '2025-10-31', 2, 360, NULL, 3),
(14, 'personnalisé', '2025-10-27', '2025-07-01', '2025-07-31', 5, 2376, NULL, 3),
(15, 'mensuel', '2025-11-03', '2025-11-01', '2025-11-30', 0, NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
--

CREATE TABLE `reservation` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `date_demande` date DEFAULT curdate(),
  `date_debut` date DEFAULT NULL,
  `date_fin` date NOT NULL,
  `statut` enum('En attente','Confirmée','Annulée') DEFAULT 'En attente',
  `utilisateur_id` int(11) NOT NULL,
  `salle_id` int(11) NOT NULL,
  `formateur_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`id`, `nom`, `date_demande`, `date_debut`, `date_fin`, `statut`, `utilisateur_id`, `salle_id`, `formateur_id`) VALUES
(3, 'aafaf', '2025-07-08', '2025-08-01', '2025-08-02', 'Confirmée', 2, 12, NULL),
(4, 'aafaf', '2025-07-08', '0000-00-00', '2025-08-02', 'En attente', 2, 11, NULL),
(5, 'gg', '2025-07-08', '0000-00-00', '2025-07-18', 'Confirmée', 2, 13, NULL),
(6, 'lll', '2025-07-09', '2025-07-09', '2025-07-25', 'Confirmée', 2, 13, NULL),
(7, 'hh', '2025-07-09', '0000-00-00', '2025-08-01', 'En attente', 2, 6, NULL),
(8, 'java', '2025-10-12', '0000-00-00', '2025-10-25', 'En attente', 2, 11, NULL),
(9, 'testing', '2025-10-14', '0000-00-00', '2025-10-16', 'Annulée', 2, 6, NULL),
(10, 'kls\\', '2025-10-28', NULL, '2025-10-24', 'Confirmée', 2, 2, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `capacite` int(11) NOT NULL,
  `localisation` varchar(50) NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `statut` enum('disponible','reserve','en_maintenance') NOT NULL DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `capacite`, `localisation`, `disponible`, `statut`) VALUES
(1, 'M1', 21, '1er étage', 1, 'disponible'),
(2, 'M2', 22, '1er étage', 1, 'disponible'),
(3, 'M3', 20, '1er étage', 1, 'disponible'),
(4, 'M4', 16, '1er étage', 1, 'reserve'),
(5, 'M5', 16, '1er étage', 1, 'reserve'),
(6, 'Salle de réunion', 50, '1er étage', 1, 'disponible'),
(7, 'M9', 30, 'RDC', 1, 'en_maintenance'),
(8, 'Y23(Salle bureatique)', 11, 'RDC', 1, 'disponible'),
(9, 'S1', 20, 'RDC', 1, 'disponible'),
(11, 'S2', 20, 'RDC', 1, 'disponible'),
(12, 'S3', 20, 'RDC', 1, 'disponible'),
(13, 'Salle de mouvement', 20, 'RDC', 1, 'disponible');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `seConnecter` tinyint(1) DEFAULT 0,
  `role` enum('administrateur','formateur','participant') NOT NULL DEFAULT 'participant'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `seConnecter`, `role`) VALUES
(2, 'mzaourou', 'afaf', 'afafmzaourou8@gmail.com', '$2y$10$44NYSYP7KUGEEl8VVMRFmOpOpYJ7aYH7fZbvr0xM5mlPNUpnslNC2', 0, 'participant'),
(4, 'mzaourou', 'ag', 'christian@dior.com', '$2y$10$OLGtlN4f0xtE6PxdJwOFYuKbziY7MbktQUs/oHvCgRM/Ah27ApdS6', 0, 'administrateur'),
(6, 'afafnona', 'afafnona', 'afafmzaourou7@gmail.com', '$2y$10$w5rnzaxhaeYmE424HD22W.EkXmRId6/mMuTmSd7jy8y4fdyqmInnu', 0, 'participant'),
(7, 'mzaourou', 'afaf', 'afafmzaourou@gmail.com', '$2y$10$/LplozZci7WOgQAZ1l2sweLP0Lhcf7Z35DUaIEIVuOWz4xZa9VyJi', 0, 'participant'),
(8, 'mzaourou', 'ag', 'g@gmail.com', '$2y$10$shLD9oLqafwy81FfOwmOWu7HhSgZKkba9hJ4VJpl6he.277x31pvO', 0, 'participant');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `administrateur`
--
ALTER TABLE `administrateur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `equipement`
--
ALTER TABLE `equipement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salle_id` (`salle_id`);

--
-- Index pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formateur_id` (`formateur_id`),
  ADD KEY `salle_id` (`salle_id`),
  ADD KEY `fk_evenement_reservation` (`reservation_id`);

--
-- Index pour la table `evenement_participants`
--
ALTER TABLE `evenement_participants`
  ADD PRIMARY KEY (`evenement_id`,`participant_id`),
  ADD KEY `participant_id` (`participant_id`);

--
-- Index pour la table `formateur`
--
ALTER TABLE `formateur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destinataire_id` (`destinataire_id`);

--
-- Index pour la table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `rapport`
--
ALTER TABLE `rapport`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administrateur_id` (`administrateur_id`);

--
-- Index pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `salle_id` (`salle_id`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `administrateur`
--
ALTER TABLE `administrateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `equipement`
--
ALTER TABLE `equipement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `formateur`
--
ALTER TABLE `formateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `rapport`
--
ALTER TABLE `rapport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `salles`
--
ALTER TABLE `salles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `administrateur`
--
ALTER TABLE `administrateur`
  ADD CONSTRAINT `administrateur_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `equipement`
--
ALTER TABLE `equipement`
  ADD CONSTRAINT `equipement_ibfk_1` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD CONSTRAINT `evenement_ibfk_1` FOREIGN KEY (`formateur_id`) REFERENCES `formateur` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `evenement_ibfk_2` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_evenement_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `evenement_participants`
--
ALTER TABLE `evenement_participants`
  ADD CONSTRAINT `evenement_participants_ibfk_1` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evenement_participants_ibfk_2` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `formateur`
--
ALTER TABLE `formateur`
  ADD CONSTRAINT `formateur_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `participants`
--
ALTER TABLE `participants`
  ADD CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rapport`
--
ALTER TABLE `rapport`
  ADD CONSTRAINT `rapport_ibfk_1` FOREIGN KEY (`administrateur_id`) REFERENCES `administrateur` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
