mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: check_master_db
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `action`
--

DROP TABLE IF EXISTS `action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `action` (
  `id_action` int NOT NULL,
  `lib_action` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `action`
--

LOCK TABLES `action` WRITE;
/*!40000 ALTER TABLE `action` DISABLE KEYS */;
INSERT INTO `action` VALUES (0,'Valider');
/*!40000 ALTER TABLE `action` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affecter`
--

DROP TABLE IF EXISTS `affecter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `affecter` (
  `id_ens` int NOT NULL,
  `id_rapport` int NOT NULL,
  `id_jury` int NOT NULL,
  PRIMARY KEY (`id_ens`,`id_rapport`,`id_jury`),
  KEY `id_rapport` (`id_rapport`),
  KEY `id_jury` (`id_jury`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affecter`
--

LOCK TABLES `affecter` WRITE;
/*!40000 ALTER TABLE `affecter` DISABLE KEYS */;
/*!40000 ALTER TABLE `affecter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annee_academique`
--

DROP TABLE IF EXISTS `annee_academique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `annee_academique` (
  `id_ac` int NOT NULL AUTO_INCREMENT,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `statut_annee` enum('En cours','Terminée') NOT NULL,
  PRIMARY KEY (`id_ac`)
) ENGINE=InnoDB AUTO_INCREMENT=2626 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annee_academique`
--

LOCK TABLES `annee_academique` WRITE;
/*!40000 ALTER TABLE `annee_academique` DISABLE KEYS */;
INSERT INTO `annee_academique` VALUES (2524,'2024-02-08','2025-05-11','En cours'),(2625,'2025-05-23','2026-11-22','Terminée');
/*!40000 ALTER TABLE `annee_academique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approuver`
--

DROP TABLE IF EXISTS `approuver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approuver` (
  `id_personnel_adm` int NOT NULL,
  `id_rapport_etd` int NOT NULL,
  `date_approbation` date DEFAULT NULL,
  `com_appr` text,
  PRIMARY KEY (`id_personnel_adm`,`id_rapport_etd`),
  KEY `id_rapport_etd` (`id_rapport_etd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approuver`
--

LOCK TABLES `approuver` WRITE;
/*!40000 ALTER TABLE `approuver` DISABLE KEYS */;
INSERT INTO `approuver` VALUES (11,49,'2025-05-25','Bien');
/*!40000 ALTER TABLE `approuver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archives`
--

DROP TABLE IF EXISTS `archives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archives` (
  `id_archives` int NOT NULL AUTO_INCREMENT,
  `id_rapport_etd` int DEFAULT NULL,
  `id_cr` int DEFAULT NULL,
  `date_archivage` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_utilisateur` int NOT NULL,
  `id_ac` varchar(10) NOT NULL,
  `fichier_archive` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_archives`),
  KEY `fk_archives_rapport` (`id_rapport_etd`),
  KEY `fk_archives_compte_rendu` (`id_cr`),
  KEY `fk_archives_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_archives_compte_rendu` FOREIGN KEY (`id_cr`) REFERENCES `compte_rendu` (`id_cr`),
  CONSTRAINT `fk_archives_rapport` FOREIGN KEY (`id_rapport_etd`) REFERENCES `rapport_etudiant` (`id_rapport_etd`),
  CONSTRAINT `fk_archives_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archives`
--

LOCK TABLES `archives` WRITE;
/*!40000 ALTER TABLE `archives` DISABLE KEYS */;
/*!40000 ALTER TABLE `archives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avoir`
--

DROP TABLE IF EXISTS `avoir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avoir` (
  `id_grd` int NOT NULL,
  `id_ens` int NOT NULL,
  `date_grd` date DEFAULT NULL,
  PRIMARY KEY (`id_grd`,`id_ens`),
  KEY `id_ens` (`id_ens`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avoir`
--

LOCK TABLES `avoir` WRITE;
/*!40000 ALTER TABLE `avoir` DISABLE KEYS */;
INSERT INTO `avoir` VALUES (1,1,'2025-05-20'),(1,2,'2025-05-19'),(1,3,'2025-05-19'),(1,4,'2023-01-01'),(1,5,'2023-01-01'),(1,6,'2023-01-01'),(1,7,'2023-01-01'),(1,8,'2023-01-01'),(1,9,'2023-01-01'),(1,10,'2023-01-01'),(2,20,'2025-07-06'),(3,13,'2025-05-31'),(3,14,'2025-05-31'),(3,15,'2025-05-31'),(3,16,'2025-05-31'),(1,21,'2025-06-11'),(3,22,'2025-06-01'),(1,23,'2025-06-01'),(2,24,'2025-06-18');
/*!40000 ALTER TABLE `avoir` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_commission`
--

DROP TABLE IF EXISTS `chat_commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_commission` (
  `id_chat` int NOT NULL AUTO_INCREMENT,
  `id_ens` int NOT NULL,
  `id_rapport_etd` int NOT NULL,
  `id_message` int NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_chat`),
  UNIQUE KEY `id_message` (`id_message`),
  KEY `id_ens` (`id_ens`),
  KEY `idx_rapport` (`id_rapport_etd`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_commission`
--

LOCK TABLES `chat_commission` WRITE;
/*!40000 ALTER TABLE `chat_commission` DISABLE KEYS */;
INSERT INTO `chat_commission` VALUES (1,3,49,1,'2025-05-25 23:26:24'),(2,1,49,2,'2025-05-25 23:26:42'),(3,2,49,3,'2025-05-25 23:27:03'),(4,3,49,4,'2025-05-25 23:28:18'),(5,2,49,5,'2025-05-25 23:28:45'),(6,1,49,6,'2025-05-25 23:29:43'),(7,1,49,7,'2025-05-25 23:30:47'),(8,2,49,8,'2025-05-25 23:32:42'),(9,1,49,9,'2025-05-25 23:32:51'),(10,1,49,10,'2025-05-25 23:33:08'),(11,2,49,11,'2025-05-25 23:33:16'),(12,1,49,12,'2025-05-25 23:33:28'),(13,1,49,13,'2025-05-25 23:33:45'),(14,2,49,14,'2025-05-25 23:34:21'),(15,1,49,15,'2025-05-25 23:34:46');
/*!40000 ALTER TABLE `chat_commission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compte_rendu`
--

DROP TABLE IF EXISTS `compte_rendu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compte_rendu` (
  `id_cr` int NOT NULL AUTO_INCREMENT,
  `id_rapport_etd` int NOT NULL,
  `nom_cr` varchar(100) DEFAULT NULL,
  `date_cr` date DEFAULT NULL,
  `fichier_cr` varchar(255) NOT NULL,
  PRIMARY KEY (`id_cr`),
  KEY `fk_cr_rapport_etd` (`id_rapport_etd`),
  CONSTRAINT `fk_cr_rapport_etd` FOREIGN KEY (`id_rapport_etd`) REFERENCES `rapport_etudiant` (`id_rapport_etd`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compte_rendu`
--

LOCK TABLES `compte_rendu` WRITE;
/*!40000 ALTER TABLE `compte_rendu` DISABLE KEYS */;
INSERT INTO `compte_rendu` VALUES (11,49,'Compte rendu du 2025-06-01','2025-06-01','assets/uploads/compte_rendu/GOMEZ_Ange Axel_2025-06-01.pdf');
/*!40000 ALTER TABLE `compte_rendu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demande_soutenance`
--

DROP TABLE IF EXISTS `demande_soutenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demande_soutenance` (
  `id_demande` int NOT NULL AUTO_INCREMENT,
  `num_etd` int NOT NULL,
  `date_demande` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  `statut_demande` enum('En attente','Traitée') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'En attente',
  PRIMARY KEY (`id_demande`),
  KEY `num_etd` (`num_etd`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demande_soutenance`
--

LOCK TABLES `demande_soutenance` WRITE;
/*!40000 ALTER TABLE `demande_soutenance` DISABLE KEYS */;
INSERT INTO `demande_soutenance` VALUES (27,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée'),(26,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée'),(25,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée'),(22,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée'),(21,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée'),(24,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée'),(23,1,'2025-05-25 00:00:00','2025-05-25 17:16:38','Traitée');
/*!40000 ALTER TABLE `demande_soutenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deposer`
--

DROP TABLE IF EXISTS `deposer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposer` (
  `num_etd` int NOT NULL,
  `id_rapport_etd` int NOT NULL,
  `date_depot` date DEFAULT NULL,
  PRIMARY KEY (`num_etd`,`id_rapport_etd`),
  KEY `id_rapport_etd` (`id_rapport_etd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposer`
--

LOCK TABLES `deposer` WRITE;
/*!40000 ALTER TABLE `deposer` DISABLE KEYS */;
INSERT INTO `deposer` VALUES (1,49,'2025-05-25');
/*!40000 ALTER TABLE `deposer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `type_fichier` varchar(50) DEFAULT NULL,
  `taille_fichier` int DEFAULT NULL,
  `telecharger_par` int NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reunion_id` (`reunion_id`),
  KEY `documents_ibfk_2` (`telecharger_par`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `reunions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`telecharger_par`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ecue`
--

DROP TABLE IF EXISTS `ecue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecue` (
  `id_ecue` int NOT NULL,
  `lib_ecue` varchar(100) DEFAULT NULL,
  `credit_ecue` int DEFAULT NULL,
  `id_ue` int DEFAULT NULL,
  PRIMARY KEY (`id_ecue`),
  KEY `id_ue` (`id_ue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ecue`
--

LOCK TABLES `ecue` WRITE;
/*!40000 ALTER TABLE `ecue` DISABLE KEYS */;
INSERT INTO `ecue` VALUES (0,'',0,0);
/*!40000 ALTER TABLE `ecue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enseignant_specialite`
--

DROP TABLE IF EXISTS `enseignant_specialite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enseignant_specialite` (
  `id_ens` int NOT NULL,
  `id_spe` int NOT NULL,
  PRIMARY KEY (`id_ens`,`id_spe`),
  KEY `id_spe` (`id_spe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enseignant_specialite`
--

LOCK TABLES `enseignant_specialite` WRITE;
/*!40000 ALTER TABLE `enseignant_specialite` DISABLE KEYS */;
INSERT INTO `enseignant_specialite` VALUES (23,1),(2,2),(7,2),(1,3),(3,3),(6,3),(10,3),(21,3),(9,4),(20,4),(22,4),(24,4),(4,5),(16,5),(5,7),(8,8);
/*!40000 ALTER TABLE `enseignant_specialite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enseignants`
--

DROP TABLE IF EXISTS `enseignants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enseignants` (
  `id_ens` int NOT NULL AUTO_INCREMENT,
  `nom_ens` varchar(50) DEFAULT NULL,
  `prenoms_ens` varchar(50) DEFAULT NULL,
  `email_ens` varchar(100) DEFAULT NULL,
  `date_entree_fonction` date DEFAULT NULL,
  `num_tel_ens` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `date_naissance_ens` date DEFAULT NULL,
  `sexe_ens` enum('Homme','Femme') NOT NULL,
  `photo_ens` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `mdp_ens` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_ens`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enseignants`
--

LOCK TABLES `enseignants` WRITE;
/*!40000 ALTER TABLE `enseignants` DISABLE KEYS */;
INSERT INTO `enseignants` VALUES (1,'KOUA','Brou','kouaB@univ.edu',NULL,'0554126358','2025-05-15','Homme','assets/uploads/profiles/683c909ad943b_pdp.jpg','7ef21598d64e4d4eabed6e9ff00f9b9b3e14464799b16279f5ecc16b7475bc97'),(2,'BROU','Patrice','brouP@univ.edu',NULL,'','0000-00-00','Homme','','4854286bb514f68044481d57dba9a2e566b79b4b6317f68c2e97b55197242800'),(3,'WAH','Médard','wahM@univ.edu',NULL,'','0000-00-00','Homme','','d1ca26082383037ae8cd65eb6f56fbdd1dcb6c8b48e47bc937d2e26047c0c5ab'),(4,'DIARRA','Mamadou','diarraM@univ.edu',NULL,'','0000-00-00','Homme','','83a4c6a74878358f75790333520b3f5463fb82e7d2e8e078a7bccac7c028493a'),(5,'CODJIA','Adolphe','codjiaA@univ.edu',NULL,'','0000-00-00','Homme','','8610662eb326ba1125029b5fc34d50d1f0f6cf737ffe7edbed7ce003f8e43bf8'),(6,'IDA','Brou','idaB@univ.edu',NULL,'','0000-00-00','Homme','','acec2454b5dcc5d741b13d5e703f252306663f107ba6e8ec3ceba3fa2d48e6b4'),(7,'KOUAKOU','Mathias','kouakouM@univ.edu',NULL,'','0000-00-00','Homme','','44abaac58f10d40e2c71635ff22b2b9d0f022705194bc6f15ea1ffa3f5a95b87'),(8,'KOUAKOU','Florent','kouakouF@univ.edu',NULL,'','0000-00-00','Homme','','95d0c9dbe9524157bb22eded258711ff6e2a27c835a8717b263d9809a9ba2ed4'),(9,'BAILLY','Balé','baillyB@univ.edu',NULL,'','0000-00-00','Homme','','d63539ffceb1864f36ac2104f8b92b9cb878c52eefd2f7019bdee0e0e9e70f41'),(10,'BAKAYOKO','Ibrahima','bakayokoI@univ.edu',NULL,'','0000-00-00','Homme','','3409e586c8fc3b086f9432a784058123b77b65b5579bcc3f7f217fd5756734e5'),(22,'Konaté','NGOLO','konateN@univ.edu','2025-06-10',NULL,NULL,'Homme',NULL,NULL),(21,'YOLI BI','Martin','yoliM@gmail.com','2025-06-11',NULL,NULL,'Homme',NULL,NULL),(20,'YOLI BI','Ibrahim','yoliB@univ.ci','2025-07-06',NULL,NULL,'Homme',NULL,NULL),(23,'NINDJIN','Malan','nindjinM@edu.ci','2025-06-11',NULL,NULL,'Homme','assets/uploads/profiles/683dbd4a68f20_logo_mi-removebg-preview.png',NULL),(24,'TOURE','Mohamed','toureM@univ.edu','2025-06-18',NULL,NULL,'Homme',NULL,NULL);
/*!40000 ALTER TABLE `enseignants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entreprise`
--

DROP TABLE IF EXISTS `entreprise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprise` (
  `id_entr` int NOT NULL AUTO_INCREMENT,
  `lib_entr` varchar(255) DEFAULT NULL,
  `adresse` varchar(500) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_entr`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entreprise`
--

LOCK TABLES `entreprise` WRITE;
/*!40000 ALTER TABLE `entreprise` DISABLE KEYS */;
INSERT INTO `entreprise` VALUES (17,'DATA354','Genie 2000 - faya','Abidjan','Côte d’Ivoire','22450572','data354@mail.com'),(18,'DATA354','Genie 2000 - faya','Abidjan','Côte d’Ivoire','0707019478','data354@mail.com'),(19,'Ange Axel Gomez','Genie 2000 - faya','Abidjan','Côte d’Ivoire','0707019478','data354@mail.com');
/*!40000 ALTER TABLE `entreprise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etudiants` (
  `num_etd` int NOT NULL,
  `nom_etd` varchar(100) DEFAULT NULL,
  `num_carte_etd` varchar(30) DEFAULT NULL,
  `prenom_etd` varchar(100) DEFAULT NULL,
  `date_naissance_etd` date DEFAULT NULL,
  `email_etd` varchar(255) DEFAULT NULL,
  `mdp_etd` varchar(255) DEFAULT NULL,
  `statut_eligibilite` enum('En attente de confirmation','Éligible','Non éligible') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'En attente de confirmation',
  `promotion_etd` varchar(15) NOT NULL,
  `sexe_etd` enum('Homme','Femme') NOT NULL,
  `num_tel_etd` varchar(20) NOT NULL,
  `adresse_etd` varchar(120) NOT NULL,
  `ville_etd` varchar(50) NOT NULL,
  `pays_etd` varchar(30) NOT NULL,
  `photo_etd` varchar(255) NOT NULL,
  `id_niv_etd` int DEFAULT NULL,
  PRIMARY KEY (`num_etd`),
  KEY `fk_etudiants_niveau_etude` (`id_niv_etd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etudiants`
--

LOCK TABLES `etudiants` WRITE;
/*!40000 ALTER TABLE `etudiants` DISABLE KEYS */;
INSERT INTO `etudiants` VALUES (2,'KROUMA','ETD2025002','Franck Adams','2005-11-22','francky@etu.univ.edu','adb029bcca098ad49fb407588213f334f63ba6447fcb3890b6ff4b865c9904ee','Éligible','2021-2022','Homme','','','','','',3),(3,'AMANI','ETD2025003','Yves','2001-03-08','yvesA@etu.univ.edu','f65ff48872eaae1ebbe688036fe211cfac21bee110f01612f6dee77f19514717','En attente de confirmation','2021-2022','Homme','','','','','',NULL),(4,'YAO','ETD2025004','Ama Marie-grâce','2004-01-12','yaoAy@etu.univ.edu','2d4a54cdf420b77f0ae9e02f37c4b9b018d6f9a84bf7c14c93bd3f8c3a0bfe6e','Non éligible','2003-2004','Femme','','','','','',NULL),(5,'COULIBALY','ETD2025005','Emmanuella','2005-05-18','emma.moreau@etu.univ.edu','3113cf7a13d42e2d98553b5d1e86554c7331bdc9d51a20d1b52edf09c7c66470','En attente de confirmation','2004-2005','Femme','','','','','',NULL),(1,'GOMEZ','ETD2025001','Ange Axel','2000-05-15','axelangegomez@gmail.com','b30fd6658bf116e99f4be6c88a21dfe8797fca5cd17ac5fb1fda8d140d3ca387','Éligible','2008-2009','Homme','+225 0707019478','genie 2000','Abidjan','Côte d\'ivoire','',3);
/*!40000 ALTER TABLE `etudiants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluer_ecue`
--

DROP TABLE IF EXISTS `evaluer_ecue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluer_ecue` (
  `num_etd` int NOT NULL,
  `id_ecue` int NOT NULL,
  `id_ens` int NOT NULL,
  `date_eval` date DEFAULT NULL,
  `note` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`num_etd`,`id_ecue`,`id_ens`),
  KEY `id_ecue` (`id_ecue`),
  KEY `id_ens` (`id_ens`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluer_ecue`
--

LOCK TABLES `evaluer_ecue` WRITE;
/*!40000 ALTER TABLE `evaluer_ecue` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluer_ecue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faire_stage`
--

DROP TABLE IF EXISTS `faire_stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faire_stage` (
  `num_etd` int NOT NULL,
  `id_entr` int NOT NULL,
  `intitule_stage` varchar(255) DEFAULT NULL,
  `description_stage` text NOT NULL,
  `type_stage` enum('stage_fin_etude','stage_immersion') DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `date_env` date DEFAULT NULL,
  `nom_tuteur` varchar(100) NOT NULL,
  `poste_tuteur` varchar(60) NOT NULL,
  `telephone_tuteur` varchar(30) NOT NULL,
  `email_tuteur` varchar(130) NOT NULL,
  PRIMARY KEY (`num_etd`,`id_entr`),
  KEY `id_entr` (`id_entr`),
  CONSTRAINT `fk_faire_stage_entreprise` FOREIGN KEY (`id_entr`) REFERENCES `entreprise` (`id_entr`) ON DELETE CASCADE,
  CONSTRAINT `fk_faire_stage_etudiant` FOREIGN KEY (`num_etd`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faire_stage`
--

LOCK TABLES `faire_stage` WRITE;
/*!40000 ALTER TABLE `faire_stage` DISABLE KEYS */;
INSERT INTO `faire_stage` VALUES (1,17,'IA & DATA SCIENCE','Stage d\'apprentissage ','stage_fin_etude','2025-06-07','2025-06-07','2025-05-25','KOUAME Philippe','Data scientist','0707019478','kouamP@gmail.com'),(1,18,'IA & DATA SCIENCE','Stage d\'observation','stage_immersion','2025-06-01','2025-06-05','2025-05-25','KOUAME Philippe','Data scientist','2245010130','kouamP@gmail.com'),(1,19,'IA & DATA SCIENCE','sds','stage_immersion','2025-05-31','2025-05-29','2025-05-25','ds','ds','0707019478','kouamP@gmail.com');
/*!40000 ALTER TABLE `faire_stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fonction`
--

DROP TABLE IF EXISTS `fonction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fonction` (
  `id_fonction` int NOT NULL,
  `nom_fonction` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_fonction`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fonction`
--

LOCK TABLES `fonction` WRITE;
/*!40000 ALTER TABLE `fonction` DISABLE KEYS */;
INSERT INTO `fonction` VALUES (1,'Directeur de filière'),(2,'Responsable de licence'),(3,'Responsable de master'),(4,'Enseignant vacataire');
/*!40000 ALTER TABLE `fonction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade`
--

DROP TABLE IF EXISTS `grade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade` (
  `id_grd` int NOT NULL,
  `nom_grd` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_grd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade`
--

LOCK TABLES `grade` WRITE;
/*!40000 ALTER TABLE `grade` DISABLE KEYS */;
INSERT INTO `grade` VALUES (1,'Professeur d\'université'),(2,'Maître de conférences'),(3,'Chargé d\'enseignement'),(4,'Doctorant enseignant');
/*!40000 ALTER TABLE `grade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groupe_utilisateur`
--

DROP TABLE IF EXISTS `groupe_utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_utilisateur` (
  `id_gu` int NOT NULL,
  `lib_gu` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_gu`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groupe_utilisateur`
--

LOCK TABLES `groupe_utilisateur` WRITE;
/*!40000 ALTER TABLE `groupe_utilisateur` DISABLE KEYS */;
INSERT INTO `groupe_utilisateur` VALUES (1,'Étudiant'),(2,'Chargé de communication'),(3,'Responsable scolarité'),(4,'Secrétaire'),(5,'Enseignant sans responsabilité administrative'),(6,'Responsable niveau'),(7,'Responsable filière'),(8,'Administrateur plateforme'),(9,'Commission de validation');
/*!40000 ALTER TABLE `groupe_utilisateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inscrire`
--

DROP TABLE IF EXISTS `inscrire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inscrire` (
  `num_etd` int NOT NULL,
  `id_ac` int NOT NULL,
  `id_niv_etd` int NOT NULL,
  `date_insc` date DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`num_etd`,`id_ac`,`id_niv_etd`),
  KEY `id_ac` (`id_ac`),
  KEY `id_niv_etd` (`id_niv_etd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inscrire`
--

LOCK TABLES `inscrire` WRITE;
/*!40000 ALTER TABLE `inscrire` DISABLE KEYS */;
/*!40000 ALTER TABLE `inscrire` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id_message` int NOT NULL AUTO_INCREMENT,
  `expediteur_id` int DEFAULT NULL,
  `destinataire_id` int DEFAULT NULL,
  `destinataire_type` enum('individuel','groupe','commission','étudiant','tous') DEFAULT 'individuel',
  `objet` varchar(255) DEFAULT NULL,
  `contenu` text NOT NULL,
  `type_message` enum('chat','notification','email','rappel','systeme','alerte') NOT NULL,
  `categorie` enum('memoire','evaluation','deadline','commission','general') DEFAULT 'general',
  `priorite` enum('basse','normale','haute','urgente') DEFAULT 'normale',
  `statut` enum('brouillon','envoyé','lu','archivé','supprimé') DEFAULT 'envoyé',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_envoi` timestamp NULL DEFAULT NULL,
  `date_lecture` timestamp NULL DEFAULT NULL,
  `date_rappel` timestamp NULL DEFAULT NULL,
  `rapport_id` int DEFAULT NULL,
  `message_reference_id` int DEFAULT NULL,
  PRIMARY KEY (`id_message`),
  KEY `message_reference_id` (`message_reference_id`),
  KEY `idx_expediteur` (`expediteur_id`),
  KEY `idx_destinataire` (`destinataire_id`),
  KEY `idx_type` (`type_message`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date_creation` (`date_creation`),
  KEY `idx_rapport` (`rapport_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,3,NULL,'commission',NULL,'Bonjour','chat','commission','normale','envoyé','2025-05-25 23:26:24',NULL,NULL,NULL,49,NULL),(2,1,NULL,'commission',NULL,'Comment allez-vous?','chat','commission','normale','envoyé','2025-05-25 23:26:42',NULL,NULL,NULL,49,NULL),(3,2,NULL,'commission',NULL,'Bof inh et rapport là?','chat','commission','normale','envoyé','2025-05-25 23:27:03',NULL,NULL,NULL,49,NULL),(4,3,NULL,'commission',NULL,'Perso je pense que ça peut aller pour le rapport, qu\'en pensez vous?','chat','commission','normale','envoyé','2025-05-25 23:28:18',NULL,NULL,NULL,49,NULL),(5,2,NULL,'commission',NULL,'Je pense que non inhh\r\n','chat','commission','normale','envoyé','2025-05-25 23:28:45',NULL,NULL,NULL,49,NULL),(6,1,NULL,'commission',NULL,'Mais si pourquoi penses-tu que non?','chat','commission','normale','envoyé','2025-05-25 23:29:43',NULL,NULL,NULL,49,NULL),(7,1,NULL,'commission',NULL,'Genre','chat','commission','normale','envoyé','2025-05-25 23:30:47',NULL,NULL,NULL,49,NULL),(8,2,NULL,'commission',NULL,'Boff','chat','commission','normale','envoyé','2025-05-25 23:32:42',NULL,NULL,NULL,49,NULL),(9,1,NULL,'commission',NULL,'humm','chat','commission','normale','envoyé','2025-05-25 23:32:51',NULL,NULL,NULL,49,NULL),(10,1,NULL,'commission',NULL,'qu\'en penses tu','chat','commission','normale','envoyé','2025-05-25 23:33:08',NULL,NULL,NULL,49,NULL),(11,2,NULL,'commission',NULL,'ahh','chat','commission','normale','envoyé','2025-05-25 23:33:16',NULL,NULL,NULL,49,NULL),(12,1,NULL,'commission',NULL,'djan','chat','commission','normale','envoyé','2025-05-25 23:33:28',NULL,NULL,NULL,49,NULL),(13,1,NULL,'commission',NULL,'boff','chat','commission','normale','envoyé','2025-05-25 23:33:45',NULL,NULL,NULL,49,NULL),(14,2,NULL,'commission',NULL,'tchai','chat','commission','normale','envoyé','2025-05-25 23:34:21',NULL,NULL,NULL,49,NULL),(15,1,NULL,'commission',NULL,'Ahahaha','chat','commission','normale','envoyé','2025-05-25 23:34:46',NULL,NULL,NULL,49,NULL);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `niveau_acces_donnees`
--

DROP TABLE IF EXISTS `niveau_acces_donnees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `niveau_acces_donnees` (
  `id_niveau_acces` int NOT NULL,
  `lib_niveau_acces` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_niveau_acces`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `niveau_acces_donnees`
--

LOCK TABLES `niveau_acces_donnees` WRITE;
/*!40000 ALTER TABLE `niveau_acces_donnees` DISABLE KEYS */;
INSERT INTO `niveau_acces_donnees` VALUES (1,'Accès limité aux données personnelles'),(2,'Accès aux données des étudiants'),(3,'Accès aux données administratives'),(4,'Accès au suivi pédagogique'),(5,'Accès complet aux données de validation'),(6,'Accès complet administrateur');
/*!40000 ALTER TABLE `niveau_acces_donnees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `niveau_approbation`
--

DROP TABLE IF EXISTS `niveau_approbation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `niveau_approbation` (
  `id_approb` int NOT NULL AUTO_INCREMENT,
  `lib_approb` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_approb`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `niveau_approbation`
--

LOCK TABLES `niveau_approbation` WRITE;
/*!40000 ALTER TABLE `niveau_approbation` DISABLE KEYS */;
INSERT INTO `niveau_approbation` VALUES (7,'Validé'),(8,'2');
/*!40000 ALTER TABLE `niveau_approbation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `niveau_etude`
--

DROP TABLE IF EXISTS `niveau_etude`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `niveau_etude` (
  `id_niv_etd` int NOT NULL AUTO_INCREMENT,
  `lib_niv_etd` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_niv_etd`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `niveau_etude`
--

LOCK TABLES `niveau_etude` WRITE;
/*!40000 ALTER TABLE `niveau_etude` DISABLE KEYS */;
INSERT INTO `niveau_etude` VALUES (1,'Licence 1'),(2,'Licence 2'),(3,'Licence 3'),(4,'Master 1'),(5,'Master 2');
/*!40000 ALTER TABLE `niveau_etude` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `est_lue` tinyint(1) DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_ibfk_1` (`id_utilisateur`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `occuper`
--

DROP TABLE IF EXISTS `occuper`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `occuper` (
  `id_fonction` int NOT NULL,
  `id_ens` int NOT NULL,
  `date_occup` date DEFAULT NULL,
  PRIMARY KEY (`id_fonction`,`id_ens`),
  KEY `id_ens` (`id_ens`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `occuper`
--

LOCK TABLES `occuper` WRITE;
/*!40000 ALTER TABLE `occuper` DISABLE KEYS */;
INSERT INTO `occuper` VALUES (1,1,'2025-05-20'),(3,2,'2025-05-19'),(4,3,'2025-05-19'),(3,4,'2023-01-01'),(2,5,'2023-01-01'),(2,6,'2023-01-01'),(2,7,'2023-01-01'),(4,8,'2023-01-01'),(4,9,'2023-01-01'),(4,10,'2023-01-01'),(2,20,'2025-07-06'),(2,13,'2025-05-31'),(2,14,'2025-05-31'),(2,15,'2025-05-31'),(2,16,'2025-05-31'),(4,21,'2025-06-11'),(1,22,'2025-06-01'),(3,23,'2025-06-01'),(2,24,'2025-06-18');
/*!40000 ALTER TABLE `occuper` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement_reglement`
--

DROP TABLE IF EXISTS `paiement_reglement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiement_reglement` (
  `id_paiement` int NOT NULL AUTO_INCREMENT,
  `id_reglement` int NOT NULL,
  `numero_recu` varchar(50) NOT NULL,
  `montant_paye` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL DEFAULT (curdate()),
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `numero_recu` (`numero_recu`),
  KEY `id_reglement` (`id_reglement`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_reglement`
--

LOCK TABLES `paiement_reglement` WRITE;
/*!40000 ALTER TABLE `paiement_reglement` DISABLE KEYS */;
INSERT INTO `paiement_reglement` VALUES (17,8,'REC-20257935FE',475000.00,'2025-05-28'),(18,8,'REC-2025055A94',9999999.00,'2025-05-28'),(19,8,'REC-2025AA0BD7',9999999.00,'2025-05-28'),(20,8,'REC-2025663CFC',7000000.00,'2025-05-28'),(21,8,'REC-20253DC80B',72524956.00,'2025-05-28'),(22,8,'REC-202594B03D',45.00,'2025-05-28'),(23,10,'REC-2025E010BD',75000.00,'2025-05-28');
/*!40000 ALTER TABLE `paiement_reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participants`
--

DROP TABLE IF EXISTS `participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `participants` (
  `id_utilisateur` int NOT NULL,
  `reunion_id` int NOT NULL,
  `status` enum('en attente','acceptée','refusée') NOT NULL DEFAULT 'en attente',
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_participant` (`reunion_id`,`id_utilisateur`),
  KEY `idx_participants_status` (`status`),
  KEY `participants_ibfk_2` (`id_utilisateur`),
  CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `reunions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `participants_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participants`
--

LOCK TABLES `participants` WRITE;
/*!40000 ALTER TABLE `participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personnel_administratif`
--

DROP TABLE IF EXISTS `personnel_administratif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnel_administratif` (
  `id_personnel_adm` int NOT NULL AUTO_INCREMENT,
  `nom_personnel_adm` varchar(20) DEFAULT NULL,
  `prenoms_personnel_adm` varchar(100) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `date_naissance_personnel_adm` date DEFAULT NULL,
  `tel_personnel_adm` varchar(20) DEFAULT NULL,
  `email_personnel_adm` varchar(100) DEFAULT NULL,
  `mdp_personnel_adm` varchar(100) DEFAULT NULL,
  `sexe_personnel_adm` enum('Homme','Femme') NOT NULL,
  `photo_personnel_adm` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id_personnel_adm`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel_administratif`
--

LOCK TABLES `personnel_administratif` WRITE;
/*!40000 ALTER TABLE `personnel_administratif` DISABLE KEYS */;
INSERT INTO `personnel_administratif` VALUES (1,'SÉRI','Marie Christine',NULL,'1991-03-04','0783124545','seriMC@univ.edu','df8f597da9c13895791d3fdb81cb9112877b12ec7b26c9752126bc34f182c38f','Femme',''),(2,'YAH','Christine',NULL,'1993-10-23','0710124524','yah@univ.edu','1df83c4230a9a05ef403b6e82866282b3236e4409ac95892a6e9f9a2f4bf09b1','Femme',''),(3,'KAMÉNAN','Durand','2025-06-01','1988-11-12','0884745210','durandK@univ.edu','4ec476c2d7b6bd0f316afbb2c0cdae3f8a816da2b15e7a2477d1155b0d4dde05','Homme',''),(8,'Sara','Evangéline','2025-06-20',NULL,NULL,'saraE@univ.ci',NULL,'Femme',NULL),(9,'KOUADIO','Rostand','2025-06-21',NULL,NULL,'kouaR@gmail.com',NULL,'Homme',NULL);
/*!40000 ALTER TABLE `personnel_administratif` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pister`
--

DROP TABLE IF EXISTS `pister`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pister` (
  `id_utilisateur` int NOT NULL,
  `id_traitement` int NOT NULL,
  `date_piste` date DEFAULT NULL,
  `heure_piste` time DEFAULT NULL,
  `acceder` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`,`id_traitement`),
  KEY `id_traitement` (`id_traitement`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pister`
--

LOCK TABLES `pister` WRITE;
/*!40000 ALTER TABLE `pister` DISABLE KEYS */;
/*!40000 ALTER TABLE `pister` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posseder`
--

DROP TABLE IF EXISTS `posseder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posseder` (
  `id_util` int NOT NULL,
  `id_gu` int NOT NULL,
  `date_poss` date DEFAULT NULL,
  PRIMARY KEY (`id_util`,`id_gu`),
  KEY `id_gu` (`id_gu`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posseder`
--

LOCK TABLES `posseder` WRITE;
/*!40000 ALTER TABLE `posseder` DISABLE KEYS */;
INSERT INTO `posseder` VALUES (0,6,'2025-06-01'),(2,9,'2025-05-19'),(3,9,'2025-05-19'),(4,9,'2025-05-01'),(5,6,'2025-05-01'),(6,6,'2025-05-01'),(7,6,'2025-05-01'),(8,5,'2025-05-01'),(9,5,'2025-05-01'),(10,5,'2025-05-01'),(11,2,'2025-05-19'),(12,4,NULL),(13,3,NULL),(14,1,'2025-05-21'),(15,1,'2025-05-01'),(16,1,'2025-05-01'),(17,1,NULL),(18,1,'2025-05-01'),(1,8,'2025-05-20'),(0,4,'2025-06-01'),(19,5,'2025-06-01'),(20,3,'2025-06-01'),(21,3,'2025-06-01'),(22,5,'2025-06-01'),(23,5,'2025-06-01');
/*!40000 ALTER TABLE `posseder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rappels`
--

DROP TABLE IF EXISTS `rappels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rappels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `date_rappel` datetime NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reunion_id` (`reunion_id`),
  CONSTRAINT `rappels_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `reunions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rappels`
--

LOCK TABLES `rappels` WRITE;
/*!40000 ALTER TABLE `rappels` DISABLE KEYS */;
/*!40000 ALTER TABLE `rappels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rapport_etudiant`
--

DROP TABLE IF EXISTS `rapport_etudiant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rapport_etudiant` (
  `id_rapport_etd` int NOT NULL AUTO_INCREMENT,
  `num_etd` int DEFAULT NULL,
  `nom_rapport` varchar(100) DEFAULT NULL,
  `date_rapport` date DEFAULT NULL,
  `theme_memoire` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `statut_rapport` enum('En attente d''approbation','Approuvé','Désapprouvé','En attente de validation','Validé','Rejeté','Non soumis') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `fichier_rapport` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id_rapport_etd`),
  KEY `num_etd` (`num_etd`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rapport_etudiant`
--

LOCK TABLES `rapport_etudiant` WRITE;
/*!40000 ALTER TABLE `rapport_etudiant` DISABLE KEYS */;
INSERT INTO `rapport_etudiant` VALUES (49,1,'GOMEZ_Ange Axel_2025-05-25','2025-05-25','L\'informatique c\'est la vie','Validé','assets/uploads/rapports/GOMEZ_Ange Axel_2025-05-25.pdf');
/*!40000 ALTER TABLE `rapport_etudiant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rattacher`
--

DROP TABLE IF EXISTS `rattacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rattacher` (
  `id_gu` int NOT NULL,
  `id_traitement` int NOT NULL,
  PRIMARY KEY (`id_gu`,`id_traitement`),
  KEY `id_traitement` (`id_traitement`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rattacher`
--

LOCK TABLES `rattacher` WRITE;
/*!40000 ALTER TABLE `rattacher` DISABLE KEYS */;
INSERT INTO `rattacher` VALUES (1,18),(1,19),(1,20),(1,21),(1,24),(2,10),(2,12),(2,14),(2,16),(2,17),(3,3),(3,10),(3,12),(3,14),(3,15),(3,23),(4,10),(4,12),(4,14),(5,10),(5,12),(6,10),(6,12),(7,10),(7,12),(8,1),(8,2),(8,4),(8,5),(8,6),(8,7),(8,10),(8,11),(8,12),(8,13),(8,22),(9,1),(9,4),(9,5),(9,6),(9,7),(9,8),(9,9),(9,10),(9,12);
/*!40000 ALTER TABLE `rattacher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reglement`
--

DROP TABLE IF EXISTS `reglement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reglement` (
  `id_reglement` int NOT NULL AUTO_INCREMENT,
  `num_etd` int NOT NULL,
  `id_ac` int NOT NULL,
  `id_niv_etd` int NOT NULL,
  `numero_reglement` varchar(50) NOT NULL,
  `montant_a_payer` decimal(10,2) NOT NULL,
  `total_paye` decimal(10,2) DEFAULT '0.00',
  `statut` enum('Non payé','Partiel','Payé') DEFAULT 'Non payé',
  `date_reglement` date NOT NULL DEFAULT (curdate()),
  PRIMARY KEY (`id_reglement`),
  UNIQUE KEY `numero_reglement` (`numero_reglement`),
  KEY `num_etd` (`num_etd`),
  KEY `id_niv_etd` (`id_niv_etd`),
  KEY `id_ac` (`id_ac`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reglement`
--

LOCK TABLES `reglement` WRITE;
/*!40000 ALTER TABLE `reglement` DISABLE KEYS */;
INSERT INTO `reglement` VALUES (8,1,2024,3,'REG-20250001',99999999.99,99999999.00,'Partiel','2025-05-28'),(9,2,2024,0,'REG-20250002',0.00,0.00,'Non payé','2025-05-28'),(10,2,2024,3,'REG-20250003',45000000.00,75000.00,'Partiel','2025-05-28');
/*!40000 ALTER TABLE `reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rendre`
--

DROP TABLE IF EXISTS `rendre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rendre` (
  `id_cr` int NOT NULL,
  `id_ens` int NOT NULL,
  `date_env` date DEFAULT NULL,
  PRIMARY KEY (`id_cr`,`id_ens`),
  KEY `id_ens` (`id_ens`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rendre`
--

LOCK TABLES `rendre` WRITE;
/*!40000 ALTER TABLE `rendre` DISABLE KEYS */;
INSERT INTO `rendre` VALUES (11,4,'2025-06-01');
/*!40000 ALTER TABLE `rendre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reset_password`
--

DROP TABLE IF EXISTS `reset_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reset_password` (
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_password`
--

LOCK TABLES `reset_password` WRITE;
/*!40000 ALTER TABLE `reset_password` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_password` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `responsable_compte_rendu`
--

DROP TABLE IF EXISTS `responsable_compte_rendu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `responsable_compte_rendu` (
  `id_ens` int NOT NULL,
  `actif` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_ens`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `responsable_compte_rendu`
--

LOCK TABLES `responsable_compte_rendu` WRITE;
/*!40000 ALTER TABLE `responsable_compte_rendu` DISABLE KEYS */;
INSERT INTO `responsable_compte_rendu` VALUES (1,0),(2,0),(3,0),(4,1);
/*!40000 ALTER TABLE `responsable_compte_rendu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reunions`
--

DROP TABLE IF EXISTS `reunions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reunions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `type` enum('normale','urgente') NOT NULL,
  `date_reunion` date NOT NULL,
  `heure_debut` time NOT NULL,
  `duree` decimal(3,1) NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `description` text,
  `rapports_count` int DEFAULT '0',
  `status` enum('programmée','en cours','terminée','annulée') NOT NULL DEFAULT 'programmée',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reunions_date` (`date_reunion`),
  KEY `idx_reunions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reunions`
--

LOCK TABLES `reunions` WRITE;
/*!40000 ALTER TABLE `reunions` DISABLE KEYS */;
/*!40000 ALTER TABLE `reunions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sauvegardes`
--

DROP TABLE IF EXISTS `sauvegardes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sauvegardes` (
  `id_sauvegarde` int NOT NULL AUTO_INCREMENT,
  `nom_sauvegarde` varchar(255) NOT NULL,
  `description` text,
  `nom_fichier` varchar(255) NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `taille_fichier` bigint DEFAULT NULL,
  PRIMARY KEY (`id_sauvegarde`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sauvegardes`
--

LOCK TABLES `sauvegardes` WRITE;
/*!40000 ALTER TABLE `sauvegardes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sauvegardes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semestre`
--

DROP TABLE IF EXISTS `semestre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `semestre` (
  `id_semestre` int NOT NULL AUTO_INCREMENT,
  `lib_semestre` varchar(12) NOT NULL,
  `id_niv_etd` int NOT NULL,
  PRIMARY KEY (`id_semestre`),
  KEY `fk_niv_etd` (`id_niv_etd`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semestre`
--

LOCK TABLES `semestre` WRITE;
/*!40000 ALTER TABLE `semestre` DISABLE KEYS */;
INSERT INTO `semestre` VALUES (1,'Semestre 1',1),(2,'Semestre 2',1),(3,'Semestre 3',2),(4,'Semestre 4',2),(5,'Semestre 5',3),(6,'Semestre 6',3),(7,'Semestre 7',4),(8,'Semestre 8',4),(9,'Semestre 9',5);
/*!40000 ALTER TABLE `semestre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `specialite`
--

DROP TABLE IF EXISTS `specialite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specialite` (
  `id_spe` int NOT NULL,
  `lib_spe` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_spe`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `specialite`
--

LOCK TABLES `specialite` WRITE;
/*!40000 ALTER TABLE `specialite` DISABLE KEYS */;
INSERT INTO `specialite` VALUES (1,'Informatique'),(2,'Mathématiques Appliquées'),(3,'Réseaux et Télécommunications'),(4,'Intelligence Artificielle'),(5,'Génie Logiciel'),(6,'Cybersécurité'),(7,'Statistique et Décisionnel'),(8,'Big Data');
/*!40000 ALTER TABLE `specialite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statut_jury`
--

DROP TABLE IF EXISTS `statut_jury`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statut_jury` (
  `id_jury` int NOT NULL,
  `lib_jury` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_jury`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statut_jury`
--

LOCK TABLES `statut_jury` WRITE;
/*!40000 ALTER TABLE `statut_jury` DISABLE KEYS */;
/*!40000 ALTER TABLE `statut_jury` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarif_inscription`
--

DROP TABLE IF EXISTS `tarif_inscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarif_inscription` (
  `id_tarif` int NOT NULL AUTO_INCREMENT,
  `id_ac` int NOT NULL,
  `id_niv_etd` int NOT NULL,
  `montant_tarif` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_tarif`),
  UNIQUE KEY `unique_tarif` (`id_ac`,`id_niv_etd`),
  KEY `id_niv_etd` (`id_niv_etd`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarif_inscription`
--

LOCK TABLES `tarif_inscription` WRITE;
/*!40000 ALTER TABLE `tarif_inscription` DISABLE KEYS */;
INSERT INTO `tarif_inscription` VALUES (1,1,1,150000.00),(2,1,2,200000.00),(3,1,3,250000.00),(4,1,4,300000.00),(5,1,5,350000.00),(6,2,1,130000.00),(7,2,2,180000.00),(8,2,3,230000.00),(9,2,4,280000.00),(10,2,5,330000.00);
/*!40000 ALTER TABLE `tarif_inscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traitement`
--

DROP TABLE IF EXISTS `traitement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traitement` (
  `id_traitement` int NOT NULL,
  `lib_traitement` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nom_traitement` varchar(130) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `classe_icone` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id_traitement`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traitement`
--

LOCK TABLES `traitement` WRITE;
/*!40000 ALTER TABLE `traitement` DISABLE KEYS */;
INSERT INTO `traitement` VALUES (1,'dashboard','Tableau de bord','fas fa-home'),(3,'evaluations_etudiants','Évaluations des étudiants','fas fa-pen-to-square'),(4,'analyses','Analyse des rapports étudiants','fa-solid fa-magnifying-glass-chart'),(5,'validations','Processus de validation','fas fa-check-circle'),(6,'consultations','Consultation des documents étudiants','fa-solid fa-file-lines'),(7,'decisions','Archivages des documents','fas fa-folder'),(8,'archives','Mes archives','fas fa-archive'),(9,'reunions','Planification des réunions','fas fa-calendar-check'),(10,'messages','Messagerie','fa-solid fa-inbox'),(11,'piste_audit','Piste d\'audit','fas fa-book-open-reader'),(12,'parameters','Paramètres','fas fa-cog'),(13,'parametres_generaux','Paramètres généraux','fas fa-gears'),(14,'etudiants','Étudiants','fa-solid fa-users'),(15,'inscriptions_etudiants','Inscriptions étudiants','fas fa-id-card'),(16,'suivis_des_decisions','Suivis des décisions','fas fa-list-check'),(17,'comptes_rendus','Consultations des comptes rendus','fas fa-file-alt'),(18,'soutenances','Candidature à la soutenance','fa fa-graduation-cap'),(19,'rapports','Gestion du rapport/mémoire','fa fa-file-alt'),(20,'reclamations','Gestions des réclamations','fas fa-bullhorn'),(21,'profils','Profils et informations','fa-solid fa-circle-info'),(22,'sauvegardes_et_restaurations','Sauvegardes et restauration des données','fas fa-cloud-upload-alt'),(2,'ressources_humaines','Gestion des ressources humaines','fa-solid fa-user-tie'),(23,'demandes_soutenances','Traitement des demandes de soutenance','fas fa-file-alt'),(24,'boites_messages','Boites de messages','fa-solid fa-inbox');
/*!40000 ALTER TABLE `traitement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_a_groupe`
--

DROP TABLE IF EXISTS `type_a_groupe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_a_groupe` (
  `id_tu` int NOT NULL,
  `id_gu` int NOT NULL,
  PRIMARY KEY (`id_tu`,`id_gu`),
  KEY `id_gu` (`id_gu`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_a_groupe`
--

LOCK TABLES `type_a_groupe` WRITE;
/*!40000 ALTER TABLE `type_a_groupe` DISABLE KEYS */;
INSERT INTO `type_a_groupe` VALUES (1,1),(2,5),(2,9),(3,6),(3,7),(3,8),(3,9),(4,2),(4,3),(4,4);
/*!40000 ALTER TABLE `type_a_groupe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_utilisateur`
--

DROP TABLE IF EXISTS `type_utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_utilisateur` (
  `id_tu` int NOT NULL,
  `lib_tu` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_utilisateur`
--

LOCK TABLES `type_utilisateur` WRITE;
/*!40000 ALTER TABLE `type_utilisateur` DISABLE KEYS */;
INSERT INTO `type_utilisateur` VALUES (1,'Étudiant'),(2,'Enseignant simple'),(3,'Enseignant administratif'),(4,'Personnel administratif ');
/*!40000 ALTER TABLE `type_utilisateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ue`
--

DROP TABLE IF EXISTS `ue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ue` (
  `id_ue` int NOT NULL,
  `lib_ue` varchar(100) DEFAULT NULL,
  `credit_ue` int DEFAULT NULL,
  `volume_horaire` int NOT NULL,
  `id_niv_etd` int NOT NULL,
  `id_semestre` int NOT NULL,
  `id_annee_academique` int NOT NULL,
  PRIMARY KEY (`id_ue`),
  KEY `fk_niv_etd` (`id_niv_etd`),
  KEY `fk_id_semestre` (`id_semestre`),
  KEY `fk_id_ac` (`id_annee_academique`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ue`
--

LOCK TABLES `ue` WRITE;
/*!40000 ALTER TABLE `ue` DISABLE KEYS */;
INSERT INTO `ue` VALUES (3501,'Analyse et conception à objet',6,60,3,5,2524),(2901,'Gestion financière',3,30,5,9,0),(2902,'Management de projet et intégration d\'application',6,60,5,9,0),(2903,'Audit informatique',3,30,5,9,0),(2904,'Entrepreunariat',2,20,5,9,0),(2905,'Multimedia mobile',3,30,5,9,0),(2906,'Ingenierie des exigences et veille technologique',3,30,5,9,0),(2907,'Mathématiques financières',3,30,5,9,0),(2908,'Anglais',2,20,5,9,0);
/*!40000 ALTER TABLE `ue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateur` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `login_utilisateur` varchar(50) DEFAULT NULL,
  `mdp_utilisateur` varchar(100) DEFAULT NULL,
  `statut_utilisateur` enum('Actif','Inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Inactif',
  `id_niveau_acces` int DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  KEY `fk_niveau_acces` (`id_niveau_acces`),
  CONSTRAINT `fk_niveau_acces` FOREIGN KEY (`id_niveau_acces`) REFERENCES `niveau_acces_donnees` (`id_niveau_acces`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur`
--

LOCK TABLES `utilisateur` WRITE;
/*!40000 ALTER TABLE `utilisateur` DISABLE KEYS */;
INSERT INTO `utilisateur` VALUES (1,'kouaB@univ.edu','e92f96f743c7bfbe187d586f17191e03ebb0fdde59771ca4dfbf7ab6aa7ca26d','Actif',NULL),(2,'brouP@univ.edu','4854286bb514f68044481d57dba9a2e566b79b4b6317f68c2e97b55197242800','Actif',NULL),(3,'wahM@univ.edu','d1ca26082383037ae8cd65eb6f56fbdd1dcb6c8b48e47bc937d2e26047c0c5ab','Actif',NULL),(4,'diarraM@univ.edu','83a4c6a74878358f75790333520b3f5463fb82e7d2e8e078a7bccac7c028493a','Actif',NULL),(5,'codjiaA@univ.edu','8610662eb326ba1125029b5fc34d50d1f0f6cf737ffe7edbed7ce003f8e43bf8','Actif',NULL),(6,'idaB@univ.edu','acec2454b5dcc5d741b13d5e703f252306663f107ba6e8ec3ceba3fa2d48e6b4','Actif',NULL),(7,'kouakouM@univ.edu','44abaac58f10d40e2c71635ff22b2b9d0f022705194bc6f15ea1ffa3f5a95b87','Actif',NULL),(8,'kouakouF@univ.edu','95d0c9dbe9524157bb22eded258711ff6e2a27c835a8717b263d9809a9ba2ed4','Actif',NULL),(9,'baillyB@univ.edu','d63539ffceb1864f36ac2104f8b92b9cb878c52eefd2f7019bdee0e0e9e70f41','Actif',NULL),(10,'bakayokoI@univ.edu','3409e586c8fc3b086f9432a784058123b77b65b5579bcc3f7f217fd5756734e5','Actif',NULL),(11,'seriMC@univ.edu','df8f597da9c13895791d3fdb81cb9112877b12ec7b26c9752126bc34f182c38f','Actif',NULL),(12,'yah@univ.edu','1df83c4230a9a05ef403b6e82866282b3236e4409ac95892a6e9f9a2f4bf09b1','Actif',NULL),(13,'durandK@univ.edu','4ec476c2d7b6bd0f316afbb2c0cdae3f8a816da2b15e7a2477d1155b0d4dde05','Actif',NULL),(14,'axelangegomez@gmail.com','37e8d160c2c1247cbb7cbb879080807a21743a4e0a68721ea6c7e624bf95b4ea','Actif',NULL),(15,'francky@etu.univ.edu','adb029bcca098ad49fb407588213f334f63ba6447fcb3890b6ff4b865c9904ee','Actif',NULL),(16,'yvesA@etu.univ.edu','f65ff48872eaae1ebbe688036fe211cfac21bee110f01612f6dee77f19514717','Actif',NULL),(17,'yaoAy@etu.univ.edu','343c8c1d3b9f5870aa26454463e3aa7c45c946632610c2ad665edb2bcc6755ac','Actif',NULL),(18,'emma.moreau@etu.univ.edu','3113cf7a13d42e2d98553b5d1e86554c7331bdc9d51a20d1b52edf09c7c66470','Inactif',NULL),(19,'yoliM@gmail.com','dc0be3be36050495fa1254b9e1ae017dec32db257d0ea1f9db3133d588bf57e2','Actif',NULL),(20,'saraE@univ.ci',NULL,'Actif',NULL),(21,'kouaR@gmail.com',NULL,'Inactif',NULL),(22,'konateN@univ.edu','ad09f5013f1b23b87864c833317138c80140c9913ec890d1af4d59c70b3a4d34','Actif',NULL),(23,'nindjinM@edu.ci','4f1d4cd882ce932af21632cd22b9923fd9aa31306ba3e00fd1cc018de6c895c0','Actif',NULL),(24,'toureM@univ.edu','2beb56a1e8d555ade0b6c72c589bbea3c650bd5c56af3a7fb16d9a2893b11113','Actif',NULL);
/*!40000 ALTER TABLE `utilisateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateur_type_utilisateur`
--

DROP TABLE IF EXISTS `utilisateur_type_utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateur_type_utilisateur` (
  `id_utilisateur` int NOT NULL,
  `id_tu` int NOT NULL,
  `date_attribution` date DEFAULT (curdate()),
  PRIMARY KEY (`id_utilisateur`,`id_tu`),
  KEY `id_tu` (`id_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur_type_utilisateur`
--

LOCK TABLES `utilisateur_type_utilisateur` WRITE;
/*!40000 ALTER TABLE `utilisateur_type_utilisateur` DISABLE KEYS */;
INSERT INTO `utilisateur_type_utilisateur` VALUES (0,3,'2025-06-01'),(0,4,'2025-06-01'),(1,3,'2025-05-20'),(2,3,'2025-05-19'),(3,3,'2025-05-19'),(4,3,'2025-05-04'),(5,3,'2025-05-04'),(6,3,'2025-05-04'),(7,3,'2025-05-04'),(8,2,'2025-05-04'),(9,2,'2025-05-04'),(10,2,'2025-05-04'),(11,4,'2025-05-04'),(12,4,'2025-05-04'),(13,4,'2025-05-04'),(14,1,'2025-05-21'),(15,1,'2025-05-04'),(16,1,'2025-05-04'),(17,1,'2025-05-04'),(18,1,'2025-05-04'),(19,2,'2025-06-01'),(20,4,'2025-06-01'),(21,4,'2025-06-01'),(22,2,'2025-06-02'),(23,2,'2025-06-02'),(24,2,'2025-06-01');
/*!40000 ALTER TABLE `utilisateur_type_utilisateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `valider`
--

DROP TABLE IF EXISTS `valider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `valider` (
  `id_ens` int NOT NULL,
  `id_rapport_etd` int NOT NULL,
  `date_validation` date DEFAULT NULL,
  `com_validation` text,
  `decision` varchar(25) NOT NULL,
  PRIMARY KEY (`id_ens`,`id_rapport_etd`),
  KEY `id_rapport_etd` (`id_rapport_etd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `valider`
--

LOCK TABLES `valider` WRITE;
/*!40000 ALTER TABLE `valider` DISABLE KEYS */;
INSERT INTO `valider` VALUES (1,49,'2025-05-25','Bon','Validé'),(4,49,'2025-05-25','bon','Validé'),(2,49,'2025-05-25','Han','Validé'),(3,49,'2025-05-25','Bon','Validé');
/*!40000 ALTER TABLE `valider` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-03  0:13:17
