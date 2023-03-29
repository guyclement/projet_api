-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 29 mars 2023 à 20:39
-- Version du serveur : 8.0.31
-- Version de PHP : 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projetforum`
--

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

DROP TABLE IF EXISTS `article`;
CREATE TABLE IF NOT EXISTS `article` (
  `id_article` int NOT NULL AUTO_INCREMENT,
  `contenu` varchar(200) NOT NULL,
  `datePublication` int NOT NULL,
  `auteur` varchar(20) NOT NULL,
  PRIMARY KEY (`id_article`),
  KEY `fk_auteur` (`auteur`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `article`
--

INSERT INTO `article` (`id_article`, `contenu`, `datePublication`, `auteur`) VALUES
(4, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. In eu tellus et leo eleifend maximus. Proin commodo lacus ex, sed dictum tellus fermentum quis. Donec eget est vitae nunc molestie placerat. Pr', 2147483647, 'Clément'),
(5, 'Integer sed tristique ante. Phasellus eget est tortor. Vivamus tincidunt, nibh et varius rutrum, tortor tellus tincidunt tortor, at sagittis mi odio nec sapien. Integer semper pulvinar massa pellentes', 2147483647, 'Clément'),
(6, 'Vestibulum rutrum sem ut leo cursus, vel ultrices purus varius. Sed gravida neque vitae lectus dignissim, laoreet tempus ante ultrices. Vivamus tristique porttitor lobortis. Vivamus turpis ex, varius ', 2147483647, 'utilisateur'),
(7, 'contenu', 2147483647, 'utilisateur'),
(8, 'un super article', 2147483647, 'utilisateur');

-- --------------------------------------------------------

--
-- Structure de la table `liker`
--

DROP TABLE IF EXISTS `liker`;
CREATE TABLE IF NOT EXISTS `liker` (
  `id_article` int NOT NULL,
  `pseudo` varchar(20) NOT NULL,
  `statut` int NOT NULL,
  PRIMARY KEY (`id_article`,`pseudo`),
  KEY `fk_pseudo` (`pseudo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `liker`
--

INSERT INTO `liker` (`id_article`, `pseudo`, `statut`) VALUES
(1, 'clement', 1),
(1, 'utilisateur', 0),
(2, 'clement', 1),
(2, 'utilisateur', 1),
(7, 'Clément', 0);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `pseudo` varchar(20) NOT NULL,
  `mdp` varchar(65) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  PRIMARY KEY (`pseudo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`pseudo`, `mdp`, `role`) VALUES
('clement', '8323d094b1e753666ff38463266f627517b5906e3083853305914d29aa3f9142', 'publisher'),
('utilisateur', '967520ae23e8ee14888bae72809031b98398ae4a636773e18fff917d77679334', 'publisher'),
('moderator', '967520ae23e8ee14888bae72809031b98398ae4a636773e18fff917d77679334', 'moderator');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
