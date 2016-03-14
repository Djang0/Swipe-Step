  -- phpMyAdmin SQL Dump
  -- version 4.4.13.1deb1
  -- http://www.phpmyadmin.net
  --
  -- Client :  localhost
  -- Généré le :  Lun 14 Mars 2016 à 14:52
  -- Version du serveur :  5.6.28-0ubuntu0.15.10.1
  -- Version de PHP :  5.6.11-1ubuntu3.1

  SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
  SET time_zone = "+00:00";


  /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
  /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
  /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
  /*!40101 SET NAMES utf8mb4 */;

  --
  -- Base de données :  `clicktrax`
  --

  -- --------------------------------------------------------

  --
  -- Structure de la table `hits`
  --
  DROP TABLE IF EXISTS `hits`;
  CREATE TABLE IF NOT EXISTS `hits` (
    `id` int(6) NOT NULL,
    `target_id` int(6) NOT NULL,
    `stamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip` varchar(255) NOT NULL,
    `referrer` varchar(2083) NOT NULL
  ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

  --
  -- Contenu de la table `hits`
  --


  -- --------------------------------------------------------

  --
  -- Structure de la table `owners`
  --
  DROP TABLE IF EXISTS `owners`;
  CREATE TABLE IF NOT EXISTS `owners` (
    `id` int(6) NOT NULL,
    `name` varchar(20) COLLATE utf8_bin NOT NULL,
    `api_key` varchar(32) COLLATE utf8_bin NOT NULL,
    `stamp_last_push` datetime NOT NULL,
    `stamp_last_get` datetime NOT NULL
  ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

  --
  -- Contenu de la table `owners`
  --

  INSERT INTO `owners` (`id`, `name`, `api_key`, `stamp_last_push`, `stamp_last_get`) VALUES
  (1, 'BP_generic', '863bab09542012a045aa112b31c4261d', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

  -- --------------------------------------------------------

  --
  -- Structure de la table `targets`
  --
  DROP TABLE IF EXISTS `targets`;
  CREATE TABLE IF NOT EXISTS `targets` (
    `id` int(6) NOT NULL,
    `stamp_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `code` varchar(32) COLLATE utf8_bin NOT NULL,
    `url` varchar(2083) COLLATE utf8_bin NOT NULL,
    `owner_id` int(6) NOT NULL
  ) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;



  --
  -- Index pour les tables exportées
  --

  --
  -- Index pour la table `hits`
  --
  ALTER TABLE `hits`
    ADD PRIMARY KEY (`id`),
    ADD KEY `hit_fk_target` (`target_id`);

  --
  -- Index pour la table `owners`
  --
  ALTER TABLE `owners`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `uniq_k` (`api_key`);

  --
  -- Index pour la table `targets`
  --
  ALTER TABLE `targets`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `code_uniq` (`code`),
    ADD KEY `target_fk_owner` (`owner_id`);

  --
  -- AUTO_INCREMENT pour les tables exportées
  --

  --
  -- AUTO_INCREMENT pour la table `hits`
  --
  ALTER TABLE `hits`
    MODIFY `id` int(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
  --
  -- AUTO_INCREMENT pour la table `owners`
  --
  ALTER TABLE `owners`
    MODIFY `id` int(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
  --
  -- AUTO_INCREMENT pour la table `targets`
  --
  ALTER TABLE `targets`
    MODIFY `id` int(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=15;
  /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
  /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
  /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
