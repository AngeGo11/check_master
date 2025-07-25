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
  `id_action` int NOT NULL AUTO_INCREMENT,
  `lib_action` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_action`)
) ENGINE=InnoDB AUTO_INCREMENT=1040 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `action`
--

LOCK TABLES `action` WRITE;
/*!40000 ALTER TABLE `action` DISABLE KEYS */;
INSERT INTO `action` VALUES (1,'Tentative connexion réussie'),(2,'Tentative connexion échouée'),(3,'Déconnexion'),(4,'Modification mot de passe'),(5,'Réinitialisation mot de passe'),(6,'Accès dashboard'),(7,'Accès module interdit'),(8,'Session expirée'),(9,'Activation compte'),(10,'Désactivation compte'),(11,'Ajout étudiant'),(12,'Modification étudiant'),(13,'Suppression étudiant'),(14,'Consultation profil étudiant'),(15,'Validation inscription étudiant'),(16,'Changement statut éligibilité'),(17,'Modification photo étudiant'),(18,'Export liste étudiants'),(19,'Import étudiants en masse'),(20,'Recherche étudiant'),(21,'Consultation historique étudiant'),(22,'Génération carte étudiant'),(23,'Modification promotion étudiant'),(24,'Changement niveau étudiant'),(25,'Archivage dossier étudiant'),(26,'Ajout enseignant'),(27,'Modification enseignant'),(28,'Suppression enseignant'),(29,'Consultation profil enseignant'),(30,'Attribution grade enseignant'),(31,'Modification grade enseignant'),(32,'Attribution fonction enseignant'),(33,'Modification fonction enseignant'),(34,'Attribution spécialité enseignant'),(35,'Modification photo enseignant'),(36,'Export liste enseignants'),(37,'Consultation planning enseignant'),(38,'Attribution responsabilité commission'),(39,'Révocation responsabilité commission'),(40,'Modification coordonnées enseignant'),(41,'Ajout personnel administratif'),(42,'Modification personnel administratif'),(43,'Suppression personnel administratif'),(44,'Consultation profil personnel'),(45,'Modification photo personnel'),(46,'Attribution droits personnel'),(47,'Modification droits personnel'),(48,'Export liste personnel'),(49,'Modification coordonnées personnel'),(50,'Changement statut personnel'),(51,'Ajout UE'),(52,'Modification UE'),(53,'Suppression UE'),(54,'Consultation UE'),(55,'Attribution enseignant UE'),(56,'Modification enseignant UE'),(57,'Ajout ECUE'),(58,'Modification ECUE'),(59,'Suppression ECUE'),(60,'Consultation ECUE'),(61,'Attribution enseignant ECUE'),(62,'Génération code UE'),(63,'Export maquette pédagogique'),(64,'Import maquette pédagogique'),(65,'Validation maquette'),(66,'Consultation planning UE'),(67,'Modification volume horaire'),(68,'Modification crédits'),(69,'Recherche UE/ECUE'),(70,'Duplication UE'),(71,'Dépôt rapport étudiant'),(72,'Modification rapport étudiant'),(73,'Suppression rapport étudiant'),(74,'Téléchargement rapport'),(75,'Consultation rapport'),(76,'Validation rapport par enseignant'),(77,'Rejet rapport par enseignant'),(78,'Approbation rapport par administration'),(79,'Désapprobation rapport par administration'),(80,'Partage rapport avec commission'),(81,'Commentaire sur rapport'),(82,'Consultation historique rapport'),(83,'Export rapport en PDF'),(84,'Recherche rapport'),(85,'Archivage rapport'),(86,'Restauration rapport'),(87,'Notification auteur rapport'),(88,'Changement statut rapport'),(89,'Attribution rapport à enseignant'),(90,'Consultation statistiques rapports'),(91,'Saisie évaluation UE'),(92,'Modification évaluation UE'),(93,'Suppression évaluation UE'),(94,'Consultation évaluation UE'),(95,'Saisie évaluation ECUE'),(96,'Modification évaluation ECUE'),(97,'Suppression évaluation ECUE'),(98,'Consultation évaluation ECUE'),(99,'Validation relevé notes'),(100,'Export relevé notes'),(101,'Import notes en masse'),(102,'Calcul moyenne générale'),(103,'Génération bulletin'),(104,'Consultation historique notes'),(105,'Correction note erronée'),(106,'Soumission demande soutenance'),(107,'Modification demande soutenance'),(108,'Annulation demande soutenance'),(109,'Traitement demande soutenance'),(110,'Acceptation demande soutenance'),(111,'Rejet demande soutenance'),(112,'Consultation demande soutenance'),(113,'Export liste demandes'),(114,'Recherche demande'),(115,'Notification étudiant demande'),(116,'Programmation réunion'),(117,'Modification réunion'),(118,'Annulation réunion'),(119,'Consultation réunion'),(120,'Participation réunion'),(121,'Ajout participant réunion'),(122,'Suppression participant réunion'),(123,'Envoi invitation réunion'),(124,'Confirmation participation'),(125,'Refus participation'),(126,'Téléchargement document réunion'),(127,'Ajout document réunion'),(128,'Suppression document réunion'),(129,'Génération rapport réunion'),(130,'Export planning réunions'),(131,'Création compte rendu'),(132,'Modification compte rendu'),(133,'Suppression compte rendu'),(134,'Consultation compte rendu'),(135,'Téléchargement compte rendu'),(136,'Envoi compte rendu par email'),(137,'Partage compte rendu'),(138,'Validation compte rendu'),(139,'Archivage compte rendu'),(140,'Export compte rendu'),(141,'Envoi message'),(142,'Lecture message'),(143,'Réponse message'),(144,'Transfert message'),(145,'Suppression message'),(146,'Archivage message'),(147,'Recherche message'),(148,'Marquer message comme lu'),(149,'Marquer message comme non lu'),(150,'Notification nouveau message'),(151,'Envoi message groupé'),(152,'Création brouillon'),(153,'Modification brouillon'),(154,'Sauvegarde brouillon'),(155,'Consultation historique messages'),(156,'Création règlement étudiant'),(157,'Modification règlement'),(158,'Suppression règlement'),(159,'Consultation règlement'),(160,'Enregistrement paiement espèces'),(161,'Enregistrement paiement chèque'),(162,'Annulation paiement'),(163,'Modification paiement'),(164,'Génération reçu paiement'),(165,'Export état paiements'),(166,'Recherche paiement'),(167,'Consultation historique paiements'),(168,'Validation inscription'),(169,'Annulation inscription'),(170,'Modification tarifs'),(171,'Configuration frais inscription'),(172,'Export relevé financier'),(173,'Relance paiement'),(174,'Calcul reste à payer'),(175,'Génération état financier'),(176,'Ajout entreprise'),(177,'Modification entreprise'),(178,'Suppression entreprise'),(179,'Consultation entreprise'),(180,'Enregistrement stage'),(181,'Modification stage'),(182,'Fin stage'),(183,'Consultation stage'),(184,'Export liste stages'),(185,'Recherche entreprise'),(186,'Soumission réclamation'),(187,'Modification réclamation'),(188,'Suppression réclamation'),(189,'Traitement réclamation'),(190,'Clôture réclamation'),(191,'Consultation réclamation'),(192,'Réponse réclamation'),(193,'Escalade réclamation'),(194,'Export réclamations'),(195,'Recherche réclamation'),(196,'Archivage document'),(197,'Consultation archive'),(198,'Restauration archive'),(199,'Suppression archive'),(200,'Téléchargement archive'),(201,'Recherche archive'),(202,'Export archives'),(203,'Compression archives'),(204,'Indexation archive'),(205,'Vérification intégrité archive'),(206,'Sauvegarde base de données'),(207,'Restauration base de données'),(208,'Export base de données'),(209,'Import base de données'),(210,'Nettoyage logs système'),(211,'Configuration système'),(212,'Modification paramètres'),(213,'Gestion utilisateurs'),(214,'Attribution permissions'),(215,'Révocation permissions'),(216,'Consultation logs erreur'),(217,'Consultation statistiques'),(218,'Génération rapport activité'),(219,'Maintenance système'),(220,'Mise à jour système'),(221,'Consultation piste audit'),(222,'Export piste audit'),(223,'Configuration notifications'),(224,'Test fonctionnalités'),(225,'Monitoring système'),(226,'Ajout promotion'),(227,'Modification promotion'),(228,'Suppression promotion'),(229,'Consultation promotion'),(230,'Ajout niveau étude'),(231,'Modification niveau étude'),(232,'Suppression niveau étude'),(233,'Configuration semestres'),(234,'Modification semestres'),(235,'Export structure pédagogique'),(236,'Création année académique'),(237,'Modification année académique'),(238,'Clôture année académique'),(239,'Activation année académique'),(240,'Consultation année académique'),(241,'Configuration tarifs année'),(242,'Export données année'),(243,'Archivage année académique'),(244,'Statistiques année académique'),(245,'Transition nouvelle année'),(246,'Envoi notification système'),(247,'Envoi email automatique'),(248,'Envoi SMS notification'),(249,'Publication annonce'),(250,'Modification annonce'),(251,'Suppression annonce'),(252,'Envoi rappel deadline'),(253,'Notification urgente'),(254,'Diffusion information'),(255,'Gestion alertes système'),(256,'Maintenance table action'),(257,'Consultation aide système'),(258,'Export manuel données'),(259,'Import manuel données'),(260,'Test intégrité données'),(300,'Accès tableau de bord'),(301,'Accès gestion ressources humaines'),(302,'Accès évaluations étudiants'),(303,'Accès analyse rapports étudiants'),(304,'Accès processus validation'),(305,'Accès consultation documents étudiants'),(306,'Accès archivage documents'),(307,'Accès mes archives'),(308,'Accès planification réunions'),(309,'Accès messagerie'),(310,'Accès piste audit'),(311,'Accès paramètres système'),(312,'Accès paramètres généraux'),(313,'Accès gestion étudiants'),(314,'Accès inscriptions étudiants'),(315,'Accès suivi décisions'),(316,'Accès comptes rendus'),(317,'Accès candidature soutenance'),(318,'Accès gestion rapports mémoires'),(319,'Accès réclamations étudiants'),(320,'Accès profils informations'),(321,'Accès sauvegardes restaurations'),(322,'Accès traitement demandes soutenance'),(323,'Accès boîte messagerie'),(324,'Accès gestion réclamations'),(400,'Accès liste enseignants'),(401,'Accès ajout enseignant'),(402,'Accès modification enseignant'),(403,'Accès liste personnel administratif'),(404,'Accès ajout personnel administratif'),(405,'Accès modification personnel administratif'),(406,'Accès attribution grades'),(407,'Accès attribution fonctions'),(408,'Accès attribution spécialités'),(410,'Accès liste étudiants'),(411,'Accès ajout étudiant'),(412,'Accès modification étudiant'),(413,'Accès validation éligibilité'),(414,'Accès historique étudiant'),(415,'Accès promotion étudiants'),(420,'Accès saisie notes UE'),(421,'Accès saisie notes ECUE'),(422,'Accès consultation notes'),(423,'Accès relevés notes'),(424,'Accès bulletins'),(425,'Accès moyennes générales'),(430,'Accès liste UE'),(431,'Accès ajout UE'),(432,'Accès modification UE'),(433,'Accès liste ECUE'),(434,'Accès ajout ECUE'),(435,'Accès modification ECUE'),(436,'Accès niveaux étude'),(437,'Accès semestres'),(438,'Accès promotions'),(439,'Accès années académiques'),(440,'Accès spécialités'),(441,'Accès grades'),(442,'Accès fonctions'),(450,'Accès liste rapports'),(451,'Accès dépôt rapport'),(452,'Accès modification rapport'),(453,'Accès validation rapports'),(454,'Accès approbation rapports'),(455,'Accès partage rapports'),(456,'Accès historique rapports'),(460,'Accès liste réunions'),(461,'Accès programmation réunion'),(462,'Accès modification réunion'),(463,'Accès participants réunions'),(464,'Accès documents réunions'),(465,'Accès comptes rendus réunions'),(470,'Accès boîte réception'),(471,'Accès envoi messages'),(472,'Accès messages envoyés'),(473,'Accès brouillons'),(474,'Accès messages archivés'),(475,'Accès notifications'),(480,'Accès gestion règlements'),(481,'Accès enregistrement paiements'),(482,'Accès historique paiements'),(483,'Accès génération reçus'),(484,'Accès états financiers'),(485,'Accès configuration tarifs'),(490,'Accès gestion stages'),(491,'Accès gestion entreprises'),(492,'Accès suivi stages'),(493,'Accès conventions stages'),(500,'Accès liste demandes soutenance'),(501,'Accès traitement demandes'),(502,'Accès validation demandes'),(503,'Accès planification soutenances'),(510,'Accès liste réclamations'),(511,'Accès traitement réclamations'),(512,'Accès suivi réclamations'),(513,'Accès réponses réclamations'),(520,'Accès archivage rapports'),(521,'Accès archivage comptes rendus'),(522,'Accès consultation archives'),(523,'Accès restauration archives'),(524,'Accès gestion archives'),(530,'Accès sauvegardes base'),(531,'Accès restaurations base'),(532,'Accès logs système'),(533,'Accès configuration système'),(534,'Accès gestion utilisateurs'),(535,'Accès gestion permissions'),(536,'Accès piste audit'),(537,'Accès monitoring système'),(538,'Accès maintenance système'),(540,'Accès statistiques étudiants'),(541,'Accès statistiques enseignants'),(542,'Accès statistiques financières'),(543,'Accès rapports activité'),(544,'Accès tableaux bord'),(545,'Accès analytics'),(550,'Accès paramètres généraux'),(551,'Accès configuration emails'),(552,'Accès configuration notifications'),(553,'Accès configuration modules'),(554,'Accès personnalisation interface'),(600,'Navigation menu principal'),(601,'Navigation sous-menu'),(602,'Retour page précédente'),(603,'Accès page aide'),(604,'Accès documentation'),(605,'Accès support technique'),(606,'Changement langue interface'),(607,'Changement thème interface'),(700,'Recherche globale'),(701,'Recherche étudiants'),(702,'Recherche enseignants'),(703,'Recherche rapports'),(704,'Recherche messages'),(705,'Filtrage par date'),(706,'Filtrage par statut'),(707,'Filtrage par type'),(708,'Export résultats recherche'),(800,'Consultation rapide profil'),(801,'Consultation rapide notes'),(802,'Consultation rapide planning'),(803,'Consultation rapide notifications'),(804,'Aperçu document'),(805,'Prévisualisation rapport'),(806,'Consultation historique connexions'),(900,'Sauvegarde session'),(901,'Restauration session'),(902,'Timeout session'),(903,'Extension session'),(904,'Nettoyage cache'),(905,'Actualisation données'),(906,'Synchronisation données'),(1000,'Tentative accès non autorisé'),(1001,'Accès avec permissions insuffisantes'),(1002,'Détection activité suspecte'),(1003,'Blocage temporaire compte'),(1004,'Déblocage compte'),(1005,'Changement niveau sécurité'),(1006,'Audit sécurité'),(1007,'Vérification intégrité'),(1008,'Scan vulnérabilités'),(1009,'Mise à jour sécurité'),(1010,'Modification frais inscription - échec (doublon)'),(1011,'Modification frais inscription - aucune modification'),(1012,'Modification frais inscription - niveau inexistant'),(1013,'Modification frais inscription - erreur DB'),(1014,'Modification frais inscription - données invalides'),(1015,'Modification frais inscription'),(1016,'Ajout action'),(1017,'Ajout entreprise'),(1018,'Ajout année académique'),(1019,'Ajout ECUE'),(1020,'Ajout UE'),(1021,'Ajout utilisateur'),(1022,'Ajout type utilisateur'),(1023,'Ajout groupe utilisateur'),(1024,'Ajout fonction'),(1025,'Ajout grade'),(1026,'Ajout spécialité'),(1027,'Ajout niveau accès'),(1028,'Ajout niveau approbation'),(1029,'Ajout niveau étude'),(1030,'Ajout statut jury'),(1031,'Ajout traitement'),(1032,'Modification frais inscription'),(1033,'Ajout frais inscription'),(1034,'Ajout responsable compte rendu'),(1035,'Ajout semestre'),(1036,'Ajout promotion'),(1037,'Rejet rapport par administration');
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
INSERT INTO `approuver` VALUES (11,50,'2025-06-25','A revoir'),(11,51,'2025-06-25','Bon rapport');
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
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_commission`
--

LOCK TABLES `chat_commission` WRITE;
/*!40000 ALTER TABLE `chat_commission` DISABLE KEYS */;
INSERT INTO `chat_commission` VALUES (1,3,49,1,'2025-05-25 23:26:24'),(2,1,49,2,'2025-05-25 23:26:42'),(3,2,49,3,'2025-05-25 23:27:03'),(4,3,49,4,'2025-05-25 23:28:18'),(5,2,49,5,'2025-05-25 23:28:45'),(6,1,49,6,'2025-05-25 23:29:43'),(7,1,49,7,'2025-05-25 23:30:47'),(8,2,49,8,'2025-05-25 23:32:42'),(9,1,49,9,'2025-05-25 23:32:51'),(10,1,49,10,'2025-05-25 23:33:08'),(11,2,49,11,'2025-05-25 23:33:16'),(12,1,49,12,'2025-05-25 23:33:28'),(13,1,49,13,'2025-05-25 23:33:45'),(14,2,49,14,'2025-05-25 23:34:21'),(15,1,49,15,'2025-05-25 23:34:46'),(16,1,50,43,'2025-06-08 14:45:14'),(17,2,50,44,'2025-06-08 14:46:07'),(18,3,50,45,'2025-06-08 14:46:51'),(19,4,50,46,'2025-06-08 14:47:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compte_rendu`
--

LOCK TABLES `compte_rendu` WRITE;
/*!40000 ALTER TABLE `compte_rendu` DISABLE KEYS */;
INSERT INTO `compte_rendu` VALUES (13,50,'Compte rendu du 2025-06-09','2025-06-09','assets/uploads/compte_rendu/GOMEZ_Ange Axel_2025-06-09.pdf');
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
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demande_soutenance`
--

LOCK TABLES `demande_soutenance` WRITE;
/*!40000 ALTER TABLE `demande_soutenance` DISABLE KEYS */;
INSERT INTO `demande_soutenance` VALUES (30,2,'2025-06-25 00:00:00','2025-06-25 23:09:38','Traitée'),(29,1,'2025-06-08 00:00:00','2025-06-08 14:35:46','Traitée');
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
INSERT INTO `deposer` VALUES (2,51,'2025-06-25'),(1,50,'2025-06-24');
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
  `id_ecue` int NOT NULL AUTO_INCREMENT,
  `lib_ecue` varchar(100) DEFAULT NULL,
  `credit_ecue` int DEFAULT NULL,
  `volume_horaire` int NOT NULL,
  `id_ue` int DEFAULT NULL,
  `id_ens` int NOT NULL,
  PRIMARY KEY (`id_ecue`),
  KEY `id_ue` (`id_ue`),
  KEY `fk_ens_ecue` (`id_ens`)
) ENGINE=MyISAM AUTO_INCREMENT=1152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ecue`
--

LOCK TABLES `ecue` WRITE;
/*!40000 ALTER TABLE `ecue` DISABLE KEYS */;
INSERT INTO `ecue` VALUES (1105,'Suites et fonctions',3,30,1104,0),(1106,'Calcul intégral',2,20,1104,0),(1107,'Élements de logique',2,20,1105,0),(1108,'Structures algébriques',3,30,1105,0),(1109,'Économie1',3,30,1107,0),(1110,'Économie2',2,20,1107,0),(1111,'Géométrie',1,10,1201,0),(1112,'Calcul matriciel',2,20,1201,0),(1113,'Espaces vectoriels',3,30,1201,0),(1114,'Probabilités 1',2,20,1202,0),(1115,'Statistique 1 ',2,20,1202,0),(1116,'Algorithmique',3,30,1204,0),(1117,'Programmation Java',2,20,1204,0),(1118,'Méthodologie de travail',1,10,1206,0),(1119,'Technique d\'expression',1,10,1206,0),(1120,'Analyse 2',3,30,2307,0),(1121,'Algèbre',3,30,2307,0),(1122,'Probabilités 2',2,20,2303,0),(1123,'Statistique 2',2,20,2303,0),(1124,'Modèle comptable',2,20,2305,0),(1125,'Opérations comptables',2,20,2305,0),(1126,'Opérations d\'inventaires',2,20,2305,0),(1127,'Arithmétique',2,20,2401,7),(1128,'Base de données relationnelles',2,20,2402,0),(1130,'Base de données et applications',3,30,2402,0),(1131,'Initiation au langage SCALA',2,20,2404,0),(1132,'Atelier de génie logiciel',4,40,2404,0),(1133,'Programmation VBA',2,20,2405,0),(1134,'Programmation C#',2,20,2405,0),(1135,'Application à la cryptographie',2,20,2407,0),(1136,'Fondamentaux de la POO',3,30,2301,0),(1137,'POO en Java',3,30,2301,0),(1138,'Fondamentaux des systèmes d\'exploitations',2,20,3502,0),(1139,'UNIX et langage C',4,40,3502,0),(1140,'Algo avancé et Java',5,50,3504,0),(1141,'Suivi des performances',2,20,3508,0),(1142,'Coûts complets et coûts partiels',2,20,3508,0),(1143,'Fondamentaux des réseaux',3,30,3604,0),(1144,'Internet/Intranet',2,20,3604,0),(1145,'ISI',2,20,1701,0),(1146,'UML',3,30,3609,0),(1147,'Files d\'attente et gestion de stock',3,30,1702,0),(1149,'Régression linéaire	',1,10,1702,0);
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
INSERT INTO `enseignants` VALUES (1,'KOUA','Brou','kouaB@univ.edu',NULL,'0554126358','2025-05-15','Homme','684e2ef1bfb71_pdp.jpg','7ef21598d64e4d4eabed6e9ff00f9b9b3e14464799b16279f5ecc16b7475bc97'),(2,'BROU','Patrice','brouP@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','4854286bb514f68044481d57dba9a2e566b79b4b6317f68c2e97b55197242800'),(3,'WAH','Médard','wahM@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','d1ca26082383037ae8cd65eb6f56fbdd1dcb6c8b48e47bc937d2e26047c0c5ab'),(4,'DIARRA','Mamadou','diarraM@univ.edu',NULL,'','0000-00-00','Homme','6844f38768faa_BIO FERME ADIAKE.png','83a4c6a74878358f75790333520b3f5463fb82e7d2e8e078a7bccac7c028493a'),(5,'CODJIA','Adolphe','codjiaA@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','8610662eb326ba1125029b5fc34d50d1f0f6cf737ffe7edbed7ce003f8e43bf8'),(6,'IDA','Brou','idaB@univ.edu',NULL,'','0000-00-00','Homme','6844f38768faa_BIO FERME ADIAKE.png','acec2454b5dcc5d741b13d5e703f252306663f107ba6e8ec3ceba3fa2d48e6b4'),(7,'KOUAKOU','Mathias','kouakouM@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','44abaac58f10d40e2c71635ff22b2b9d0f022705194bc6f15ea1ffa3f5a95b87'),(8,'KOUAKOU','Florent','kouakouF@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','95d0c9dbe9524157bb22eded258711ff6e2a27c835a8717b263d9809a9ba2ed4'),(9,'BAILLY','Balé','baillyB@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','d63539ffceb1864f36ac2104f8b92b9cb878c52eefd2f7019bdee0e0e9e70f41'),(10,'BAKAYOKO','Ibrahima','bakayokoI@univ.edu',NULL,'','0000-00-00','Homme','default_profile.jpg','3409e586c8fc3b086f9432a784058123b77b65b5579bcc3f7f217fd5756734e5'),(22,'Konaté','NGOLO','konateN@univ.edu','2025-06-10',NULL,NULL,'Homme','default_profile.jpg',NULL),(21,'YOLI BI','Martin','yoliM@gmail.com','2025-06-11',NULL,NULL,'Homme','default_profile.jpg',NULL),(20,'YOLI BI','Ibrahim','yoliB@univ.ci','2025-07-06',NULL,NULL,'Homme','default_profile.jpg',NULL),(23,'NINDJIN','Malan','nindjinM@edu.ci','2025-06-11',NULL,NULL,'Homme','683dbd4a68f20_logo_mi-removebg-preview.png',NULL),(24,'TOURE','Mohamed','toureM@univ.edu','2025-06-18',NULL,NULL,'Homme','6844f38768faa_BIO FERME ADIAKE.png',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entreprise`
--

LOCK TABLES `entreprise` WRITE;
/*!40000 ALTER TABLE `entreprise` DISABLE KEYS */;
INSERT INTO `entreprise` VALUES (17,'DATA354','Genie 2000 - faya','Abidjan','Côte d’Ivoire','22450572','data354@mail.com'),(18,'DATA354','Genie 2000 - faya','Abidjan','Côte d’Ivoire','0707019478','data354@mail.com'),(20,'DATA354','MARCORY','Abidjan','Côte d’Ivoire','2247057850','data354@mail.com'),(21,'DATA354','Riviera palmeraie','Abidjan','Côte d’Ivoire','0775851975','data354@mail.com');
/*!40000 ALTER TABLE `entreprise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etudiants` (
  `num_etd` int NOT NULL AUTO_INCREMENT,
  `nom_etd` varchar(100) DEFAULT NULL,
  `num_carte_etd` varchar(30) DEFAULT NULL,
  `prenom_etd` varchar(100) DEFAULT NULL,
  `date_naissance_etd` date DEFAULT NULL,
  `email_etd` varchar(255) DEFAULT NULL,
  `mdp_etd` varchar(255) DEFAULT NULL,
  `statut_eligibilite` enum('En attente de confirmation','Éligible','Non éligible') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'En attente de confirmation',
  `id_promotion` int NOT NULL,
  `sexe_etd` enum('Homme','Femme') NOT NULL,
  `num_tel_etd` varchar(20) NOT NULL,
  `adresse_etd` varchar(120) NOT NULL,
  `ville_etd` varchar(50) NOT NULL,
  `pays_etd` varchar(30) NOT NULL,
  `photo_etd` varchar(255) NOT NULL,
  `id_niv_etd` int DEFAULT NULL,
  PRIMARY KEY (`num_etd`),
  KEY `fk_etudiants_niveau_etude` (`id_niv_etd`),
  KEY `fk_promotion_etd` (`id_promotion`),
  CONSTRAINT `fk_promotion_etd` FOREIGN KEY (`id_promotion`) REFERENCES `promotion` (`id_promotion`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `etudiants`
--

LOCK TABLES `etudiants` WRITE;
/*!40000 ALTER TABLE `etudiants` DISABLE KEYS */;
INSERT INTO `etudiants` VALUES (1,'GOMEZ','ETD2025001','Ange Axel','2000-05-15','axelangegomez@gmail.com','b30fd6658bf116e99f4be6c88a21dfe8797fca5cd17ac5fb1fda8d140d3ca387','Éligible',17,'Homme','+225 0707019478','genie 2000','Abidjan','Côte d\'ivoire','photo_6856cda032bee.jpg',2),(2,'KROUMA','ETD2025002','Franck Adams','2005-11-22','francky@etu.univ.edu','adb029bcca098ad49fb407588213f334f63ba6447fcb3890b6ff4b865c9904ee','Éligible',19,'Homme','0185978541','Yopougon','Abidjan','Côte d’Ivoire','photo_68502d7f3054b.jpg',5),(3,'AMANI','ETD2025003','Yves','2001-03-08','yvesA@etu.univ.edu','f65ff48872eaae1ebbe688036fe211cfac21bee110f01612f6dee77f19514717','En attente de confirmation',19,'Homme','','','','','default_profile.jpg',NULL),(4,'YAO','ETD2025004','Ama Marie-grâce','2004-01-12','yaoAy@etu.univ.edu','2d4a54cdf420b77f0ae9e02f37c4b9b018d6f9a84bf7c14c93bd3f8c3a0bfe6e','Non éligible',1,'Femme','','','','','default_profile.jpg',NULL),(13,'Kouakou','ETD2025009','Ariston','2025-06-21','kouakAst@gmail.com',NULL,'En attente de confirmation',2,'Homme','','','','','default_profile.jpg',5),(15,'TRA','ETD20250010','Lou Océane','2004-04-27','noemietra27@gmail.com',NULL,'En attente de confirmation',19,'Femme','','','','','default_profile.jpg',5);
/*!40000 ALTER TABLE `etudiants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluer_ecue`
--

DROP TABLE IF EXISTS `evaluer_ecue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluer_ecue` (
  `id_eval_ecue` int NOT NULL AUTO_INCREMENT,
  `num_etd` varchar(50) NOT NULL,
  `id_ecue` int NOT NULL,
  `id_semestre` int NOT NULL,
  `id_ac` int NOT NULL,
  `id_personnel_adm` int NOT NULL,
  `note` decimal(5,2) NOT NULL,
  `credit` int NOT NULL,
  `date_eval` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_eval_ecue`),
  KEY `num_etd` (`num_etd`),
  KEY `id_ecue` (`id_ecue`),
  KEY `id_semestre` (`id_semestre`),
  KEY `id_ac` (`id_ac`),
  KEY `id_personnel_adm` (`id_personnel_adm`)
) ENGINE=MyISAM AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluer_ecue`
--

LOCK TABLES `evaluer_ecue` WRITE;
/*!40000 ALTER TABLE `evaluer_ecue` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluer_ecue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluer_ue`
--

DROP TABLE IF EXISTS `evaluer_ue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluer_ue` (
  `id_eval_ue` int NOT NULL AUTO_INCREMENT,
  `num_etd` varchar(20) NOT NULL,
  `id_ue` int NOT NULL,
  `id_semestre` int NOT NULL,
  `id_ac` int NOT NULL,
  `id_personnel_adm` int NOT NULL,
  `note` decimal(4,2) NOT NULL,
  `credit` int NOT NULL,
  `date_eval` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_eval_ue`),
  UNIQUE KEY `unique_evaluation_ue` (`num_etd`,`id_ue`,`id_semestre`,`id_ac`,`id_personnel_adm`),
  KEY `id_ue` (`id_ue`),
  KEY `id_semestre` (`id_semestre`),
  KEY `id_ac` (`id_ac`),
  KEY `id_personnel_adm` (`id_personnel_adm`)
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluer_ue`
--

LOCK TABLES `evaluer_ue` WRITE;
/*!40000 ALTER TABLE `evaluer_ue` DISABLE KEYS */;
INSERT INTO `evaluer_ue` VALUES (37,'1',2408,3,2524,3,2.00,2,'2025-06-28 11:20:14'),(36,'1',2302,3,2524,3,15.40,2,'2025-06-28 11:20:14'),(31,'1',2304,3,2524,3,13.00,3,'2025-06-28 11:20:14'),(32,'1',2306,3,2524,3,12.80,3,'2025-06-28 11:20:14'),(33,'1',2406,3,2524,3,5.00,3,'2025-06-28 11:20:14'),(34,'1',2403,3,2524,3,5.00,3,'2025-06-28 11:20:14'),(35,'1',2409,3,2524,3,6.00,2,'2025-06-28 11:20:14');
/*!40000 ALTER TABLE `evaluer_ue` ENABLE KEYS */;
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
INSERT INTO `faire_stage` VALUES (1,20,'IA & DATA SCIENCE','Stage d\'apprentissage','stage_fin_etude','2025-06-29','2025-07-10','2025-06-08','KONAN Jean Brice','Data scientist','0585694141','konanJB@gmail.com'),(2,21,'Développement web','Stage pour approfondir les compétences en web','stage_fin_etude','2025-06-27','2025-12-26','2025-06-25','KOUADIO Jean-Claude','Web design','0122457896','kouadioJC@gmail.com');
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
-- Table structure for table `frais_inscription`
--

DROP TABLE IF EXISTS `frais_inscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `frais_inscription` (
  `id_frais` int NOT NULL AUTO_INCREMENT,
  `id_niv_etd` int NOT NULL,
  `id_ac` int NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_frais`),
  KEY `fk_frais_niveau` (`id_niv_etd`),
  KEY `fk_frais_annee` (`id_ac`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frais_inscription`
--

LOCK TABLES `frais_inscription` WRITE;
/*!40000 ALTER TABLE `frais_inscription` DISABLE KEYS */;
INSERT INTO `frais_inscription` VALUES (1,1,2524,780000.00),(2,2,2524,800000.00),(3,3,2524,860000.00),(4,4,2524,925000.00),(5,5,2524,975000.00);
/*!40000 ALTER TABLE `frais_inscription` ENABLE KEYS */;
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
-- Table structure for table `historique_envoi`
--

DROP TABLE IF EXISTS `historique_envoi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historique_envoi` (
  `id_historique` int NOT NULL AUTO_INCREMENT,
  `id_cr` int NOT NULL,
  `email_destinataire` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_envoi` datetime NOT NULL,
  `statut` enum('succès','échec') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_erreur` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_historique`),
  KEY `id_cr` (`id_cr`),
  CONSTRAINT `historique_envoi_ibfk_1` FOREIGN KEY (`id_cr`) REFERENCES `compte_rendu` (`id_cr`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historique_envoi`
--

LOCK TABLES `historique_envoi` WRITE;
/*!40000 ALTER TABLE `historique_envoi` DISABLE KEYS */;
INSERT INTO `historique_envoi` VALUES (39,13,'noemietra27@gmail.com','2025-06-09 02:59:22','succès',NULL),(40,13,'axelangegomez@gmail.com','2025-06-23 17:32:53','succès',NULL),(41,13,'axelangegomez@gmail.com','2025-06-23 17:32:56','succès',NULL),(42,13,'axelangegomez@gmail.com','2025-06-23 17:33:00','succès',NULL),(43,13,'axelangegomez@gmail.com','2025-06-23 17:33:03','succès',NULL),(44,13,'axelangegomez@gmail.com','2025-06-23 17:33:06','succès',NULL),(45,13,'axelangegomez@gmail.com','2025-06-23 17:33:11','succès',NULL),(46,13,'axelangegomez@gmail.com','2025-06-23 17:33:14','succès',NULL);
/*!40000 ALTER TABLE `historique_envoi` ENABLE KEYS */;
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
  `categorie` enum('memoire','evaluation','deadline','commission','general') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'general',
  `priorite` enum('basse','normale','haute','urgente') DEFAULT 'normale',
  `statut` enum('brouillon','envoyé','lu','archivé','supprimé','répondu','non lu') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'envoyé',
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
) ENGINE=MyISAM AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (80,1,14,'individuel','SALUTATION !!','Bonjour etudiant','chat','general','normale','répondu','2025-06-25 18:19:09','2025-06-25 18:19:09',NULL,NULL,NULL,NULL),(81,14,1,'individuel','Re: SALUTATION !!','Hey bro','chat','','normale','','2025-06-25 18:20:15','2025-06-25 18:20:15',NULL,NULL,NULL,NULL),(78,11,14,'individuel','Votre rapport a été rejeté','Bonjour Ange Axel GOMEZ,<br><br>Nous regrettons de vous informer que votre rapport <strong>\"Big\"</strong> n\'a pas été approuvé pour les raisons suivantes:<br><div style=\'background-color: #f8f9fa; padding: 15px; border-left: 4px solid #e74c3c; margin-top: 10px; margin-bottom: 10px;\'><p style=\'margin: 0;\'>Structure a revoir</p></div><p>Nous vous encourageons à apporter les modifications nécessaires et à soumettre une nouvelle version.</p><p>Cordialement,<br>L\'équipe Check Master</p>','','evaluation','normale','supprimé','2025-06-25 13:56:26','2025-06-25 13:56:26',NULL,NULL,NULL,NULL),(79,11,14,'individuel','Votre rapport a été rejeté','Bonjour Ange Axel GOMEZ,<br><br>Nous regrettons de vous informer que votre rapport <strong>\"Big\"</strong> n\'a pas été approuvé pour les raisons suivantes:<br><div style=\'background-color: #f8f9fa; padding: 15px; border-left: 4px solid #e74c3c; margin-top: 10px; margin-bottom: 10px;\'><p style=\'margin: 0;\'>A revoir</p></div><p>Nous vous encourageons à apporter les modifications nécessaires et à soumettre une nouvelle version.</p><p>Cordialement,<br>L\'équipe Check Master</p>','','evaluation','normale','archivé','2025-06-25 14:10:15','2025-06-25 14:10:15',NULL,NULL,NULL,NULL),(77,11,14,'individuel','Votre rapport a été approuvé','Bonjour Ange Axel GOMEZ,<br><br>Nous avons le plaisir de vous informer que votre rapport <strong>\"Big\"</strong> a été approuvé.<br><br>Cordialement,<br>L\'équipe Check Master','','evaluation','normale','archivé','2025-06-25 13:56:14','2025-06-25 13:56:14',NULL,NULL,NULL,NULL),(75,11,14,'individuel','Votre rapport a été rejeté','Bonjour Ange Axel GOMEZ,<br><br>Nous regrettons de vous informer que votre rapport <strong>\"Big\"</strong> n\'a pas été approuvé pour les raisons suivantes:<br><div style=\'background-color: #f8f9fa; padding: 15px; border-left: 4px solid #e74c3c; margin-top: 10px; margin-bottom: 10px;\'><p style=\'margin: 0;\'>La structure est à revoir</p></div><p>Nous vous encourageons à apporter les modifications nécessaires et à soumettre une nouvelle version.</p><p>Cordialement,<br>L\'équipe Check Master</p>','','evaluation','normale','envoyé','2025-06-25 13:54:30','2025-06-25 13:54:30',NULL,NULL,NULL,NULL),(76,11,14,'individuel','Votre rapport a été approuvé','Bonjour Ange Axel GOMEZ,<br><br>Nous avons le plaisir de vous informer que votre rapport <strong>\"GOMEZ_Ange Axel_2025-06-24\"</strong> a été approuvé.<br><br>Cordialement,<br>L\'équipe Check Master','','evaluation','normale','archivé','2025-06-25 13:55:02','2025-06-25 13:55:02',NULL,NULL,NULL,NULL),(72,11,1,'individuel','Re: Re: HEY','ça peut aller','chat','','normale','lu','2025-06-21 15:14:48','2025-06-21 15:14:48',NULL,NULL,NULL,NULL),(73,11,14,'individuel','Votre rapport a été approuvé','Bonjour Ange Axel GOMEZ,<br><br>Nous avons le plaisir de vous informer que votre rapport <strong>\"GOMEZ_Ange Axel_2025-06-24\"</strong> a été approuvé.<br><br>Cordialement,<br>L\'équipe Check Master','','evaluation','normale','envoyé','2025-06-25 13:50:31','2025-06-25 13:50:31',NULL,NULL,NULL,NULL),(74,11,14,'individuel','Votre rapport a été approuvé','Bonjour Ange Axel GOMEZ,<br><br>Nous avons le plaisir de vous informer que votre rapport <strong>\"GOMEZ_Ange Axel_2025-06-24\"</strong> a été approuvé.<br><br>Cordialement,<br>L\'équipe Check Master','','evaluation','normale','supprimé','2025-06-25 13:53:37','2025-06-25 13:53:37',NULL,NULL,NULL,NULL),(65,3,11,'individuel','Re: Rappel : Rapports en attente d\'évaluation','Bien reçu Miss Séri','chat','','normale','supprimé','2025-06-09 02:50:29','2025-06-09 02:50:29',NULL,NULL,NULL,NULL),(66,11,1,'individuel','HEY','salutation mr','chat','general','normale','supprimé','2025-06-15 15:50:01','2025-06-15 15:50:01',NULL,NULL,NULL,NULL),(67,11,1,'individuel','Réclamations','etudkant demande reclamation','chat','general','normale','supprimé','2025-06-15 15:54:41','2025-06-15 15:54:41',NULL,NULL,NULL,NULL),(68,11,1,'individuel','HEY','Cc !','chat','general','normale','répondu','2025-06-16 01:25:28','2025-06-16 01:25:28',NULL,NULL,NULL,NULL),(69,1,11,'individuel','Re: HEY','C\'est comment?','chat','','normale','répondu','2025-06-16 02:23:27','2025-06-16 02:23:27',NULL,NULL,NULL,NULL),(70,1,14,'individuel','Réclamations','Votre réclamation à bien été pris en compte.','chat','general','normale','répondu','2025-06-16 14:40:27','2025-06-16 14:40:27',NULL,NULL,NULL,NULL),(71,14,1,'individuel','Re: Réclamations','Merci monsieur. Bien reçu !','chat','','normale','envoyé','2025-06-16 14:40:50','2025-06-16 14:40:50',NULL,NULL,NULL,NULL),(64,11,3,'individuel','Rappel : Rapports en attente d\'évaluation','Cher(e) membre de la commission,\r\n                                Nous vous rappelons que plusieurs rapports sont en attente de votre évaluation. Merci de bien vouloir vous connecter à votre espace pour procéder à l\'évaluation des rapports qui vous ont été assignés.\r\n                                Les délais d\'évaluation étant importants pour le bon déroulement du processus, nous vous remercions de votre diligence.\r\n                                Cordialement,\r\n                                Le secrétariat de la commission\r\n                                Université UFHB','rappel','evaluation','urgente','répondu','2025-06-09 02:49:50','2025-06-09 02:49:50',NULL,NULL,NULL,NULL),(63,11,1,'individuel','Rappel : Rapports en attente d\'évaluation','Cher(e) membre de la commission,\r\n                                Nous vous rappelons que plusieurs rapports sont en attente de votre évaluation. Merci de bien vouloir vous connecter à votre espace pour procéder à l\'évaluation des rapports qui vous ont été assignés.\r\n                                Les délais d\'évaluation étant importants pour le bon déroulement du processus, nous vous remercions de votre diligence.\r\n                                Cordialement,\r\n                                Le secrétariat de la commission\r\n                                Université UFHB','rappel','evaluation','urgente','supprimé','2025-06-09 02:43:21','2025-06-09 02:43:21',NULL,NULL,NULL,NULL),(82,14,1,'individuel','Re: SALUTATION !!','Hey','chat','','normale','','2025-06-25 18:24:52','2025-06-25 18:24:52',NULL,NULL,NULL,NULL),(83,1,14,'individuel','Tchai','C&#39;est comment','','general','normale','répondu','2025-06-25 18:28:36','2025-06-25 18:28:36',NULL,NULL,NULL,NULL),(84,14,1,'individuel','Re: Tchai','ça va vieux','chat','','normale','','2025-06-25 18:28:59','2025-06-25 18:28:59',NULL,NULL,NULL,NULL),(85,1,14,'individuel','BONSOIR','HEHEEH','','general','normale','répondu','2025-06-25 18:33:33','2025-06-25 18:33:33',NULL,NULL,NULL,NULL),(86,14,1,'individuel','Re: BONSOIR','bON','chat','','normale','','2025-06-25 18:34:10','2025-06-25 18:34:10',NULL,NULL,NULL,NULL),(87,1,14,'individuel','Bonne nuit','aurevoir','','general','normale','répondu','2025-06-25 18:35:38','2025-06-25 18:35:38',NULL,NULL,NULL,NULL),(88,14,1,'individuel','Re: Bonne nuit','A toi de même','chat','','normale','répondu','2025-06-25 18:36:17','2025-06-25 18:36:17',NULL,NULL,NULL,NULL),(89,1,11,'individuel','Miss Séri','Bonsoir','','general','normale','lu','2025-06-25 18:37:53','2025-06-25 18:37:53',NULL,NULL,NULL,NULL),(90,1,14,'individuel','Re: Re: Bonne nuit','Merci','chat','','normale','lu','2025-06-25 18:40:57','2025-06-25 18:40:57',NULL,NULL,NULL,NULL),(91,1,14,'individuel','Re: Re: Bonne nuit','Merci','chat','','normale','répondu','2025-06-25 18:42:16','2025-06-25 18:42:16',NULL,NULL,NULL,NULL),(92,14,2,'individuel','HEY','Bonsoir mr','','general','normale','lu','2025-06-25 18:43:14','2025-06-25 18:43:14',NULL,NULL,NULL,NULL),(93,14,1,'individuel','Re: Re: Re: Bonne nuit','h','chat','','normale','non lu','2025-06-25 18:47:11','2025-06-25 18:47:11',NULL,NULL,NULL,NULL),(94,11,15,'individuel','Votre rapport a été approuvé','Bonjour Franck Adams KROUMA,<br><br>Nous avons le plaisir de vous informer que votre rapport <strong>\"Informatisation des documents administratifs\"</strong> a été approuvé.<br><br>Cordialement,<br>L\'équipe Check Master','','evaluation','normale','non lu','2025-06-25 23:12:23','2025-06-25 23:12:23',NULL,NULL,NULL,NULL);
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
  `mode_de_paiement` enum('espece','cheque') NOT NULL,
  `motif_paiement` varchar(255) NOT NULL DEFAULT 'Néant',
  `numero_cheque` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Néant',
  `montant_paye` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL DEFAULT (curdate()),
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `numero_recu` (`numero_recu`),
  KEY `id_reglement` (`id_reglement`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement_reglement`
--

LOCK TABLES `paiement_reglement` WRITE;
/*!40000 ALTER TABLE `paiement_reglement` DISABLE KEYS */;
INSERT INTO `paiement_reglement` VALUES (39,28,'REC-202504B2BC','espece','Néant','Néant',970000.00,'2025-06-05'),(40,28,'REC-20257E120A','espece','Néant','Néant',750000.00,'2025-06-05'),(41,28,'REC-20256D886B','espece','Néant','Néant',250000.00,'2025-06-05'),(42,29,'REC-202518607C','espece','Néant','Néant',450000.00,'2025-06-05'),(43,29,'REC-20255ED689','espece','Néant','Néant',350000.00,'2025-06-05'),(44,29,'REC-20253CF575','espece','Néant','Néant',50000.00,'2025-06-05'),(45,29,'REC-202578125C','espece','Néant','Néant',125000.00,'2025-06-05'),(46,30,'REC-2025E0A4BB','espece','Néant','Néant',450000.00,'2025-06-05'),(47,30,'REC-20250274CB','espece','Néant','Néant',350000.00,'2025-06-05'),(48,30,'REC-2025DA5D7C','espece','Néant','Néant',175000.00,'2025-06-05'),(49,31,'REC-20257779ED','espece','Néant','Néant',45000.00,'2025-06-05'),(50,31,'REC-20253F0E58','espece','Néant','Néant',930000.00,'2025-06-05'),(51,32,'REC-2025E065C0','espece','Néant','Néant',450000.00,'2025-06-05'),(52,32,'REC-20257F9851','espece','Néant','Néant',400000.00,'2025-06-05'),(53,32,'REC-20256C2EDE','espece','Néant','Néant',125000.00,'2025-06-05'),(54,33,'REC-2025D06FEB','espece','Néant','Néant',450000.00,'2025-06-05'),(55,33,'REC-2025CE3492','espece','Néant','Néant',250000.00,'2025-06-05'),(56,33,'REC-20252888AE','espece','Néant','Néant',75000.00,'2025-06-05'),(57,33,'REC-202576BAD1','espece','Néant','Néant',200000.00,'2025-06-05'),(58,34,'REC-2025434B2D','espece','Néant','Néant',450000.00,'2025-06-05'),(59,34,'REC-2025BC48FF','espece','Néant','Néant',525000.00,'2025-06-05'),(60,35,'REC-2025B6E649','espece','Néant','Néant',450000.00,'2025-06-05'),(61,35,'REC-202518201C','espece','Néant','Néant',250000.00,'2025-06-05'),(62,36,'REC-202534420B','espece','Néant','Néant',425000.00,'2025-06-05'),(63,36,'REC-2025170DFD','espece','Néant','Néant',540000.00,'2025-06-05'),(64,36,'REC-2025A16CA9','espece','Néant','Néant',10000.00,'2025-06-05'),(65,37,'REC-202555985C','espece','Néant','Néant',750000.00,'2025-06-05'),(66,38,'REC-2025D01626','espece','Néant','Néant',875000.00,'2025-06-05'),(67,39,'REC-2025259346','espece','Néant','Néant',450000.00,'2025-06-05'),(68,39,'REC-2025D5E5B6','espece','Néant','Néant',45000.00,'2025-06-05'),(69,40,'REC-20251AF6E5','espece','Néant','Néant',450000.00,'2025-06-05'),(70,41,'REC-2025BB629E','espece','Néant','Néant',425000.00,'2025-06-05'),(71,41,'REC-2025E3EC72','espece','Néant','Néant',125000.00,'2025-06-05'),(72,41,'REC-20255CE775','espece','Néant','Néant',75000.00,'2025-06-05'),(73,41,'REC-2025DF176F','espece','Néant','Néant',150000.00,'2025-06-05'),(74,41,'REC-202513B328','espece','Néant','Néant',150000.00,'2025-06-05'),(75,41,'REC-202571E4B3','espece','Néant','Néant',50000.00,'2025-06-05'),(76,42,'REC-20259767A3','espece','Néant','Néant',425000.00,'2025-06-06'),(77,42,'REC-20259E9DAC','espece','Néant','Néant',550000.00,'2025-06-08'),(78,43,'REC-2025038363','espece','Néant','Néant',450000.00,'2025-06-18'),(79,43,'REC-2025FC220A','espece','Néant','Néant',350000.00,'2025-06-18'),(80,43,'REC-2025A456BF','espece','Néant','Néant',175000.00,'2025-06-18'),(81,44,'REC-2025E15360','espece','Néant','Néant',475000.00,'2025-06-18'),(82,45,'REC-202563CABA','espece','Néant','Néant',475000.00,'2025-06-18'),(83,45,'REC-202541E09A','cheque','Scolarité','140202',145000.00,'2025-06-18'),(84,45,'REC-2025ADC372','espece','Scolarité',NULL,25000.00,'2025-06-18'),(85,45,'REC-20258F1FFC','cheque','Scolarité','140202',275000.00,'2025-06-18'),(86,46,'REC-20250D8514','cheque','Scolarité','140202',45000.00,'2025-06-18'),(100,70,'REC-2025545DA2','cheque','frais de scolarité','5478952',975000.00,'2025-06-25');
/*!40000 ALTER TABLE `paiement_reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partage_rapport`
--

DROP TABLE IF EXISTS `partage_rapport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partage_rapport` (
  `id_rapport_etd` int NOT NULL,
  `id_personnel_adm` int NOT NULL,
  `date_partage` datetime DEFAULT CURRENT_TIMESTAMP,
  KEY `id_rapport_etd` (`id_rapport_etd`),
  KEY `id_personnel_adm` (`id_personnel_adm`),
  CONSTRAINT `partage_rapport_ibfk_1` FOREIGN KEY (`id_rapport_etd`) REFERENCES `rapport_etudiant` (`id_rapport_etd`) ON DELETE CASCADE,
  CONSTRAINT `partage_rapport_ibfk_2` FOREIGN KEY (`id_personnel_adm`) REFERENCES `personnel_administratif` (`id_personnel_adm`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partage_rapport`
--

LOCK TABLES `partage_rapport` WRITE;
/*!40000 ALTER TABLE `partage_rapport` DISABLE KEYS */;
INSERT INTO `partage_rapport` VALUES (50,1,'2025-06-09 00:23:11'),(51,1,'2025-06-25 23:16:19');
/*!40000 ALTER TABLE `partage_rapport` ENABLE KEYS */;
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
INSERT INTO `participants` VALUES (2,1,'en attente','2025-06-25 00:23:25'),(5,1,'en attente','2025-06-25 00:23:25'),(10,1,'en attente','2025-06-25 00:23:25'),(20,2,'en attente','2025-06-25 01:15:55'),(8,3,'en attente','2025-06-25 01:18:59'),(2,7,'en attente','2025-06-25 01:27:25'),(5,7,'en attente','2025-06-25 01:27:25'),(10,7,'en attente','2025-06-25 01:27:25'),(2,8,'en attente','2025-06-25 01:28:21'),(5,8,'en attente','2025-06-25 01:28:21'),(9,8,'en attente','2025-06-25 01:28:21'),(10,8,'en attente','2025-06-25 01:28:21');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel_administratif`
--

LOCK TABLES `personnel_administratif` WRITE;
/*!40000 ALTER TABLE `personnel_administratif` DISABLE KEYS */;
INSERT INTO `personnel_administratif` VALUES (1,'SÉRI','Marie Christine','2025-06-06','1991-03-04','0783124545','seriMC@univ.edu','df8f597da9c13895791d3fdb81cb9112877b12ec7b26c9752126bc34f182c38f','Femme','684e2fec88028_BIO FERME ADIAKE.png'),(2,'YAH','Christine','2025-06-20','1993-10-23','0710124524','yah@univ.edu','1df83c4230a9a05ef403b6e82866282b3236e4409ac95892a6e9f9a2f4bf09b1','Femme','default_profile.jpg'),(3,'KAMÉNAN','Durand','2025-06-01','1988-11-12','0884745210','durandK@univ.edu','4ec476c2d7b6bd0f316afbb2c0cdae3f8a816da2b15e7a2477d1155b0d4dde05','Homme','685880ae8ae66_BIO FERME ADIAKE.png'),(8,'Sara','Evangéline','2025-06-20',NULL,NULL,'saraE@univ.ci',NULL,'Femme','default_profile.jpg'),(9,'KOUADIO','Rostand','2025-06-21',NULL,NULL,'kouaR@gmail.com',NULL,'Homme','default_profile.jpg');
/*!40000 ALTER TABLE `personnel_administratif` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pister`
--

DROP TABLE IF EXISTS `pister`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pister` (
  `id_piste` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_traitement` int NOT NULL,
  `id_action` int NOT NULL,
  `date_piste` date DEFAULT NULL,
  `heure_piste` time DEFAULT NULL,
  `acceder` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_piste`),
  KEY `id_traitement` (`id_traitement`),
  KEY `fk_id_action` (`id_action`),
  KEY `fk_id_utilisateur` (`id_utilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=1622 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pister`
--

LOCK TABLES `pister` WRITE;
/*!40000 ALTER TABLE `pister` DISABLE KEYS */;
INSERT INTO `pister` VALUES (119,1,1,1,'2025-06-23','16:40:48',1),(118,1,1,2,'2025-06-23','16:40:41',0),(117,1,11,310,'2025-06-23','16:40:30',1),(116,1,1,6,'2025-06-23','16:40:23',1),(115,1,1,1,'2025-06-23','16:40:23',1),(114,1,11,310,'2025-06-23','16:39:22',1),(113,1,11,310,'2025-06-23','16:39:19',1),(112,1,11,310,'2025-06-23','16:39:15',1),(111,1,11,310,'2025-06-23','16:39:11',1),(110,1,11,310,'2025-06-23','16:39:09',1),(109,1,11,310,'2025-06-23','16:39:06',1),(108,1,11,310,'2025-06-23','16:38:12',1),(107,1,11,310,'2025-06-23','16:38:08',1),(106,1,1,6,'2025-06-23','16:38:04',1),(105,1,1,1,'2025-06-23','16:38:04',1),(104,1,11,310,'2025-06-23','16:37:53',1),(103,1,11,310,'2025-06-23','16:37:48',1),(102,13,3,302,'2025-06-23','16:35:51',1),(101,13,3,302,'2025-06-23','16:34:23',1),(100,13,3,302,'2025-06-23','16:33:40',1),(99,13,3,302,'2025-06-23','16:32:27',1),(98,13,3,302,'2025-06-23','16:30:14',1),(97,13,3,302,'2025-06-23','16:24:36',1),(96,1,11,310,'2025-06-23','16:21:47',1),(95,13,3,302,'2025-06-23','16:21:42',1),(94,13,14,313,'2025-06-23','16:21:40',1),(93,13,1,1,'2025-06-23','16:21:40',1),(92,1,11,310,'2025-06-23','16:18:05',1),(91,1,11,310,'2025-06-23','16:18:01',1),(90,1,11,310,'2025-06-23','16:17:45',1),(89,13,3,302,'2025-06-23','16:17:39',1),(88,13,14,313,'2025-06-23','16:17:38',1),(87,1,11,310,'2025-06-23','16:17:28',1),(86,13,3,302,'2025-06-23','16:17:22',1),(85,1,11,310,'2025-06-23','16:17:03',1),(84,13,3,302,'2025-06-23','16:16:59',1),(83,13,14,313,'2025-06-23','16:16:58',1),(82,13,1,1,'2025-06-23','16:16:57',1),(81,1,11,310,'2025-06-23','16:16:35',1),(80,1,8,307,'2025-06-23','16:16:33',1),(79,1,11,310,'2025-06-23','16:15:01',1),(78,1,7,306,'2025-06-23','16:14:55',1),(120,1,1,6,'2025-06-23','16:40:48',1),(121,1,11,310,'2025-06-23','16:40:50',1),(122,13,3,302,'2025-06-23','16:42:03',1),(123,13,3,302,'2025-06-23','16:43:53',1),(124,13,3,302,'2025-06-23','16:45:11',1),(125,1,1,6,'2025-06-23','16:46:52',1),(126,1,11,310,'2025-06-23','16:46:57',1),(127,1,11,310,'2025-06-23','16:49:56',1),(128,1,1,6,'2025-06-23','16:50:04',1),(129,1,11,310,'2025-06-23','16:52:56',1),(130,1,11,310,'2025-06-23','16:53:19',1),(131,1,11,310,'2025-06-23','16:53:39',1),(132,1,11,310,'2025-06-23','16:56:03',1),(133,1,11,310,'2025-06-23','16:56:07',1),(134,1,11,310,'2025-06-23','16:56:15',1),(135,1,7,306,'2025-06-23','16:57:23',1),(136,14,1,1,'2025-06-24','09:39:33',1),(137,14,10,323,'2025-06-24','09:39:37',1),(138,14,1,1,'2025-06-24','09:42:53',1),(139,1,1,1,'2025-06-24','09:43:10',1),(140,1,1,6,'2025-06-24','09:43:10',1),(141,1,11,310,'2025-06-24','09:43:18',1),(142,14,1,1,'2025-06-24','09:44:03',1),(143,14,1,2,'2025-06-24','09:44:13',0),(144,1,11,310,'2025-06-24','09:44:20',1),(145,14,1,2,'2025-06-24','09:49:14',0),(146,1,11,310,'2025-06-24','09:49:23',1),(147,14,1,2,'2025-06-24','09:50:59',0),(148,1,11,310,'2025-06-24','09:51:03',1),(149,1,11,310,'2025-06-24','09:51:14',1),(150,1,11,310,'2025-06-24','09:51:21',1),(151,14,1,2,'2025-06-24','09:52:54',0),(152,1,11,310,'2025-06-24','09:52:59',1),(153,1,1,1,'2025-06-24','10:00:31',1),(154,1,1,6,'2025-06-24','10:00:32',1),(155,1,11,310,'2025-06-24','10:01:26',1),(156,14,1,2,'2025-06-24','10:04:02',0),(157,1,11,310,'2025-06-24','10:04:05',1),(158,14,1,1,'2025-06-24','10:04:22',1),(159,1,11,310,'2025-06-24','10:04:26',1),(160,14,19,451,'2025-06-24','10:26:16',1),(161,1,2,301,'2025-06-24','11:20:02',1),(162,1,5,453,'2025-06-24','11:20:15',1),(163,1,20,319,'2025-06-24','11:20:19',1),(164,1,20,319,'2025-06-24','11:20:28',1),(165,1,5,453,'2025-06-24','11:20:49',1),(166,1,8,307,'2025-06-25','00:07:06',1),(167,1,2,301,'2025-06-25','01:05:40',1),(168,1,2,301,'2025-06-25','01:05:53',1),(169,1,2,301,'2025-06-25','01:08:00',1),(170,1,2,301,'2025-06-25','01:10:58',1),(171,1,1,6,'2025-06-25','01:40:37',1),(172,1,1,6,'2025-06-25','01:40:37',1),(173,1,2,301,'2025-06-25','01:40:40',1),(174,1,11,310,'2025-06-25','01:40:45',1),(175,1,20,319,'2025-06-25','01:40:51',1),(176,1,13,6,'2025-06-25','01:41:04',1),(177,1,11,310,'2025-06-25','01:41:07',1),(178,1,2,6,'2025-06-25','02:05:35',1),(179,1,4,6,'2025-06-25','02:05:36',1),(180,1,5,6,'2025-06-25','02:05:37',1),(181,1,6,6,'2025-06-25','02:05:38',1),(182,1,20,6,'2025-06-25','02:05:41',1),(183,1,13,6,'2025-06-25','02:05:42',1),(184,1,11,310,'2025-06-25','02:05:43',1),(185,1,11,310,'2025-06-25','02:16:23',1),(186,1,2,6,'2025-06-25','02:16:30',1),(187,14,1,1,'2025-06-25','02:16:38',1),(188,14,18,6,'2025-06-25','02:16:38',1),(189,14,19,6,'2025-06-25','02:16:39',1),(190,14,21,6,'2025-06-25','02:16:40',1),(191,14,24,6,'2025-06-25','02:16:41',1),(192,14,25,6,'2025-06-25','02:16:41',1),(193,14,18,6,'2025-06-25','02:16:42',1),(194,1,11,310,'2025-06-25','02:16:48',1),(195,1,1,6,'2025-06-25','02:17:15',1),(196,1,1,6,'2025-06-25','02:17:15',1),(197,1,2,6,'2025-06-25','02:17:21',1),(198,1,1,6,'2025-06-25','02:17:23',1),(199,1,1,6,'2025-06-25','02:17:23',1),(200,1,2,6,'2025-06-25','02:17:25',1),(201,1,4,6,'2025-06-25','02:17:27',1),(202,1,2,6,'2025-06-25','02:17:30',1),(203,1,1,6,'2025-06-25','02:17:33',1),(204,1,1,6,'2025-06-25','02:17:33',1),(205,1,4,6,'2025-06-25','02:17:58',1),(206,1,5,6,'2025-06-25','02:17:59',1),(207,1,2,6,'2025-06-25','02:18:00',1),(208,1,6,6,'2025-06-25','02:18:01',1),(209,1,2,6,'2025-06-25','02:18:06',1),(210,1,2,6,'2025-06-25','02:20:11',1),(211,13,1,1,'2025-06-25','02:20:25',1),(212,13,14,6,'2025-06-25','02:20:25',1),(213,13,14,313,'2025-06-25','02:20:25',1),(214,11,1,1,'2025-06-25','02:25:13',1),(215,11,14,6,'2025-06-25','02:25:13',1),(216,11,14,313,'2025-06-25','02:25:13',1),(217,11,14,6,'2025-06-25','02:25:16',1),(218,11,14,313,'2025-06-25','02:25:16',1),(219,11,14,6,'2025-06-25','02:25:23',1),(220,11,14,313,'2025-06-25','02:25:23',1),(221,11,14,6,'2025-06-25','02:25:44',1),(222,11,14,6,'2025-06-25','02:25:44',1),(223,11,14,313,'2025-06-25','02:25:44',1),(224,1,2,6,'2025-06-25','02:28:49',1),(225,1,2,6,'2025-06-25','02:30:09',1),(226,1,2,6,'2025-06-25','02:30:21',1),(227,1,2,6,'2025-06-25','02:30:33',1),(228,11,14,6,'2025-06-25','02:31:05',1),(229,11,14,313,'2025-06-25','02:31:05',1),(230,11,14,6,'2025-06-25','02:31:54',1),(231,11,14,313,'2025-06-25','02:31:54',1),(232,1,2,6,'2025-06-25','02:32:01',1),(233,11,14,6,'2025-06-25','02:33:44',1),(234,11,14,313,'2025-06-25','02:33:44',1),(235,11,14,6,'2025-06-25','02:35:44',1),(236,11,14,313,'2025-06-25','02:35:44',1),(237,1,2,6,'2025-06-25','02:35:57',1),(238,11,14,6,'2025-06-25','02:36:40',1),(239,11,14,313,'2025-06-25','02:36:40',1),(240,1,2,6,'2025-06-25','02:36:47',1),(241,11,16,6,'2025-06-25','02:37:01',1),(242,11,16,6,'2025-06-25','02:37:29',1),(243,11,16,6,'2025-06-25','02:37:48',1),(244,11,16,6,'2025-06-25','02:37:58',1),(245,11,14,6,'2025-06-25','02:38:05',1),(246,11,14,313,'2025-06-25','02:38:05',1),(247,1,2,6,'2025-06-25','02:38:10',1),(248,11,1,1,'2025-06-25','12:58:38',1),(249,11,14,6,'2025-06-25','12:58:40',1),(250,11,14,313,'2025-06-25','12:58:40',1),(251,11,14,6,'2025-06-25','12:58:47',1),(252,11,14,313,'2025-06-25','12:58:47',1),(253,11,14,6,'2025-06-25','12:58:52',1),(254,11,14,313,'2025-06-25','12:58:52',1),(255,11,14,6,'2025-06-25','12:58:55',1),(256,11,14,313,'2025-06-25','12:58:55',1),(257,11,14,6,'2025-06-25','12:58:57',1),(258,11,14,313,'2025-06-25','12:58:57',1),(259,11,14,6,'2025-06-25','13:38:33',1),(260,11,14,313,'2025-06-25','13:38:33',1),(261,11,14,6,'2025-06-25','13:38:35',1),(262,11,14,313,'2025-06-25','13:38:35',1),(263,11,14,6,'2025-06-25','13:38:37',1),(264,11,14,313,'2025-06-25','13:38:37',1),(265,11,14,6,'2025-06-25','13:39:12',1),(266,11,14,313,'2025-06-25','13:39:12',1),(267,11,14,6,'2025-06-25','13:39:13',1),(268,11,14,313,'2025-06-25','13:39:13',1),(269,11,14,6,'2025-06-25','13:40:16',1),(270,11,14,313,'2025-06-25','13:40:16',1),(271,11,14,6,'2025-06-25','13:40:51',1),(272,11,14,313,'2025-06-25','13:40:51',1),(273,11,14,6,'2025-06-25','13:41:02',1),(274,11,14,313,'2025-06-25','13:41:02',1),(275,11,14,6,'2025-06-25','13:41:38',1),(276,11,14,6,'2025-06-25','13:41:41',1),(277,11,14,313,'2025-06-25','13:41:41',1),(278,11,14,6,'2025-06-25','13:44:05',1),(279,11,14,313,'2025-06-25','13:44:05',1),(280,11,14,6,'2025-06-25','13:44:10',1),(281,11,14,313,'2025-06-25','13:44:10',1),(282,11,14,6,'2025-06-25','13:44:11',1),(283,11,14,313,'2025-06-25','13:44:11',1),(284,11,14,6,'2025-06-25','13:44:24',1),(285,11,14,6,'2025-06-25','13:44:26',1),(286,11,14,313,'2025-06-25','13:44:26',1),(287,11,14,6,'2025-06-25','13:50:25',1),(288,11,14,313,'2025-06-25','13:50:25',1),(289,11,14,6,'2025-06-25','13:50:27',1),(290,11,14,313,'2025-06-25','13:50:27',1),(291,11,14,6,'2025-06-25','13:50:29',1),(292,11,14,6,'2025-06-25','13:50:31',1),(293,11,14,313,'2025-06-25','13:50:31',1),(294,14,1,1,'2025-06-25','13:50:51',1),(295,14,18,6,'2025-06-25','13:50:51',1),(296,14,24,6,'2025-06-25','13:50:53',1),(297,11,14,6,'2025-06-25','13:53:29',1),(298,11,14,313,'2025-06-25','13:53:29',1),(299,11,14,6,'2025-06-25','13:53:32',1),(300,11,14,313,'2025-06-25','13:53:32',1),(301,11,14,6,'2025-06-25','13:53:34',1),(302,11,14,6,'2025-06-25','13:53:37',1),(303,11,14,313,'2025-06-25','13:53:37',1),(304,14,24,6,'2025-06-25','13:53:40',1),(305,11,14,6,'2025-06-25','13:54:14',1),(306,11,14,313,'2025-06-25','13:54:14',1),(307,11,14,6,'2025-06-25','13:54:16',1),(308,11,14,313,'2025-06-25','13:54:16',1),(309,11,14,6,'2025-06-25','13:54:18',1),(310,11,14,313,'2025-06-25','13:54:18',1),(311,11,14,6,'2025-06-25','13:54:27',1),(312,11,14,6,'2025-06-25','13:54:30',1),(313,11,14,313,'2025-06-25','13:54:30',1),(314,14,24,6,'2025-06-25','13:54:35',1),(315,11,14,6,'2025-06-25','13:54:56',1),(316,11,14,313,'2025-06-25','13:54:56',1),(317,11,14,6,'2025-06-25','13:54:59',1),(318,11,14,313,'2025-06-25','13:54:59',1),(319,11,14,6,'2025-06-25','13:55:00',1),(320,11,14,6,'2025-06-25','13:55:02',1),(321,11,14,313,'2025-06-25','13:55:02',1),(322,14,24,6,'2025-06-25','13:55:08',1),(323,11,14,6,'2025-06-25','13:56:05',1),(324,11,14,313,'2025-06-25','13:56:05',1),(325,11,14,6,'2025-06-25','13:56:09',1),(326,11,14,313,'2025-06-25','13:56:09',1),(327,11,14,6,'2025-06-25','13:56:11',1),(328,11,14,6,'2025-06-25','13:56:14',1),(329,11,14,313,'2025-06-25','13:56:14',1),(330,11,14,6,'2025-06-25','13:56:17',1),(331,11,14,313,'2025-06-25','13:56:17',1),(332,11,14,6,'2025-06-25','13:56:18',1),(333,11,14,313,'2025-06-25','13:56:18',1),(334,11,14,6,'2025-06-25','13:56:24',1),(335,11,14,6,'2025-06-25','13:56:26',1),(336,11,14,313,'2025-06-25','13:56:26',1),(337,14,24,6,'2025-06-25','13:56:28',1),(338,14,18,6,'2025-06-25','13:56:43',1),(339,14,19,6,'2025-06-25','13:56:47',1),(340,14,24,6,'2025-06-25','13:56:54',1),(341,11,14,6,'2025-06-25','14:03:24',1),(342,11,14,313,'2025-06-25','14:03:24',1),(343,11,14,6,'2025-06-25','14:05:32',1),(344,11,14,313,'2025-06-25','14:05:32',1),(345,11,14,6,'2025-06-25','14:06:08',1),(346,11,14,313,'2025-06-25','14:06:08',1),(347,11,14,6,'2025-06-25','14:08:38',1),(348,11,14,313,'2025-06-25','14:08:38',1),(349,11,14,6,'2025-06-25','14:08:49',1),(350,11,14,313,'2025-06-25','14:08:49',1),(351,11,14,6,'2025-06-25','14:08:55',1),(352,11,14,313,'2025-06-25','14:08:55',1),(353,11,14,6,'2025-06-25','14:08:56',1),(354,11,14,313,'2025-06-25','14:08:56',1),(355,11,14,6,'2025-06-25','14:09:29',1),(356,11,14,6,'2025-06-25','14:09:32',1),(357,11,14,313,'2025-06-25','14:09:32',1),(358,11,14,6,'2025-06-25','14:09:47',1),(359,11,14,313,'2025-06-25','14:09:47',1),(360,11,14,6,'2025-06-25','14:10:04',1),(361,11,14,313,'2025-06-25','14:10:04',1),(362,11,14,6,'2025-06-25','14:10:05',1),(363,11,14,313,'2025-06-25','14:10:05',1),(364,11,14,6,'2025-06-25','14:10:07',1),(365,11,14,313,'2025-06-25','14:10:07',1),(366,11,14,6,'2025-06-25','14:10:13',1),(367,11,14,6,'2025-06-25','14:10:15',1),(368,11,14,313,'2025-06-25','14:10:15',1),(369,14,24,6,'2025-06-25','14:10:29',1),(370,14,18,6,'2025-06-25','14:10:38',1),(371,14,19,6,'2025-06-25','14:10:42',1),(372,11,14,6,'2025-06-25','14:12:17',1),(373,11,14,313,'2025-06-25','14:12:17',1),(374,11,16,6,'2025-06-25','14:12:31',1),(375,1,1,1,'2025-06-25','14:12:54',1),(376,1,1,6,'2025-06-25','14:12:54',1),(377,1,1,6,'2025-06-25','14:12:55',1),(378,1,11,310,'2025-06-25','14:12:58',1),(379,1,11,310,'2025-06-25','14:13:24',1),(380,1,11,310,'2025-06-25','14:13:29',1),(381,1,1,6,'2025-06-25','14:13:34',1),(382,1,1,6,'2025-06-25','14:13:34',1),(383,1,8,6,'2025-06-25','14:14:22',1),(384,1,10,6,'2025-06-25','14:14:27',1),(385,14,24,6,'2025-06-25','14:14:39',1),(386,14,19,6,'2025-06-25','14:25:43',1),(387,14,25,6,'2025-06-25','14:29:13',1),(388,14,24,6,'2025-06-25','14:29:15',1),(389,14,24,6,'2025-06-25','14:31:42',1),(390,14,24,6,'2025-06-25','14:33:26',1),(391,14,21,6,'2025-06-25','14:33:51',1),(392,14,24,6,'2025-06-25','14:33:53',1),(393,1,2,6,'2025-06-25','14:35:59',1),(394,1,2,6,'2025-06-25','14:45:21',1),(395,1,4,6,'2025-06-25','14:45:24',1),(396,1,5,6,'2025-06-25','14:45:26',1),(397,1,6,6,'2025-06-25','14:45:28',1),(398,1,6,6,'2025-06-25','14:45:40',1),(399,1,13,6,'2025-06-25','14:50:30',1),(400,1,2,6,'2025-06-25','14:50:50',1),(401,1,2,6,'2025-06-25','14:51:15',1),(402,1,4,6,'2025-06-25','14:51:24',1),(403,1,4,6,'2025-06-25','14:51:34',1),(404,1,2,6,'2025-06-25','14:51:37',1),(405,1,2,6,'2025-06-25','14:55:28',1),(406,1,2,6,'2025-06-25','14:57:15',1),(407,1,2,6,'2025-06-25','14:58:38',1),(408,1,2,6,'2025-06-25','14:59:57',1),(409,1,2,6,'2025-06-25','15:00:45',1),(410,1,2,6,'2025-06-25','15:01:05',1),(411,1,2,6,'2025-06-25','15:01:34',1),(412,1,2,6,'2025-06-25','15:01:58',1),(413,1,2,6,'2025-06-25','15:02:06',1),(414,1,2,6,'2025-06-25','15:02:19',1),(415,1,2,6,'2025-06-25','15:05:15',1),(416,1,2,6,'2025-06-25','15:06:53',1),(417,1,2,6,'2025-06-25','15:09:04',1),(418,1,2,6,'2025-06-25','15:09:28',1),(419,1,2,6,'2025-06-25','15:10:50',1),(420,1,2,6,'2025-06-25','15:11:14',1),(421,1,2,6,'2025-06-25','15:11:44',1),(422,1,2,6,'2025-06-25','15:11:58',1),(423,1,2,6,'2025-06-25','15:12:22',1),(424,1,2,6,'2025-06-25','15:12:37',1),(425,1,2,6,'2025-06-25','15:13:10',1),(426,1,2,6,'2025-06-25','15:13:22',1),(427,1,2,6,'2025-06-25','15:13:31',1),(428,1,2,6,'2025-06-25','15:13:38',1),(429,1,2,6,'2025-06-25','15:13:46',1),(430,1,2,6,'2025-06-25','15:13:56',1),(431,1,2,6,'2025-06-25','15:14:05',1),(432,1,2,6,'2025-06-25','15:14:33',1),(433,1,2,6,'2025-06-25','15:15:20',1),(434,1,2,6,'2025-06-25','15:16:12',1),(435,1,2,6,'2025-06-25','15:16:25',1),(436,1,2,6,'2025-06-25','15:17:17',1),(437,1,2,6,'2025-06-25','15:17:43',1),(438,1,2,6,'2025-06-25','15:17:56',1),(439,1,2,6,'2025-06-25','15:18:05',1),(440,1,2,6,'2025-06-25','15:18:14',1),(441,1,2,6,'2025-06-25','15:21:17',1),(442,1,2,6,'2025-06-25','15:22:15',1),(443,1,2,6,'2025-06-25','15:22:34',1),(444,1,2,6,'2025-06-25','15:22:43',1),(445,1,2,6,'2025-06-25','15:23:01',1),(446,1,2,6,'2025-06-25','15:23:11',1),(447,1,2,6,'2025-06-25','15:23:12',1),(448,1,2,6,'2025-06-25','15:23:15',1),(449,1,11,310,'2025-06-25','15:29:00',1),(450,14,1,1,'2025-06-25','15:29:15',1),(451,14,18,6,'2025-06-25','15:29:15',1),(452,14,19,6,'2025-06-25','15:29:16',1),(453,14,21,6,'2025-06-25','15:29:17',1),(454,14,24,6,'2025-06-25','15:29:18',1),(455,14,25,6,'2025-06-25','15:29:19',1),(456,14,18,6,'2025-06-25','15:29:25',1),(457,14,19,6,'2025-06-25','15:29:28',1),(458,1,1,1,'2025-06-25','15:29:46',1),(459,1,1,6,'2025-06-25','15:29:46',1),(460,1,1,6,'2025-06-25','15:29:46',1),(461,1,11,310,'2025-06-25','15:29:50',1),(462,1,11,310,'2025-06-25','15:31:58',1),(463,13,1,1,'2025-06-25','15:34:29',1),(464,13,14,6,'2025-06-25','15:34:29',1),(465,13,3,6,'2025-06-25','15:34:30',1),(466,13,10,6,'2025-06-25','15:34:31',1),(467,13,12,6,'2025-06-25','15:34:32',1),(468,13,15,6,'2025-06-25','15:34:33',1),(469,1,11,310,'2025-06-25','15:34:40',1),(470,13,14,6,'2025-06-25','15:36:54',1),(471,1,2,6,'2025-06-25','15:37:31',1),(472,1,2,6,'2025-06-25','15:37:56',1),(473,13,14,6,'2025-06-25','15:38:48',1),(474,1,2,6,'2025-06-25','15:40:26',1),(475,13,14,6,'2025-06-25','15:50:57',1),(476,13,14,6,'2025-06-25','15:52:09',1),(477,1,2,6,'2025-06-25','15:52:41',1),(478,1,2,6,'2025-06-25','15:53:44',1),(479,1,5,6,'2025-06-25','15:54:50',1),(480,1,4,6,'2025-06-25','15:54:51',1),(481,1,5,6,'2025-06-25','15:54:52',1),(482,1,10,6,'2025-06-25','15:54:53',1),(483,1,11,310,'2025-06-25','15:54:56',1),(484,1,11,310,'2025-06-25','15:55:07',1),(485,1,2,6,'2025-06-25','15:55:14',1),(486,1,4,6,'2025-06-25','15:55:16',1),(487,1,2,6,'2025-06-25','15:55:17',1),(488,13,3,6,'2025-06-25','15:58:02',1),(489,1,11,310,'2025-06-25','15:59:21',1),(490,1,11,310,'2025-06-25','15:59:25',1),(491,1,1,6,'2025-06-25','15:59:35',1),(492,1,1,6,'2025-06-25','15:59:35',1),(493,1,2,6,'2025-06-25','15:59:37',1),(494,1,11,310,'2025-06-25','15:59:38',1),(495,1,11,310,'2025-06-25','16:06:11',1),(496,1,11,310,'2025-06-25','16:06:48',1),(497,1,11,310,'2025-06-25','16:06:51',1),(498,1,11,310,'2025-06-25','16:06:58',1),(499,1,11,310,'2025-06-25','16:07:01',1),(500,1,11,310,'2025-06-25','16:07:07',1),(501,1,11,310,'2025-06-25','16:07:12',1),(502,1,11,310,'2025-06-25','16:07:15',1),(503,1,11,310,'2025-06-25','16:08:04',1),(504,1,11,310,'2025-06-25','16:08:09',1),(505,1,11,310,'2025-06-25','16:08:14',1),(506,13,3,6,'2025-06-25','16:20:12',1),(507,13,3,6,'2025-06-25','16:21:40',1),(508,13,3,6,'2025-06-25','16:25:54',1),(509,13,3,6,'2025-06-25','16:31:03',1),(510,1,13,6,'2025-06-25','16:31:40',1),(511,13,3,91,'2025-06-25','16:40:57',1),(512,13,3,6,'2025-06-25','16:40:57',1),(513,13,3,6,'2025-06-25','16:41:55',1),(514,13,3,6,'2025-06-25','16:44:50',1),(515,13,3,6,'2025-06-25','16:45:42',1),(516,1,13,52,'2025-06-25','16:55:24',1),(517,1,13,59,'2025-06-25','16:56:24',1),(518,13,3,6,'2025-06-25','16:56:43',1),(519,13,3,6,'2025-06-25','17:00:05',1),(520,13,3,6,'2025-06-25','17:03:08',1),(521,13,3,6,'2025-06-25','17:03:58',1),(522,13,3,6,'2025-06-25','17:05:57',1),(523,13,3,6,'2025-06-25','17:06:25',1),(524,13,3,91,'2025-06-25','17:10:41',1),(525,13,3,6,'2025-06-25','17:10:41',1),(526,13,3,6,'2025-06-25','17:11:45',1),(527,13,3,6,'2025-06-25','17:15:25',1),(528,13,3,91,'2025-06-25','17:16:15',1),(529,13,3,6,'2025-06-25','17:16:15',1),(530,13,3,6,'2025-06-25','17:16:57',1),(531,13,3,6,'2025-06-25','17:31:19',1),(532,13,3,6,'2025-06-25','17:31:52',1),(533,13,3,91,'2025-06-25','17:35:23',1),(534,13,3,6,'2025-06-25','17:35:24',1),(535,13,3,6,'2025-06-25','17:42:50',1),(536,13,3,6,'2025-06-25','17:43:25',1),(537,13,3,6,'2025-06-25','17:49:17',1),(538,13,3,6,'2025-06-25','17:54:24',1),(539,13,3,6,'2025-06-25','17:55:55',1),(540,13,3,6,'2025-06-25','17:58:26',1),(541,13,10,6,'2025-06-25','17:58:28',1),(542,13,14,6,'2025-06-25','17:58:46',1),(543,13,14,6,'2025-06-25','18:00:13',1),(544,13,14,6,'2025-06-25','18:00:26',1),(545,13,14,6,'2025-06-25','18:00:49',1),(546,13,14,6,'2025-06-25','18:01:34',1),(547,13,14,6,'2025-06-25','18:01:55',1),(548,13,14,6,'2025-06-25','18:02:07',1),(549,13,14,6,'2025-06-25','18:02:27',1),(550,13,14,6,'2025-06-25','18:02:50',1),(551,13,14,6,'2025-06-25','18:03:09',1),(552,13,14,6,'2025-06-25','18:03:38',1),(553,13,14,6,'2025-06-25','18:03:54',1),(554,14,1,1,'2025-06-25','18:07:20',1),(555,14,18,6,'2025-06-25','18:07:20',1),(556,14,24,6,'2025-06-25','18:07:22',1),(557,1,13,6,'2025-06-25','18:09:12',1),(558,1,10,6,'2025-06-25','18:09:15',1),(559,14,24,6,'2025-06-25','18:13:37',1),(560,14,24,6,'2025-06-25','18:13:45',1),(561,14,24,6,'2025-06-25','18:13:54',1),(562,14,24,6,'2025-06-25','18:14:05',1),(563,1,10,6,'2025-06-25','18:16:23',1),(564,1,10,6,'2025-06-25','18:17:19',1),(565,1,10,6,'2025-06-25','18:18:39',1),(566,1,10,6,'2025-06-25','18:18:48',1),(567,1,10,6,'2025-06-25','18:18:54',1),(568,1,10,6,'2025-06-25','18:18:56',1),(569,1,10,6,'2025-06-25','18:18:59',1),(570,1,10,141,'2025-06-25','18:19:09',1),(571,1,10,6,'2025-06-25','18:19:10',1),(572,14,24,6,'2025-06-25','18:19:26',1),(573,1,10,6,'2025-06-25','18:19:37',1),(574,1,9,6,'2025-06-25','18:19:42',1),(575,1,10,6,'2025-06-25','18:19:45',1),(576,1,10,6,'2025-06-25','18:20:05',1),(577,14,24,6,'2025-06-25','18:20:16',1),(578,14,24,6,'2025-06-25','18:20:19',1),(579,1,10,6,'2025-06-25','18:20:26',1),(580,14,24,6,'2025-06-25','18:24:44',1),(581,14,24,6,'2025-06-25','18:24:53',1),(582,1,10,6,'2025-06-25','18:24:59',1),(583,1,10,6,'2025-06-25','18:27:40',1),(584,1,10,6,'2025-06-25','18:28:25',1),(585,1,10,6,'2025-06-25','18:28:28',1),(586,1,10,141,'2025-06-25','18:28:36',1),(587,1,10,6,'2025-06-25','18:28:38',1),(588,14,24,6,'2025-06-25','18:28:48',1),(589,14,24,6,'2025-06-25','18:29:01',1),(590,1,10,6,'2025-06-25','18:29:06',1),(591,1,10,6,'2025-06-25','18:33:04',1),(592,1,10,6,'2025-06-25','18:33:17',1),(593,1,10,6,'2025-06-25','18:33:22',1),(594,1,10,141,'2025-06-25','18:33:33',1),(595,1,10,6,'2025-06-25','18:33:35',1),(596,14,24,6,'2025-06-25','18:33:48',1),(597,1,10,6,'2025-06-25','18:33:53',1),(598,14,24,6,'2025-06-25','18:34:12',1),(599,1,10,6,'2025-06-25','18:34:15',1),(600,1,10,6,'2025-06-25','18:35:23',1),(601,1,10,141,'2025-06-25','18:35:38',1),(602,1,10,6,'2025-06-25','18:35:40',1),(603,14,24,6,'2025-06-25','18:35:51',1),(604,1,10,6,'2025-06-25','18:35:59',1),(605,14,24,6,'2025-06-25','18:36:19',1),(606,1,10,6,'2025-06-25','18:36:22',1),(607,14,24,6,'2025-06-25','18:36:32',1),(608,1,10,6,'2025-06-25','18:37:17',1),(609,1,10,6,'2025-06-25','18:37:21',1),(610,1,10,6,'2025-06-25','18:37:24',1),(611,1,10,6,'2025-06-25','18:37:28',1),(612,1,10,6,'2025-06-25','18:37:33',1),(613,1,10,6,'2025-06-25','18:37:38',1),(614,1,10,6,'2025-06-25','18:37:41',1),(615,1,10,141,'2025-06-25','18:37:53',1),(616,1,10,6,'2025-06-25','18:37:54',1),(617,14,1,1,'2025-06-25','18:38:01',1),(618,14,18,6,'2025-06-25','18:38:01',1),(619,11,1,1,'2025-06-25','18:38:08',1),(620,11,14,6,'2025-06-25','18:38:08',1),(621,11,17,6,'2025-06-25','18:38:12',1),(622,11,10,6,'2025-06-25','18:38:14',1),(623,1,10,6,'2025-06-25','18:38:42',1),(624,1,10,6,'2025-06-25','18:39:19',1),(625,11,10,6,'2025-06-25','18:40:03',1),(626,1,10,6,'2025-06-25','18:40:28',1),(627,1,10,6,'2025-06-25','18:40:48',1),(628,1,10,6,'2025-06-25','18:40:59',1),(629,1,10,6,'2025-06-25','18:41:02',1),(630,11,10,6,'2025-06-25','18:41:16',1),(631,14,1,1,'2025-06-25','18:41:23',1),(632,14,18,6,'2025-06-25','18:41:23',1),(633,14,24,6,'2025-06-25','18:41:26',1),(634,14,24,6,'2025-06-25','18:41:40',1),(635,1,10,6,'2025-06-25','18:41:47',1),(636,1,10,6,'2025-06-25','18:42:00',1),(637,1,10,6,'2025-06-25','18:42:17',1),(638,1,10,6,'2025-06-25','18:42:20',1),(639,14,24,6,'2025-06-25','18:42:32',1),(640,14,24,6,'2025-06-25','18:42:40',1),(641,14,10,141,'2025-06-25','18:43:14',1),(642,14,24,6,'2025-06-25','18:43:16',1),(643,1,10,6,'2025-06-25','18:43:30',1),(644,2,1,1,'2025-06-25','18:43:38',1),(645,2,1,6,'2025-06-25','18:43:38',1),(646,2,1,6,'2025-06-25','18:43:38',1),(647,2,10,6,'2025-06-25','18:43:41',1),(648,2,10,6,'2025-06-25','18:43:47',1),(649,14,24,6,'2025-06-25','18:45:58',1),(650,1,1,1,'2025-06-25','18:46:43',1),(651,1,1,6,'2025-06-25','18:46:43',1),(652,1,1,6,'2025-06-25','18:46:43',1),(653,1,10,6,'2025-06-25','18:46:45',1),(654,14,24,6,'2025-06-25','18:47:13',1),(655,1,10,6,'2025-06-25','18:47:16',1),(656,1,10,6,'2025-06-25','18:50:13',1),(657,1,10,6,'2025-06-25','18:50:16',1),(658,1,10,6,'2025-06-25','18:54:54',1),(659,1,10,6,'2025-06-25','18:54:56',1),(660,1,10,6,'2025-06-25','18:54:59',1),(661,1,10,6,'2025-06-25','18:56:22',1),(662,1,10,6,'2025-06-25','18:56:26',1),(663,1,10,6,'2025-06-25','18:56:31',1),(664,1,10,6,'2025-06-25','18:57:23',1),(665,1,10,6,'2025-06-25','18:57:26',1),(666,1,10,6,'2025-06-25','18:57:29',1),(667,1,10,6,'2025-06-25','18:57:32',1),(668,1,10,6,'2025-06-25','18:58:15',1),(669,1,10,6,'2025-06-25','18:59:15',1),(670,1,10,6,'2025-06-25','18:59:18',1),(671,1,10,6,'2025-06-25','18:59:53',1),(672,1,1,1,'2025-06-25','19:00:19',1),(673,1,1,6,'2025-06-25','19:00:19',1),(674,1,1,6,'2025-06-25','19:00:19',1),(675,1,1,6,'2025-06-25','19:01:13',1),(676,1,1,6,'2025-06-25','19:01:13',1),(677,1,10,6,'2025-06-25','19:01:17',1),(678,1,10,6,'2025-06-25','19:01:29',1),(679,1,10,6,'2025-06-25','19:01:33',1),(680,1,10,6,'2025-06-25','19:01:36',1),(681,1,10,6,'2025-06-25','19:02:05',1),(682,1,10,6,'2025-06-25','19:02:10',1),(683,1,10,6,'2025-06-25','19:02:17',1),(684,1,10,6,'2025-06-25','19:02:30',1),(685,1,10,6,'2025-06-25','19:03:30',1),(686,1,10,6,'2025-06-25','19:03:33',1),(687,3,1,1,'2025-06-25','19:03:49',1),(688,3,1,6,'2025-06-25','19:03:50',1),(689,3,1,6,'2025-06-25','19:03:50',1),(690,3,12,6,'2025-06-25','19:03:52',1),(691,1,10,6,'2025-06-25','19:03:59',1),(692,1,1,1,'2025-06-25','19:04:22',1),(693,1,1,6,'2025-06-25','19:04:22',1),(694,1,1,6,'2025-06-25','19:04:22',1),(695,1,13,6,'2025-06-25','19:04:28',1),(696,1,10,6,'2025-06-25','19:04:31',1),(697,1,10,6,'2025-06-25','19:04:37',1),(698,1,10,6,'2025-06-25','19:04:39',1),(699,3,10,6,'2025-06-25','19:06:13',1),(700,3,10,6,'2025-06-25','19:06:19',1),(701,1,10,6,'2025-06-25','19:06:49',1),(702,1,11,310,'2025-06-25','19:11:00',1),(703,1,11,310,'2025-06-25','19:11:11',1),(704,1,11,310,'2025-06-25','19:11:21',1),(705,1,11,310,'2025-06-25','19:11:28',1),(706,1,11,310,'2025-06-25','19:11:45',1),(707,1,11,310,'2025-06-25','19:14:50',1),(708,1,1,1,'2025-06-25','19:14:58',1),(709,1,1,6,'2025-06-25','19:14:58',1),(710,1,1,6,'2025-06-25','19:14:58',1),(711,1,11,310,'2025-06-25','19:15:00',1),(712,1,1,3,'2025-06-25','19:18:08',1),(713,1,1,1,'2025-06-25','19:18:16',1),(714,1,1,6,'2025-06-25','19:18:16',1),(715,1,1,6,'2025-06-25','19:18:16',1),(716,1,11,310,'2025-06-25','19:18:20',1),(717,1,1,3,'2025-06-25','19:18:31',1),(718,1,1,1,'2025-06-25','19:18:39',1),(719,1,1,6,'2025-06-25','19:18:39',1),(720,1,1,6,'2025-06-25','19:18:39',1),(721,1,13,6,'2025-06-25','19:18:42',1),(722,1,11,310,'2025-06-25','19:18:44',1),(723,1,1,3,'2025-06-25','19:19:00',1),(724,1,1,1,'2025-06-25','19:19:06',1),(725,1,1,6,'2025-06-25','19:19:06',1),(726,1,1,6,'2025-06-25','19:19:06',1),(727,1,11,310,'2025-06-25','19:19:09',1),(728,1,11,310,'2025-06-25','19:19:26',1),(729,1,11,310,'2025-06-25','19:19:33',1),(730,1,2,6,'2025-06-25','23:02:35',1),(731,1,2,6,'2025-06-25','23:02:42',1),(732,1,2,6,'2025-06-25','23:03:50',1),(733,1,4,6,'2025-06-25','23:04:43',1),(734,15,1,1,'2025-06-25','23:06:17',1),(735,15,18,6,'2025-06-25','23:06:17',1),(736,15,18,6,'2025-06-25','23:07:56',1),(737,15,18,6,'2025-06-25','23:08:02',1),(738,15,18,6,'2025-06-25','23:08:06',1),(739,1,4,6,'2025-06-25','23:08:18',1),(740,1,1,3,'2025-06-25','23:08:20',1),(741,13,1,1,'2025-06-25','23:08:29',1),(742,13,14,6,'2025-06-25','23:08:29',1),(743,13,23,6,'2025-06-25','23:08:41',1),(744,13,23,6,'2025-06-25','23:08:43',1),(745,13,15,6,'2025-06-25','23:08:55',1),(746,13,15,6,'2025-06-25','23:09:13',1),(747,13,23,6,'2025-06-25','23:09:25',1),(748,13,23,6,'2025-06-25','23:09:27',1),(749,13,23,6,'2025-06-25','23:09:38',1),(750,15,18,6,'2025-06-25','23:09:49',1),(751,15,19,6,'2025-06-25','23:09:54',1),(752,15,19,6,'2025-06-25','23:11:31',1),(753,13,1,3,'2025-06-25','23:11:52',1),(754,11,1,2,'2025-06-25','23:12:01',0),(755,11,1,1,'2025-06-25','23:12:07',1),(756,11,14,6,'2025-06-25','23:12:07',1),(757,11,14,6,'2025-06-25','23:12:12',1),(758,11,14,6,'2025-06-25','23:12:21',1),(759,11,14,78,'2025-06-25','23:12:23',1),(760,11,14,6,'2025-06-25','23:12:23',1),(761,11,14,6,'2025-06-25','23:15:27',1),(762,11,14,6,'2025-06-25','23:15:42',1),(763,11,14,6,'2025-06-25','23:15:45',1),(764,11,14,6,'2025-06-25','23:15:51',1),(765,11,14,6,'2025-06-25','23:16:17',1),(766,11,14,80,'2025-06-25','23:16:19',1),(767,11,14,80,'2025-06-25','23:16:21',1),(768,11,14,80,'2025-06-25','23:16:23',1),(769,11,14,80,'2025-06-25','23:16:25',1),(770,11,14,6,'2025-06-25','23:16:30',1),(771,11,1,3,'2025-06-25','23:16:44',1),(772,1,1,1,'2025-06-25','23:16:52',1),(773,1,1,6,'2025-06-25','23:16:52',1),(774,1,1,6,'2025-06-25','23:16:52',1),(775,1,10,6,'2025-06-25','23:16:59',1),(776,1,4,6,'2025-06-25','23:17:04',1),(777,1,1,6,'2025-06-25','23:17:09',1),(778,1,1,6,'2025-06-25','23:17:09',1),(779,1,2,6,'2025-06-25','23:17:19',1),(780,1,4,6,'2025-06-25','23:17:25',1),(781,1,5,6,'2025-06-25','23:17:31',1),(782,1,4,6,'2025-06-25','23:17:32',1),(783,1,4,6,'2025-06-25','23:17:34',1),(784,1,5,6,'2025-06-25','23:17:35',1),(785,1,4,6,'2025-06-25','23:17:36',1),(786,1,2,6,'2025-06-25','23:17:36',1),(787,1,4,6,'2025-06-25','23:18:21',1),(788,1,5,6,'2025-06-25','23:18:22',1),(789,1,6,6,'2025-06-25','23:18:23',1),(790,15,1,3,'2025-06-25','23:19:52',1),(791,13,1,1,'2025-06-25','23:20:13',1),(792,13,14,6,'2025-06-25','23:20:13',1),(793,11,1,1,'2025-06-25','23:20:31',1),(794,11,14,6,'2025-06-25','23:20:31',1),(795,11,16,6,'2025-06-25','23:20:38',1),(796,11,16,6,'2025-06-25','23:20:43',1),(797,11,16,6,'2025-06-25','23:20:45',1),(798,11,16,6,'2025-06-25','23:20:53',1),(799,11,16,6,'2025-06-25','23:20:57',1),(800,11,16,6,'2025-06-25','23:22:18',1),(801,13,14,6,'2025-06-25','23:24:09',1),(802,13,3,6,'2025-06-25','23:24:15',1),(803,13,14,6,'2025-06-25','23:28:18',1),(804,13,14,6,'2025-06-25','23:29:55',1),(805,13,15,6,'2025-06-25','23:30:03',1),(806,13,20,6,'2025-06-25','23:30:44',1),(807,13,3,6,'2025-06-25','23:32:12',1),(808,11,17,6,'2025-06-25','23:32:32',1),(809,11,14,6,'2025-06-25','23:32:41',1),(810,11,14,6,'2025-06-25','23:33:38',1),(811,11,17,6,'2025-06-25','23:33:42',1),(812,11,16,6,'2025-06-25','23:34:10',1),(813,11,16,6,'2025-06-25','23:34:27',1),(814,11,16,6,'2025-06-25','23:34:29',1),(815,11,16,6,'2025-06-25','23:34:32',1),(816,11,10,6,'2025-06-25','23:34:34',1),(817,11,10,6,'2025-06-25','23:36:39',1),(818,11,17,6,'2025-06-25','23:36:43',1),(819,11,16,6,'2025-06-25','23:36:46',1),(820,13,10,6,'2025-06-25','23:38:51',1),(821,13,12,6,'2025-06-25','23:38:54',1),(822,13,14,6,'2025-06-25','23:38:56',1),(823,13,15,6,'2025-06-25','23:39:09',1),(824,13,20,6,'2025-06-25','23:39:14',1),(825,13,23,6,'2025-06-25','23:39:18',1),(826,13,20,6,'2025-06-25','23:39:19',1),(827,13,20,6,'2025-06-25','23:41:19',1),(828,13,23,6,'2025-06-25','23:41:24',1),(829,13,23,6,'2025-06-25','23:44:47',1),(830,13,23,6,'2025-06-25','23:45:26',1),(831,13,23,6,'2025-06-25','23:46:23',1),(832,13,23,6,'2025-06-25','23:47:03',1),(833,13,15,6,'2025-06-25','23:47:10',1),(834,13,3,6,'2025-06-25','23:47:13',1),(835,13,23,6,'2025-06-25','23:47:20',1),(836,13,20,6,'2025-06-25','23:47:23',1),(837,13,3,6,'2025-06-25','23:47:35',1),(838,13,15,6,'2025-06-25','23:47:39',1),(839,13,23,6,'2025-06-25','23:47:43',1),(840,13,3,6,'2025-06-25','23:47:51',1),(841,13,23,6,'2025-06-25','23:48:03',1),(842,1,6,6,'2025-06-25','23:50:46',1),(843,1,7,6,'2025-06-25','23:51:05',1),(844,1,7,6,'2025-06-26','00:07:49',1),(845,1,8,6,'2025-06-26','00:08:00',1),(846,1,9,6,'2025-06-26','00:08:05',1),(847,1,10,6,'2025-06-26','00:08:20',1),(848,1,11,310,'2025-06-26','00:08:24',1),(849,1,11,310,'2025-06-26','00:08:37',1),(850,1,11,310,'2025-06-26','00:08:41',1),(851,1,12,6,'2025-06-26','00:09:04',1),(852,1,20,6,'2025-06-26','00:09:09',1),(853,1,11,310,'2025-06-26','00:09:17',1),(854,1,11,310,'2025-06-26','00:09:21',1),(855,1,13,6,'2025-06-26','00:09:25',1),(856,1,20,6,'2025-06-26','00:09:26',1),(857,1,22,6,'2025-06-26','00:09:30',1),(858,1,20,6,'2025-06-26','00:09:41',1),(859,1,11,310,'2025-06-26','00:09:46',1),(860,1,11,310,'2025-06-26','00:09:55',1),(861,1,11,310,'2025-06-26','00:09:58',1),(862,13,23,6,'2025-06-26','00:11:12',1),(863,1,11,310,'2025-06-26','00:11:16',1),(864,1,1,3,'2025-06-26','00:11:35',1),(865,1,1,1,'2025-06-26','00:11:42',1),(866,1,1,6,'2025-06-26','00:11:42',1),(867,1,1,6,'2025-06-26','00:11:42',1),(868,1,11,310,'2025-06-26','00:11:46',1),(869,1,11,310,'2025-06-26','00:11:52',1),(870,1,11,310,'2025-06-26','00:11:56',1),(871,1,11,310,'2025-06-26','00:12:00',1),(872,1,11,310,'2025-06-26','00:12:50',1),(873,1,11,310,'2025-06-26','00:12:54',1),(874,1,11,310,'2025-06-26','00:12:58',1),(875,1,11,310,'2025-06-26','00:13:06',1),(876,1,22,6,'2025-06-26','00:13:22',1),(877,1,20,6,'2025-06-26','00:13:29',1),(878,1,11,310,'2025-06-26','00:13:34',1),(879,1,11,310,'2025-06-26','00:13:43',1),(880,1,13,6,'2025-06-26','00:13:56',1),(881,1,4,6,'2025-06-26','00:24:55',1),(882,1,6,6,'2025-06-26','00:27:31',1),(883,1,6,6,'2025-06-26','00:28:25',1),(884,1,6,6,'2025-06-26','00:35:04',1),(885,1,6,6,'2025-06-26','00:35:54',1),(886,1,6,6,'2025-06-26','00:37:48',1),(887,1,6,6,'2025-06-26','00:37:50',1),(888,1,6,6,'2025-06-26','00:38:07',1),(889,1,6,6,'2025-06-26','00:38:10',1),(890,1,20,6,'2025-06-26','00:42:54',1),(891,1,20,6,'2025-06-26','00:43:06',1),(892,13,20,6,'2025-06-26','00:43:06',1),(893,13,20,6,'2025-06-26','00:43:13',1),(894,1,20,6,'2025-06-26','00:43:13',1),(895,13,20,6,'2025-06-26','00:43:18',1),(896,1,20,6,'2025-06-26','00:43:22',1),(897,13,20,6,'2025-06-26','00:43:28',1),(898,13,20,6,'2025-06-26','00:43:34',1),(899,13,20,6,'2025-06-26','00:43:41',1),(900,1,20,6,'2025-06-26','00:43:47',1),(901,1,20,6,'2025-06-26','00:43:53',1),(902,13,20,6,'2025-06-26','00:44:16',1),(903,13,20,6,'2025-06-26','00:45:14',1),(904,13,20,6,'2025-06-26','00:45:19',1),(905,13,20,6,'2025-06-26','00:46:06',1),(906,13,1,1,'2025-06-26','00:53:41',1),(907,13,14,6,'2025-06-26','00:53:42',1),(908,13,20,6,'2025-06-26','00:53:44',1),(909,13,23,6,'2025-06-26','00:53:50',1),(910,13,20,6,'2025-06-26','00:53:56',1),(911,13,20,6,'2025-06-26','00:54:03',1),(912,13,20,6,'2025-06-26','00:54:09',1),(913,13,20,6,'2025-06-26','00:54:14',1),(914,13,20,6,'2025-06-26','00:54:23',1),(915,13,20,6,'2025-06-26','00:54:30',1),(916,13,20,6,'2025-06-26','00:54:36',1),(917,13,20,6,'2025-06-26','00:54:41',1),(918,13,20,6,'2025-06-26','00:55:19',1),(919,13,20,6,'2025-06-26','00:55:53',1),(920,13,20,6,'2025-06-26','00:56:17',1),(921,13,1,1,'2025-06-26','00:56:40',1),(922,13,14,6,'2025-06-26','00:56:40',1),(923,13,20,6,'2025-06-26','00:56:42',1),(924,13,14,6,'2025-06-26','00:56:47',1),(925,13,15,6,'2025-06-26','00:56:49',1),(926,13,20,6,'2025-06-26','00:56:52',1),(927,13,20,6,'2025-06-26','00:56:56',1),(928,13,15,6,'2025-06-26','00:57:02',1),(929,13,15,6,'2025-06-26','00:57:04',1),(930,13,23,6,'2025-06-26','01:03:44',1),(931,13,20,6,'2025-06-26','01:03:48',1),(932,13,15,6,'2025-06-26','01:04:30',1),(933,13,20,6,'2025-06-26','01:04:32',1),(934,13,15,6,'2025-06-26','01:04:44',1),(935,13,20,6,'2025-06-26','01:04:55',1),(936,13,10,6,'2025-06-26','01:05:03',1),(937,13,23,6,'2025-06-26','01:05:03',1),(938,13,14,6,'2025-06-26','01:05:06',1),(939,13,15,6,'2025-06-26','01:05:07',1),(940,13,20,6,'2025-06-26','01:05:08',1),(941,13,20,6,'2025-06-26','01:05:15',1),(942,13,23,6,'2025-06-26','01:05:21',1),(943,13,20,6,'2025-06-26','01:05:23',1),(944,13,20,6,'2025-06-26','01:05:30',1),(945,13,20,6,'2025-06-26','01:05:53',1),(946,13,20,6,'2025-06-26','01:06:19',1),(947,13,20,6,'2025-06-26','01:06:27',1),(948,13,23,6,'2025-06-26','01:06:34',1),(949,13,20,6,'2025-06-26','01:06:37',1),(950,13,23,6,'2025-06-26','01:06:44',1),(951,13,23,6,'2025-06-26','01:12:26',1),(952,13,20,6,'2025-06-26','01:12:27',1),(953,13,23,6,'2025-06-26','01:12:34',1),(954,13,1,3,'2025-06-26','01:13:35',1),(955,1,1,1,'2025-06-26','01:13:47',1),(956,1,1,6,'2025-06-26','01:13:47',1),(957,1,1,6,'2025-06-26','01:13:47',1),(958,1,4,6,'2025-06-26','01:13:49',1),(959,1,4,6,'2025-06-26','01:14:48',1),(960,1,20,6,'2025-06-26','01:14:57',1),(961,1,1,3,'2025-06-26','01:15:02',1),(962,13,1,1,'2025-06-26','01:15:12',1),(963,13,14,6,'2025-06-26','01:15:12',1),(964,13,20,6,'2025-06-26','01:15:13',1),(965,13,23,6,'2025-06-26','01:15:19',1),(966,13,20,6,'2025-06-26','01:15:19',1),(967,13,20,6,'2025-06-26','01:15:24',1),(968,13,15,6,'2025-06-26','01:15:29',1),(969,13,20,6,'2025-06-26','01:15:30',1),(970,13,1,1,'2025-06-26','14:21:46',1),(971,13,14,6,'2025-06-26','14:21:46',1),(972,13,20,6,'2025-06-26','14:21:48',1),(973,13,14,6,'2025-06-26','14:21:58',1),(974,13,1,1,'2025-06-26','14:21:58',1),(975,13,14,6,'2025-06-26','14:21:58',1),(976,13,12,6,'2025-06-26','14:21:59',1),(977,13,15,6,'2025-06-26','14:22:01',1),(978,13,20,6,'2025-06-26','14:23:22',1),(979,13,20,6,'2025-06-26','14:23:28',1),(980,13,15,6,'2025-06-26','14:24:35',1),(981,13,20,6,'2025-06-26','14:24:36',1),(982,13,15,6,'2025-06-26','14:24:42',1),(983,13,23,6,'2025-06-26','14:24:43',1),(984,13,15,6,'2025-06-26','14:24:44',1),(985,13,15,6,'2025-06-26','14:28:33',1),(986,13,20,6,'2025-06-26','14:28:34',1),(987,13,15,6,'2025-06-26','14:28:41',1),(988,13,20,6,'2025-06-26','14:28:41',1),(989,13,15,6,'2025-06-26','14:29:38',1),(990,13,20,6,'2025-06-26','14:29:39',1),(991,13,20,6,'2025-06-26','14:29:46',1),(992,13,15,6,'2025-06-26','14:29:53',1),(993,13,20,6,'2025-06-26','14:30:21',1),(994,13,15,6,'2025-06-26','14:30:26',1),(995,13,14,6,'2025-06-26','14:30:28',1),(996,13,15,6,'2025-06-26','14:30:28',1),(997,13,20,6,'2025-06-26','14:30:30',1),(998,13,15,6,'2025-06-26','14:30:37',1),(999,13,15,6,'2025-06-26','14:31:31',1),(1000,13,20,6,'2025-06-26','14:31:32',1),(1001,13,20,6,'2025-06-26','14:31:37',1),(1002,13,15,6,'2025-06-26','14:32:26',1),(1003,13,14,6,'2025-06-26','14:32:28',1),(1004,13,20,6,'2025-06-26','14:32:29',1),(1005,13,15,6,'2025-06-26','14:32:35',1),(1006,13,15,6,'2025-06-26','14:34:11',1),(1007,13,20,6,'2025-06-26','14:34:12',1),(1008,13,20,6,'2025-06-26','14:34:36',1),(1009,13,20,6,'2025-06-26','14:36:57',1),(1010,13,20,6,'2025-06-26','14:37:03',1),(1011,13,20,6,'2025-06-26','14:37:19',1),(1012,13,20,6,'2025-06-26','14:37:43',1),(1013,13,14,6,'2025-06-26','14:37:49',1),(1014,13,15,6,'2025-06-26','14:37:51',1),(1015,13,20,6,'2025-06-26','14:37:52',1),(1016,13,15,6,'2025-06-26','14:37:57',1),(1017,13,20,6,'2025-06-26','14:41:13',1),(1018,13,15,6,'2025-06-26','14:42:20',1),(1019,13,15,6,'2025-06-26','14:43:17',1),(1020,13,20,6,'2025-06-26','14:46:53',1),(1021,13,15,6,'2025-06-26','14:47:25',1),(1022,13,14,6,'2025-06-26','14:47:26',1),(1023,13,15,6,'2025-06-26','14:55:10',1),(1024,13,15,6,'2025-06-26','15:02:18',1),(1025,13,14,6,'2025-06-26','15:02:22',1),(1026,13,23,6,'2025-06-26','15:02:25',1),(1027,13,20,189,'2025-06-26','15:06:44',1),(1028,14,1,1,'2025-06-26','15:08:10',1),(1029,14,18,6,'2025-06-26','15:08:10',1),(1030,14,25,6,'2025-06-26','15:08:13',1),(1031,14,25,6,'2025-06-26','15:08:34',1),(1032,13,23,6,'2025-06-26','15:09:28',1),(1033,13,23,6,'2025-06-26','15:12:55',1),(1034,13,23,6,'2025-06-26','15:14:11',1),(1035,13,23,6,'2025-06-26','15:15:12',1),(1036,13,23,6,'2025-06-26','15:15:14',1),(1037,13,23,6,'2025-06-26','15:15:16',1),(1038,13,23,6,'2025-06-26','15:15:16',1),(1039,13,23,6,'2025-06-26','15:15:17',1),(1040,13,23,6,'2025-06-26','15:15:23',1),(1041,13,23,6,'2025-06-26','15:15:25',1),(1042,13,23,6,'2025-06-26','15:15:25',1),(1043,13,23,6,'2025-06-26','15:15:29',1),(1044,13,23,6,'2025-06-26','15:15:30',1),(1045,13,23,6,'2025-06-26','15:15:33',1),(1046,13,23,6,'2025-06-26','15:16:24',1),(1047,13,23,6,'2025-06-26','15:16:26',1),(1048,13,23,6,'2025-06-26','15:16:39',1),(1049,13,23,6,'2025-06-26','15:16:40',1),(1050,13,1,6,'2025-06-26','15:16:42',1),(1051,13,1,6,'2025-06-26','15:16:42',1),(1052,13,1,6,'2025-06-26','15:16:43',1),(1053,13,1,6,'2025-06-26','15:16:43',1),(1054,13,23,6,'2025-06-26','15:16:53',1),(1055,13,23,6,'2025-06-26','15:16:55',1),(1056,13,23,6,'2025-06-26','15:16:57',1),(1057,13,23,6,'2025-06-26','15:17:56',1),(1058,13,23,6,'2025-06-26','15:17:58',1),(1059,13,23,6,'2025-06-26','15:18:03',1),(1060,13,23,6,'2025-06-26','15:18:06',1),(1061,13,23,6,'2025-06-26','15:20:31',1),(1062,13,23,6,'2025-06-26','15:21:54',1),(1063,13,23,6,'2025-06-26','15:21:57',1),(1064,13,23,6,'2025-06-26','15:21:59',1),(1065,13,23,6,'2025-06-26','15:22:03',1),(1066,13,23,6,'2025-06-26','15:22:05',1),(1067,13,23,6,'2025-06-26','15:23:40',1),(1068,13,23,6,'2025-06-26','15:23:46',1),(1069,13,23,6,'2025-06-26','15:23:48',1),(1070,13,23,6,'2025-06-26','15:23:51',1),(1071,13,23,6,'2025-06-26','15:24:28',1),(1072,13,23,6,'2025-06-26','15:24:31',1),(1073,13,23,6,'2025-06-26','15:24:33',1),(1074,13,23,6,'2025-06-26','15:24:36',1),(1075,13,23,6,'2025-06-26','15:24:38',1),(1076,13,23,6,'2025-06-26','15:38:24',1),(1077,13,1,3,'2025-06-26','15:38:27',1),(1078,1,1,1,'2025-06-26','15:38:33',1),(1079,1,1,6,'2025-06-26','15:38:34',1),(1080,1,1,6,'2025-06-26','15:38:34',1),(1081,1,4,6,'2025-06-26','15:38:35',1),(1082,1,6,6,'2025-06-26','15:38:41',1),(1083,1,1,6,'2025-06-26','15:38:49',1),(1084,1,1,6,'2025-06-26','15:38:49',1),(1085,1,1,6,'2025-06-26','15:39:16',1),(1086,1,1,6,'2025-06-26','15:39:16',1),(1087,1,1,6,'2025-06-26','15:39:28',1),(1088,1,1,6,'2025-06-26','15:39:28',1),(1089,1,4,6,'2025-06-26','15:41:55',1),(1090,1,4,6,'2025-06-26','15:43:04',1),(1091,1,5,6,'2025-06-26','15:43:07',1),(1092,1,4,6,'2025-06-26','15:43:27',1),(1093,1,2,6,'2025-06-26','15:43:30',1),(1094,1,6,6,'2025-06-26','15:43:50',1),(1095,1,1,6,'2025-06-26','15:43:53',1),(1096,1,1,6,'2025-06-26','15:43:53',1),(1097,1,1,3,'2025-06-26','15:46:35',1),(1098,12,1,2,'2025-06-26','15:46:44',0),(1099,12,1,1,'2025-06-26','15:46:56',1),(1100,12,14,6,'2025-06-26','15:46:56',1),(1101,12,1,3,'2025-06-26','15:46:59',1),(1102,1,1,1,'2025-06-26','15:47:08',1),(1103,1,1,6,'2025-06-26','15:47:08',1),(1104,1,1,6,'2025-06-26','15:47:08',1),(1105,1,6,6,'2025-06-26','15:47:59',1),(1106,1,6,6,'2025-06-26','15:48:22',1),(1107,1,1,6,'2025-06-26','15:48:32',1),(1108,1,1,6,'2025-06-26','15:48:32',1),(1109,1,6,6,'2025-06-26','15:54:07',1),(1110,1,6,6,'2025-06-26','15:54:11',1),(1111,1,6,6,'2025-06-26','15:55:43',1),(1112,1,6,6,'2025-06-26','15:55:46',1),(1113,1,6,6,'2025-06-26','15:55:54',1),(1114,1,6,6,'2025-06-26','15:56:35',1),(1115,1,6,6,'2025-06-26','15:57:45',1),(1116,1,6,6,'2025-06-26','15:58:13',1),(1117,1,6,6,'2025-06-26','15:58:16',1),(1118,1,7,6,'2025-06-26','15:58:21',1),(1119,1,6,6,'2025-06-26','15:58:25',1),(1120,1,6,6,'2025-06-26','15:58:30',1),(1121,1,6,6,'2025-06-26','15:58:33',1),(1122,1,7,6,'2025-06-26','15:59:20',1),(1123,1,7,6,'2025-06-26','16:04:59',1),(1124,1,1,6,'2025-06-26','16:05:02',1),(1125,1,1,6,'2025-06-26','16:05:02',1),(1126,1,7,6,'2025-06-26','16:06:38',1),(1127,1,1,6,'2025-06-26','16:06:42',1),(1128,1,1,6,'2025-06-26','16:06:42',1),(1129,1,7,6,'2025-06-26','16:07:30',1),(1130,1,7,6,'2025-06-26','16:07:33',1),(1131,1,7,6,'2025-06-26','16:07:42',1),(1132,1,7,6,'2025-06-26','16:09:01',1),(1133,1,7,6,'2025-06-26','16:09:03',1),(1134,1,7,6,'2025-06-26','16:10:27',1),(1135,1,7,6,'2025-06-26','16:10:30',1),(1136,1,7,6,'2025-06-26','16:11:52',1),(1137,1,7,6,'2025-06-26','16:11:55',1),(1138,1,7,6,'2025-06-26','16:11:59',1),(1139,1,7,6,'2025-06-26','16:12:03',1),(1140,1,7,6,'2025-06-26','16:12:06',1),(1141,1,7,6,'2025-06-26','16:12:10',1),(1142,1,7,6,'2025-06-26','16:12:17',1),(1143,1,7,6,'2025-06-26','16:12:21',1),(1144,14,1,3,'2025-06-26','16:16:34',1),(1145,13,1,1,'2025-06-26','16:16:41',1),(1146,13,14,6,'2025-06-26','16:16:41',1),(1147,13,15,6,'2025-06-26','16:16:43',1),(1148,13,15,6,'2025-06-26','16:16:46',1),(1149,13,15,6,'2025-06-26','16:16:49',1),(1150,13,15,6,'2025-06-26','16:16:51',1),(1151,13,15,6,'2025-06-26','16:16:54',1),(1152,13,15,6,'2025-06-26','16:16:58',1),(1153,13,15,6,'2025-06-26','16:17:01',1),(1154,13,15,6,'2025-06-26','16:17:05',1),(1155,13,15,6,'2025-06-26','16:17:07',1),(1156,13,15,6,'2025-06-26','16:17:12',1),(1157,13,15,6,'2025-06-26','16:17:14',1),(1158,13,15,6,'2025-06-26','16:17:20',1),(1159,13,15,6,'2025-06-26','16:17:22',1),(1160,13,15,6,'2025-06-26','16:17:25',1),(1161,13,15,6,'2025-06-26','16:17:29',1),(1162,13,15,6,'2025-06-26','16:17:32',1),(1163,13,15,6,'2025-06-26','16:17:41',1),(1164,13,15,6,'2025-06-26','16:17:45',1),(1165,13,15,6,'2025-06-26','16:17:48',1),(1166,13,15,6,'2025-06-26','16:17:52',1),(1167,1,7,6,'2025-06-26','16:20:24',1),(1168,13,15,6,'2025-06-26','16:20:28',1),(1169,13,15,6,'2025-06-26','16:20:31',1),(1170,13,15,6,'2025-06-26','16:20:35',1),(1171,13,15,6,'2025-06-26','16:20:38',1),(1172,13,15,6,'2025-06-26','16:20:40',1),(1173,1,7,6,'2025-06-26','16:21:34',1),(1174,13,15,6,'2025-06-26','16:21:38',1),(1175,13,15,6,'2025-06-26','16:21:41',1),(1176,13,15,6,'2025-06-26','16:21:44',1),(1177,13,15,6,'2025-06-26','16:21:46',1),(1178,13,15,6,'2025-06-26','16:23:33',1),(1179,13,15,6,'2025-06-26','16:23:37',1),(1180,13,15,6,'2025-06-26','16:23:39',1),(1181,13,15,6,'2025-06-26','16:23:41',1),(1182,13,15,6,'2025-06-26','16:23:44',1),(1183,13,15,6,'2025-06-26','16:24:11',1),(1184,13,15,6,'2025-06-26','16:24:14',1),(1185,13,15,6,'2025-06-26','16:24:16',1),(1186,13,15,6,'2025-06-26','16:24:21',1),(1187,1,7,6,'2025-06-26','16:25:46',1),(1188,13,15,6,'2025-06-26','16:25:50',1),(1189,13,15,6,'2025-06-26','16:25:52',1),(1190,13,15,6,'2025-06-26','16:25:54',1),(1191,13,15,6,'2025-06-26','16:25:57',1),(1192,13,15,6,'2025-06-26','16:25:59',1),(1193,1,10,6,'2025-06-26','16:26:14',1),(1194,1,10,6,'2025-06-26','16:26:47',1),(1195,1,10,6,'2025-06-26','16:28:53',1),(1196,1,10,6,'2025-06-26','16:28:56',1),(1197,1,10,6,'2025-06-26','16:29:00',1),(1198,1,10,6,'2025-06-26','16:29:04',1),(1199,1,10,6,'2025-06-26','16:29:07',1),(1200,1,10,6,'2025-06-26','16:29:10',1),(1201,1,10,6,'2025-06-26','16:29:13',1),(1202,1,10,6,'2025-06-26','16:29:16',1),(1203,1,10,6,'2025-06-26','16:29:19',1),(1204,1,10,6,'2025-06-26','16:29:22',1),(1205,1,10,6,'2025-06-26','16:29:24',1),(1206,1,10,6,'2025-06-26','16:29:29',1),(1207,1,10,6,'2025-06-26','16:29:40',1),(1208,1,10,6,'2025-06-26','16:29:44',1),(1209,1,10,6,'2025-06-26','16:29:47',1),(1210,1,10,6,'2025-06-26','16:31:47',1),(1211,1,10,6,'2025-06-26','16:32:08',1),(1212,1,10,6,'2025-06-26','16:32:14',1),(1213,1,10,6,'2025-06-26','16:35:27',1),(1214,1,10,6,'2025-06-26','16:35:30',1),(1215,1,10,6,'2025-06-26','16:35:36',1),(1216,1,10,6,'2025-06-26','16:35:43',1),(1217,1,10,6,'2025-06-26','16:35:47',1),(1218,1,10,6,'2025-06-26','16:35:53',1),(1219,1,10,6,'2025-06-26','16:35:56',1),(1220,1,10,6,'2025-06-26','16:35:59',1),(1221,1,10,6,'2025-06-26','16:36:04',1),(1222,1,10,6,'2025-06-26','16:36:07',1),(1223,1,10,6,'2025-06-26','16:36:45',1),(1224,1,10,6,'2025-06-26','16:36:50',1),(1225,1,10,6,'2025-06-26','16:36:54',1),(1226,1,10,6,'2025-06-26','16:37:27',1),(1227,1,10,6,'2025-06-26','16:38:22',1),(1228,1,10,6,'2025-06-26','16:38:52',1),(1229,1,10,6,'2025-06-26','16:41:24',1),(1230,13,14,6,'2025-06-26','16:42:09',1),(1231,13,14,6,'2025-06-26','16:51:38',1),(1232,13,14,6,'2025-06-26','16:52:52',1),(1233,13,14,6,'2025-06-26','16:53:44',1),(1234,1,8,6,'2025-06-26','16:53:50',1),(1235,13,14,6,'2025-06-26','16:54:20',1),(1236,13,14,6,'2025-06-26','16:56:03',1),(1237,13,14,6,'2025-06-26','16:57:47',1),(1238,13,14,6,'2025-06-26','16:58:02',1),(1239,13,14,6,'2025-06-26','17:01:05',1),(1240,13,14,6,'2025-06-26','17:01:14',1),(1241,13,14,6,'2025-06-26','17:01:16',1),(1242,13,14,6,'2025-06-26','17:01:18',1),(1243,13,14,6,'2025-06-26','17:01:28',1),(1244,13,14,6,'2025-06-26','17:01:30',1),(1245,13,14,6,'2025-06-26','17:01:35',1),(1246,13,14,6,'2025-06-26','17:01:40',1),(1247,13,14,6,'2025-06-26','17:01:44',1),(1248,13,14,6,'2025-06-26','17:01:50',1),(1249,13,14,6,'2025-06-26','17:01:53',1),(1250,13,14,6,'2025-06-26','17:01:55',1),(1251,13,14,6,'2025-06-26','17:01:58',1),(1252,13,14,6,'2025-06-26','17:03:02',1),(1253,13,14,6,'2025-06-26','17:03:24',1),(1254,13,14,6,'2025-06-26','17:03:49',1),(1255,13,14,6,'2025-06-26','17:04:04',1),(1256,13,14,6,'2025-06-26','17:04:20',1),(1257,13,14,6,'2025-06-26','17:04:30',1),(1258,13,14,6,'2025-06-26','17:04:40',1),(1259,13,1,3,'2025-06-26','17:05:14',1),(1260,11,1,1,'2025-06-26','17:05:23',1),(1261,11,14,6,'2025-06-26','17:05:23',1),(1262,11,14,6,'2025-06-26','17:06:11',1),(1263,11,14,6,'2025-06-26','17:06:51',1),(1264,11,14,6,'2025-06-26','17:07:35',1),(1265,11,14,6,'2025-06-26','17:10:35',1),(1266,11,14,6,'2025-06-26','17:10:46',1),(1267,11,14,6,'2025-06-26','17:10:49',1),(1268,11,14,6,'2025-06-26','17:13:15',1),(1269,11,14,6,'2025-06-26','17:14:15',1),(1270,11,14,6,'2025-06-26','17:15:36',1),(1271,11,14,6,'2025-06-26','17:17:57',1),(1272,11,14,6,'2025-06-26','17:24:11',1),(1273,11,14,6,'2025-06-26','17:24:15',1),(1274,11,14,6,'2025-06-26','17:24:18',1),(1275,11,14,6,'2025-06-26','17:24:22',1),(1276,11,14,6,'2025-06-26','17:24:27',1),(1277,11,14,6,'2025-06-26','17:24:30',1),(1278,11,14,6,'2025-06-26','17:24:32',1),(1279,11,14,6,'2025-06-26','17:24:36',1),(1280,11,14,6,'2025-06-26','17:24:40',1),(1281,11,14,6,'2025-06-26','17:24:43',1),(1282,11,14,6,'2025-06-26','17:26:25',1),(1283,11,14,6,'2025-06-26','17:27:10',1),(1284,11,14,6,'2025-06-26','17:27:14',1),(1285,11,14,6,'2025-06-26','17:27:18',1),(1286,11,14,6,'2025-06-26','17:27:27',1),(1287,11,14,6,'2025-06-26','17:27:31',1),(1288,11,14,6,'2025-06-26','17:27:35',1),(1289,11,14,6,'2025-06-26','17:27:39',1),(1290,11,14,6,'2025-06-26','17:27:42',1),(1291,11,14,6,'2025-06-26','17:27:46',1),(1292,11,14,6,'2025-06-26','17:27:52',1),(1293,11,14,6,'2025-06-26','17:28:31',1),(1294,11,14,6,'2025-06-26','17:28:33',1),(1295,11,14,6,'2025-06-26','17:28:37',1),(1296,11,14,6,'2025-06-26','17:28:41',1),(1297,11,14,6,'2025-06-26','17:28:49',1),(1298,11,14,6,'2025-06-26','17:28:52',1),(1299,11,16,6,'2025-06-26','17:28:53',1),(1300,11,17,6,'2025-06-26','17:29:01',1),(1301,11,17,6,'2025-06-26','17:29:03',1),(1302,11,16,6,'2025-06-26','17:29:05',1),(1303,11,16,6,'2025-06-26','17:29:18',1),(1304,11,17,6,'2025-06-26','17:41:28',1),(1305,11,16,6,'2025-06-26','17:41:37',1),(1306,11,1,3,'2025-06-26','17:44:09',1),(1307,13,1,1,'2025-06-26','17:44:14',1),(1308,13,14,6,'2025-06-26','17:44:14',1),(1309,13,3,6,'2025-06-26','17:44:16',1),(1310,13,3,6,'2025-06-26','17:45:28',1),(1311,13,3,6,'2025-06-26','17:46:14',1),(1312,13,3,6,'2025-06-26','17:47:01',1),(1313,13,3,6,'2025-06-26','17:47:48',1),(1314,13,3,6,'2025-06-26','17:51:27',1),(1315,13,3,6,'2025-06-26','17:52:32',1),(1316,13,3,6,'2025-06-26','17:52:45',1),(1317,13,3,6,'2025-06-26','17:56:41',1),(1318,13,1,6,'2025-06-26','17:56:44',1),(1319,13,1,6,'2025-06-26','17:56:44',1),(1320,13,1,6,'2025-06-26','17:56:48',1),(1321,13,1,6,'2025-06-26','17:56:48',1),(1322,13,1,6,'2025-06-26','17:56:54',1),(1323,13,1,6,'2025-06-26','17:56:54',1),(1324,13,1,6,'2025-06-26','17:56:58',1),(1325,13,1,6,'2025-06-26','17:56:58',1),(1326,13,3,6,'2025-06-26','17:58:21',1),(1327,13,1,6,'2025-06-26','17:58:23',1),(1328,13,1,6,'2025-06-26','17:58:23',1),(1329,13,1,6,'2025-06-26','17:58:27',1),(1330,13,1,6,'2025-06-26','17:58:27',1),(1331,13,3,6,'2025-06-26','18:00:15',1),(1332,13,3,6,'2025-06-26','18:00:19',1),(1333,13,3,6,'2025-06-26','18:00:22',1),(1334,13,3,6,'2025-06-26','18:00:25',1),(1335,13,3,6,'2025-06-26','18:00:28',1),(1336,13,3,6,'2025-06-26','18:00:31',1),(1337,13,3,6,'2025-06-26','18:00:48',1),(1338,13,3,6,'2025-06-26','18:00:51',1),(1339,13,3,6,'2025-06-26','18:00:54',1),(1340,13,3,6,'2025-06-26','18:01:00',1),(1341,13,3,6,'2025-06-26','18:01:02',1),(1342,13,15,6,'2025-06-26','18:01:26',1),(1343,13,1,3,'2025-06-26','18:01:47',1),(1344,11,1,1,'2025-06-26','18:03:10',1),(1345,11,14,6,'2025-06-26','18:03:11',1),(1346,11,16,6,'2025-06-26','18:03:12',1),(1347,11,16,6,'2025-06-26','18:08:43',1),(1348,11,16,6,'2025-06-26','18:08:46',1),(1349,11,16,6,'2025-06-26','18:08:54',1),(1350,11,16,6,'2025-06-26','18:08:58',1),(1351,11,16,6,'2025-06-26','18:09:07',1),(1352,11,16,6,'2025-06-26','18:09:09',1),(1353,11,16,6,'2025-06-26','18:09:14',1),(1354,11,16,6,'2025-06-26','18:09:20',1),(1355,11,16,6,'2025-06-26','18:09:23',1),(1356,11,16,6,'2025-06-26','18:09:29',1),(1357,11,16,6,'2025-06-26','18:12:05',1),(1358,11,16,6,'2025-06-26','18:12:08',1),(1359,11,16,6,'2025-06-26','18:12:14',1),(1360,11,16,6,'2025-06-26','18:12:18',1),(1361,11,16,6,'2025-06-26','18:12:25',1),(1362,11,16,6,'2025-06-26','18:12:28',1),(1363,11,16,6,'2025-06-26','18:12:32',1),(1364,11,16,6,'2025-06-26','18:12:34',1),(1365,11,16,6,'2025-06-26','18:13:41',1),(1366,11,16,6,'2025-06-26','18:15:03',1),(1367,11,16,6,'2025-06-26','18:15:06',1),(1368,11,16,6,'2025-06-26','18:15:09',1),(1369,11,17,6,'2025-06-26','18:15:14',1),(1370,11,17,6,'2025-06-26','18:15:17',1),(1371,11,17,6,'2025-06-26','18:15:24',1),(1372,11,1,3,'2025-06-26','18:15:30',1),(1373,1,1,1,'2025-06-26','18:15:58',1),(1374,1,1,6,'2025-06-26','18:15:58',1),(1375,1,1,6,'2025-06-26','18:15:58',1),(1376,1,8,6,'2025-06-26','18:16:20',1),(1377,1,9,6,'2025-06-26','18:16:36',1),(1378,1,4,6,'2025-06-26','18:16:38',1),(1379,1,1,6,'2025-06-26','18:18:56',1),(1380,1,1,6,'2025-06-26','18:18:56',1),(1381,1,1,6,'2025-06-26','18:19:02',1),(1382,1,1,6,'2025-06-26','18:19:02',1),(1383,1,1,6,'2025-06-26','18:20:16',1),(1384,1,1,6,'2025-06-26','18:20:16',1),(1385,1,1,6,'2025-06-26','18:20:21',1),(1386,1,1,6,'2025-06-26','18:20:21',1),(1387,1,4,6,'2025-06-26','18:25:08',1),(1388,1,4,6,'2025-06-26','18:25:14',1),(1389,1,4,6,'2025-06-26','18:26:08',1),(1390,1,4,6,'2025-06-26','18:28:38',1),(1391,1,4,6,'2025-06-26','18:28:39',1),(1392,1,1,6,'2025-06-26','18:28:41',1),(1393,1,1,6,'2025-06-26','18:28:41',1),(1394,1,1,6,'2025-06-26','18:28:45',1),(1395,1,1,6,'2025-06-26','18:28:45',1),(1396,1,4,6,'2025-06-26','18:29:16',1),(1397,1,1,6,'2025-06-26','18:29:20',1),(1398,1,1,6,'2025-06-26','18:29:20',1),(1399,1,4,6,'2025-06-26','18:30:01',1),(1400,1,4,6,'2025-06-26','18:30:04',1),(1401,1,4,6,'2025-06-26','18:30:10',1),(1402,1,4,6,'2025-06-26','18:30:17',1),(1403,1,4,6,'2025-06-26','18:30:49',1),(1404,1,4,6,'2025-06-26','18:30:52',1),(1405,1,4,6,'2025-06-26','18:30:56',1),(1406,1,9,6,'2025-06-26','18:31:45',1),(1407,1,1,3,'2025-06-26','18:32:16',1),(1408,1,8,6,'2025-06-26','18:32:22',1),(1409,1,9,6,'2025-06-26','18:32:23',1),(1410,13,1,1,'2025-06-26','18:32:32',1),(1411,13,14,6,'2025-06-26','18:32:32',1),(1412,13,23,6,'2025-06-26','18:32:37',1),(1413,13,23,6,'2025-06-26','18:32:40',1),(1414,13,23,6,'2025-06-26','18:32:42',1),(1415,13,23,6,'2025-06-26','18:32:45',1),(1416,13,23,6,'2025-06-26','18:32:45',1),(1417,13,23,6,'2025-06-26','18:32:49',1),(1418,13,23,6,'2025-06-26','18:32:51',1),(1419,13,23,6,'2025-06-26','18:32:53',1),(1420,13,23,6,'2025-06-26','18:33:20',1),(1421,13,23,6,'2025-06-26','18:33:20',1),(1422,13,23,6,'2025-06-26','18:33:21',1),(1423,13,23,6,'2025-06-26','18:33:23',1),(1424,13,23,6,'2025-06-26','18:33:25',1),(1425,13,23,6,'2025-06-26','18:33:38',1),(1426,13,23,6,'2025-06-26','18:33:40',1),(1427,13,23,6,'2025-06-26','18:33:42',1),(1428,1,9,6,'2025-06-26','18:33:59',1),(1429,1,9,6,'2025-06-26','18:34:10',1),(1430,1,9,6,'2025-06-26','18:34:14',1),(1431,1,9,6,'2025-06-26','18:34:20',1),(1432,1,9,6,'2025-06-26','18:34:31',1),(1433,1,9,6,'2025-06-26','18:34:35',1),(1434,1,9,6,'2025-06-26','18:34:42',1),(1435,1,9,6,'2025-06-26','18:34:47',1),(1436,1,9,6,'2025-06-26','18:34:51',1),(1437,1,8,6,'2025-06-26','18:57:33',1),(1438,1,8,6,'2025-06-26','18:57:37',1),(1439,1,8,6,'2025-06-26','18:57:42',1),(1440,1,8,6,'2025-06-26','18:57:58',1),(1441,1,8,6,'2025-06-26','18:58:02',1),(1442,1,8,6,'2025-06-26','19:01:36',1),(1443,1,8,6,'2025-06-26','19:01:40',1),(1444,1,8,6,'2025-06-26','19:01:43',1),(1445,1,13,6,'2025-06-26','19:48:38',1),(1446,1,13,6,'2025-06-26','20:08:13',1),(1447,1,13,6,'2025-06-26','20:10:07',1),(1448,1,13,6,'2025-06-26','20:15:51',1),(1449,1,13,6,'2025-06-26','22:01:16',1),(1450,1,13,6,'2025-06-26','22:06:28',1),(1451,1,13,6,'2025-06-26','22:08:37',1),(1452,1,13,6,'2025-06-26','22:10:42',1),(1453,1,13,6,'2025-06-26','22:14:39',1),(1454,1,13,6,'2025-06-26','22:15:25',1),(1455,1,13,6,'2025-06-26','22:17:14',1),(1456,1,13,6,'2025-06-26','22:18:29',1),(1457,1,13,6,'2025-06-26','22:19:52',1),(1458,1,13,6,'2025-06-26','22:20:15',1),(1459,1,13,6,'2025-06-26','22:21:16',1),(1460,1,13,6,'2025-06-26','22:24:44',1),(1461,1,13,6,'2025-06-26','22:25:53',1),(1462,1,13,6,'2025-06-26','22:27:03',1),(1463,1,13,6,'2025-06-26','22:29:11',1),(1464,1,13,6,'2025-06-26','22:30:39',1),(1465,1,13,6,'2025-06-26','22:32:29',1),(1466,1,13,6,'2025-06-26','22:33:55',1),(1467,1,13,6,'2025-06-26','22:35:04',1),(1468,1,13,6,'2025-06-26','22:39:18',1),(1469,1,13,6,'2025-06-26','22:40:19',1),(1470,1,13,6,'2025-06-26','22:49:31',1),(1471,1,13,6,'2025-06-26','22:50:54',1),(1472,1,13,6,'2025-06-26','22:51:42',1),(1473,1,13,6,'2025-06-26','23:07:10',1),(1474,1,13,6,'2025-06-26','23:08:09',1),(1475,1,13,6,'2025-06-26','23:08:51',1),(1476,1,13,6,'2025-06-26','23:14:56',1),(1477,1,1,3,'2025-06-26','23:14:58',1),(1478,13,1,1,'2025-06-26','23:15:04',1),(1479,13,14,6,'2025-06-26','23:15:04',1),(1480,13,3,6,'2025-06-26','23:15:08',1),(1481,13,1,3,'2025-06-26','23:15:56',1),(1482,1,1,1,'2025-06-26','23:16:02',1),(1483,1,1,6,'2025-06-26','23:16:02',1),(1484,1,1,6,'2025-06-26','23:16:02',1),(1485,1,13,6,'2025-06-26','23:16:06',1),(1486,1,13,6,'2025-06-26','23:23:39',1),(1487,1,13,6,'2025-06-26','23:36:19',1),(1488,1,13,6,'2025-06-26','23:36:42',1),(1489,1,13,6,'2025-06-26','23:37:11',1),(1490,1,13,6,'2025-06-26','23:37:23',1),(1491,1,13,6,'2025-06-27','00:33:28',1),(1492,1,1,3,'2025-06-27','00:33:30',1),(1493,13,1,1,'2025-06-27','00:33:42',1),(1494,13,14,6,'2025-06-27','00:33:42',1),(1495,13,3,6,'2025-06-27','00:33:43',1),(1496,13,3,6,'2025-06-27','00:40:00',1),(1497,13,3,6,'2025-06-27','00:42:20',1),(1498,13,3,6,'2025-06-27','00:47:26',1),(1499,13,3,6,'2025-06-27','00:50:59',1),(1500,13,3,6,'2025-06-27','00:52:36',1),(1501,13,3,6,'2025-06-27','00:55:44',1),(1502,13,3,6,'2025-06-27','00:57:56',1),(1503,13,3,6,'2025-06-27','01:06:43',1),(1504,13,3,6,'2025-06-27','01:09:24',1),(1505,13,3,6,'2025-06-27','01:09:42',1),(1506,13,3,91,'2025-06-27','01:11:49',0),(1507,13,3,6,'2025-06-27','01:11:49',1),(1508,13,3,6,'2025-06-27','01:13:00',1),(1509,13,3,6,'2025-06-27','01:16:28',1),(1510,13,3,6,'2025-06-27','01:19:45',1),(1511,13,3,6,'2025-06-27','01:20:19',1),(1512,13,3,6,'2025-06-27','01:21:04',1),(1513,13,3,91,'2025-06-27','01:22:08',1),(1514,13,3,6,'2025-06-27','01:22:08',1),(1515,13,3,6,'2025-06-27','01:30:20',1),(1516,13,3,6,'2025-06-27','01:34:11',1),(1517,13,3,6,'2025-06-27','01:39:40',1),(1518,13,10,6,'2025-06-27','01:39:41',1),(1519,13,14,6,'2025-06-27','01:39:47',1),(1520,13,14,6,'2025-06-27','01:39:50',1),(1521,13,14,6,'2025-06-27','01:39:53',1),(1522,13,14,6,'2025-06-27','01:39:56',1),(1523,13,15,6,'2025-06-27','01:40:00',1),(1524,13,23,6,'2025-06-27','01:40:03',1),(1525,13,1,3,'2025-06-27','01:40:06',1),(1526,1,1,1,'2025-06-27','01:40:13',1),(1527,1,1,6,'2025-06-27','01:40:13',1),(1528,1,1,6,'2025-06-27','01:40:13',1),(1529,1,2,6,'2025-06-27','01:40:14',1),(1530,1,4,6,'2025-06-27','01:40:16',1),(1531,1,5,6,'2025-06-27','01:40:18',1),(1532,1,6,6,'2025-06-27','01:40:18',1),(1533,1,5,6,'2025-06-27','01:40:19',1),(1534,1,4,6,'2025-06-27','01:40:21',1),(1535,1,5,6,'2025-06-27','01:40:23',1),(1536,1,6,6,'2025-06-27','01:40:24',1),(1537,1,6,6,'2025-06-27','01:40:28',1),(1538,1,7,6,'2025-06-27','01:40:37',1),(1539,1,8,6,'2025-06-27','01:40:39',1),(1540,1,9,6,'2025-06-27','01:40:42',1),(1541,1,22,6,'2025-06-27','01:40:56',1),(1542,1,22,6,'2025-06-27','01:41:20',1),(1543,1,1,3,'2025-06-27','01:56:27',1),(1544,13,1,1,'2025-06-27','02:27:42',1),(1545,13,14,6,'2025-06-27','02:27:42',1),(1546,13,1,3,'2025-06-27','02:29:14',1),(1547,1,1,1,'2025-06-27','02:29:20',1),(1548,1,1,6,'2025-06-27','02:29:20',1),(1549,1,1,6,'2025-06-27','02:29:20',1),(1550,1,11,310,'2025-06-27','02:29:22',1),(1551,1,11,310,'2025-06-27','02:29:36',1),(1552,1,11,310,'2025-06-27','02:29:43',1),(1553,1,11,310,'2025-06-27','02:29:48',1),(1554,1,1,3,'2025-06-27','02:30:00',1),(1555,13,1,1,'2025-06-27','02:30:08',1),(1556,13,14,6,'2025-06-27','02:30:09',1),(1557,13,3,6,'2025-06-27','02:30:11',1),(1558,13,1,3,'2025-06-27','03:29:43',1),(1559,1,1,1,'2025-06-28','10:04:42',1),(1560,1,1,6,'2025-06-28','10:04:43',1),(1561,1,1,6,'2025-06-28','10:04:43',1),(1562,1,1,3,'2025-06-28','10:04:46',1),(1563,13,1,1,'2025-06-28','10:04:54',1),(1564,13,14,6,'2025-06-28','10:04:55',1),(1565,13,14,6,'2025-06-28','10:05:21',1),(1566,13,14,6,'2025-06-28','10:05:32',1),(1567,13,3,6,'2025-06-28','10:05:35',1),(1568,13,3,6,'2025-06-28','10:11:52',1),(1569,13,3,6,'2025-06-28','10:15:43',1),(1570,13,3,6,'2025-06-28','10:18:40',1),(1571,13,14,6,'2025-06-28','10:19:18',1),(1572,13,15,6,'2025-06-28','10:19:20',1),(1573,13,3,6,'2025-06-28','10:19:44',1),(1574,13,3,6,'2025-06-28','10:20:21',1),(1575,13,3,6,'2025-06-28','10:22:16',1),(1576,13,3,6,'2025-06-28','10:24:02',1),(1577,13,3,6,'2025-06-28','10:25:18',1),(1578,13,3,6,'2025-06-28','10:25:45',1),(1579,13,3,6,'2025-06-28','10:25:48',1),(1580,13,3,6,'2025-06-28','10:27:00',1),(1581,13,3,6,'2025-06-28','10:28:37',1),(1582,13,3,6,'2025-06-28','10:29:28',1),(1583,13,3,6,'2025-06-28','10:29:54',1),(1584,13,3,6,'2025-06-28','10:31:43',1),(1585,13,3,6,'2025-06-28','10:32:40',1),(1586,13,3,6,'2025-06-28','10:35:36',1),(1587,13,3,6,'2025-06-28','10:37:28',1),(1588,13,3,6,'2025-06-28','10:40:58',1),(1589,13,3,6,'2025-06-28','10:42:00',1),(1590,13,3,6,'2025-06-28','10:44:58',1),(1591,13,3,6,'2025-06-28','10:45:53',1),(1592,13,3,6,'2025-06-28','10:46:33',1),(1593,13,3,6,'2025-06-28','10:55:24',1),(1594,13,3,6,'2025-06-28','10:56:35',1),(1595,13,3,6,'2025-06-28','10:56:51',1),(1596,13,3,6,'2025-06-28','11:00:02',1),(1597,13,3,6,'2025-06-28','11:16:47',1),(1598,13,3,6,'2025-06-28','11:17:10',1),(1599,13,3,6,'2025-06-28','11:20:08',1),(1600,13,3,6,'2025-06-28','11:20:09',1),(1601,13,3,6,'2025-06-28','11:20:20',1),(1602,13,3,6,'2025-06-28','11:20:24',1),(1603,13,12,6,'2025-06-28','11:20:35',1),(1604,13,3,6,'2025-06-28','11:20:35',1),(1605,13,1,3,'2025-06-28','13:15:30',1),(1606,11,1,1,'2025-06-28','13:15:37',1),(1607,11,14,6,'2025-06-28','13:15:37',1),(1608,11,14,6,'2025-06-28','13:15:41',1),(1609,11,14,6,'2025-06-28','13:16:18',1),(1610,11,14,6,'2025-06-28','20:43:18',1),(1611,11,14,6,'2025-06-28','20:43:25',1),(1612,11,16,6,'2025-06-28','20:43:27',1),(1613,11,17,6,'2025-06-28','20:43:27',1),(1614,11,10,6,'2025-06-28','20:43:28',1),(1615,11,12,6,'2025-06-28','20:43:29',1),(1616,11,10,6,'2025-06-28','20:43:30',1),(1617,11,12,6,'2025-06-28','20:43:32',1),(1618,1,1,1,'2025-06-30','15:09:39',1),(1619,1,1,6,'2025-06-30','15:09:39',1),(1620,1,1,6,'2025-06-30','15:09:39',1),(1621,1,22,6,'2025-06-30','15:09:44',1);
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
-- Table structure for table `promotion`
--

DROP TABLE IF EXISTS `promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion` (
  `id_promotion` int NOT NULL AUTO_INCREMENT,
  `lib_promotion` varchar(50) NOT NULL,
  PRIMARY KEY (`id_promotion`),
  UNIQUE KEY `unique_promotion` (`lib_promotion`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion`
--

LOCK TABLES `promotion` WRITE;
/*!40000 ALTER TABLE `promotion` DISABLE KEYS */;
INSERT INTO `promotion` VALUES (1,'2003-2004'),(2,'2004-2005'),(3,'2005-2006'),(4,'2006-2007'),(5,'2007-2008'),(6,'2008-2009'),(7,'2009-2010'),(8,'2010-2011'),(9,'2011-2012'),(10,'2012-2013'),(11,'2013-2014'),(12,'2014-2015'),(13,'2015-2016'),(14,'2016-2017'),(15,'2017-2018'),(16,'2018-2019'),(17,'2019-2020'),(18,'2020-2021'),(19,'2021-2022'),(20,'2022-2023'),(21,'2023-2024'),(22,'2024-2025');
/*!40000 ALTER TABLE `promotion` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rapport_etudiant`
--

LOCK TABLES `rapport_etudiant` WRITE;
/*!40000 ALTER TABLE `rapport_etudiant` DISABLE KEYS */;
INSERT INTO `rapport_etudiant` VALUES (50,1,'GOMEZ_Ange Axel_2025-06-24','2025-06-24','Big','En attente d\'approbation','assets/uploads/rapports/GOMEZ_Ange Axel_2025-06-24.pdf'),(51,2,'KROUMA_Franck Adams_2025-06-25','2025-06-25','Informatisation des documents administratifs','En attente d\'approbation','assets/uploads/rapports/KROUMA_Franck Adams_2025-06-25.pdf');
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
INSERT INTO `rattacher` VALUES (1,18),(1,19),(1,21),(1,24),(1,25),(2,10),(2,12),(2,14),(2,16),(2,17),(3,3),(3,10),(3,12),(3,14),(3,15),(3,20),(3,23),(4,10),(4,12),(4,14),(5,10),(5,12),(6,10),(6,12),(7,10),(7,12),(8,1),(8,2),(8,4),(8,5),(8,6),(8,7),(8,8),(8,9),(8,10),(8,11),(8,12),(8,13),(8,20),(8,22),(9,1),(9,4),(9,5),(9,6),(9,7),(9,8),(9,9),(9,10),(9,12);
/*!40000 ALTER TABLE `rattacher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reclamations`
--

DROP TABLE IF EXISTS `reclamations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reclamations` (
  `id_reclamation` int NOT NULL AUTO_INCREMENT,
  `id_ac` int NOT NULL,
  `num_etd` int NOT NULL,
  `motif_reclamation` text NOT NULL,
  `matieres` text NOT NULL,
  `date_reclamation` date NOT NULL DEFAULT (curdate()),
  `piece_jointe` varchar(255) DEFAULT NULL,
  `retour_traitement` varchar(255) DEFAULT NULL,
  `date_traitement` date DEFAULT NULL,
  `statut_reclamation` enum('En attente','Traitée') NOT NULL DEFAULT 'En attente',
  PRIMARY KEY (`id_reclamation`),
  KEY `num_etd` (`num_etd`),
  KEY `fk_reclamations_ac` (`id_ac`),
  CONSTRAINT `fk_reclamations_ac` FOREIGN KEY (`id_ac`) REFERENCES `annee_academique` (`id_ac`) ON DELETE CASCADE,
  CONSTRAINT `fk_reclamations_etudiant` FOREIGN KEY (`num_etd`) REFERENCES `etudiants` (`num_etd`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reclamations`
--

LOCK TABLES `reclamations` WRITE;
/*!40000 ALTER TABLE `reclamations` DISABLE KEYS */;
INSERT INTO `reclamations` VALUES (1,2524,1,'notes manquante','Programmation linéaire','2025-06-17',NULL,'Bien','2025-06-26','Traitée'),(5,2524,1,'Note manquantes','[\"Alg\\u00e8bre\",\"Analyse\"]','2025-06-26',NULL,NULL,NULL,'En attente');
/*!40000 ALTER TABLE `reclamations` ENABLE KEYS */;
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
  `reste_a_payer` decimal(10,0) NOT NULL,
  `statut` enum('Non payé','Partiel','Soldé') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Non payé',
  `date_reglement` date NOT NULL DEFAULT (curdate()),
  `mode_de_paiement` enum('espece','cheque') DEFAULT 'espece',
  `numero_cheque` varchar(12) DEFAULT 'Néant',
  `motif_paiement` varchar(255) DEFAULT 'Néant',
  PRIMARY KEY (`id_reglement`),
  UNIQUE KEY `numero_reglement` (`numero_reglement`),
  KEY `num_etd` (`num_etd`),
  KEY `id_niv_etd` (`id_niv_etd`),
  KEY `id_ac` (`id_ac`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reglement`
--

LOCK TABLES `reglement` WRITE;
/*!40000 ALTER TABLE `reglement` DISABLE KEYS */;
INSERT INTO `reglement` VALUES (70,2,2024,5,'REG-20250001',975000.00,975000.00,0,'Partiel','2025-06-25','cheque','5478952','frais de scolarité');
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
INSERT INTO `rendre` VALUES (13,4,'2025-06-09');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reunions`
--

LOCK TABLES `reunions` WRITE;
/*!40000 ALTER TABLE `reunions` DISABLE KEYS */;
INSERT INTO `reunions` VALUES (1,'#REU001','normale','2025-06-27','00:26:00',1.5,'SALLE A','A examiner',2,'programmée','2025-06-25 00:23:25','2025-06-25 00:23:25'),(2,'#REU002','urgente','2025-06-28','01:19:00',1.5,'SALLE B','plusieurs rapport en urgence',2,'programmée','2025-06-25 01:15:55','2025-06-25 01:15:55'),(3,'#REU003','normale','2025-06-27','05:18:00',1.5,'SALLE A','Venez massivement',0,'programmée','2025-06-25 01:18:59','2025-06-25 01:18:59'),(7,'#REU005','normale','2025-06-28','01:30:00',1.5,'SALLE B','desc',3,'programmée','2025-06-25 01:27:25','2025-06-25 01:27:25'),(8,'#REU008','normale','2025-06-27','01:31:00',1.5,'SALLE A','desc',3,'programmée','2025-06-25 01:28:21','2025-06-25 01:28:21');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sauvegardes`
--

LOCK TABLES `sauvegardes` WRITE;
/*!40000 ALTER TABLE `sauvegardes` DISABLE KEYS */;
INSERT INTO `sauvegardes` VALUES (1,'bddTest','Base de test','C:\\wamp64\\www\\GSCV\\pages/sauvegardes/2025-06-03_00-13-16_bddTest.sql','2025-06-03 00:13:17',67040);
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
  `id_traitement` int NOT NULL AUTO_INCREMENT,
  `lib_traitement` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nom_traitement` varchar(130) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `classe_icone` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `titre_header` varchar(255) DEFAULT NULL,
  `sous_titre_header` varchar(255) DEFAULT NULL,
  `image_header` varchar(255) DEFAULT '../assets/images/logo_mi_sbg.png',
  PRIMARY KEY (`id_traitement`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traitement`
--

LOCK TABLES `traitement` WRITE;
/*!40000 ALTER TABLE `traitement` DISABLE KEYS */;
INSERT INTO `traitement` VALUES (1,'dashboard','Tableau de bord','fas fa-home','Tableau de bord','Aperçu global ','../assets/images/logo_mi_sbg.png'),(3,'evaluations_etudiants','Évaluations des étudiants','fas fa-pen-to-square','Évaluations des étudiants','Saisissez et consultez les évaluations','../assets/images/logo_mi_sbg.png'),(4,'analyses','Analyse des rapports étudiants','fa-solid fa-magnifying-glass-chart','Analyse des rapports','Analysez les rapports et mémoires des étudiants','../assets/images/logo_mi_sbg.png'),(5,'validations','Processus de validation','fas fa-check-circle','Validation des dossiers','Validez les différentes étapes administratives','../assets/images/logo_mi_sbg.png'),(6,'consultations','Consultation des documents étudiants','fa-solid fa-file-lines','Consultation des documents','Accédez aux documents des étudiants','../assets/images/logo_mi_sbg.png'),(7,'archivage_documents','Archivages des documents','fas fa-folder','Archivage des documents','Archivez ou consultez les documents étudiants','../assets/images/logo_mi_sbg.png'),(8,'archives','Mes archives','fas fa-archive','Mes archives','Consultez vos documents archivés','../assets/images/logo_mi_sbg.png'),(9,'reunions','Planification des réunions','fas fa-calendar-check','Réunions','Planifiez ou visualisez les réunions','../assets/images/logo_mi_sbg.png'),(10,'messages','Messagerie','fa-solid fa-inbox','Messagerie','Envoyez et recevez des messages internes','../assets/images/logo_mi_sbg.png'),(11,'piste_audit','Piste d\'audit','fas fa-book-open-reader','Piste d’audit','Suivi des actions utilisateurs sur la plateforme','../assets/images/logo_mi_sbg.png'),(12,'parameters','Paramètres','fas fa-cog','Paramètres','Réglez les options générales du système','../assets/images/logo_mi_sbg.png'),(13,'parametres_generaux','Paramètres généraux','fas fa-gears','Paramètres généraux','Configuration globale de la plateforme','../assets/images/logo_mi_sbg.png'),(14,'etudiants','Étudiants','fa-solid fa-users','Gestion des étudiants','Liste et informations des étudiants','../assets/images/logo_mi_sbg.png'),(15,'inscriptions_etudiants','Inscriptions étudiants','fas fa-id-card','Inscriptions','Gérez les inscriptions des étudiants','../assets/images/logo_mi_sbg.png'),(16,'suivis_des_decisions','Suivis des décisions','fas fa-list-check','Suivi des décisions','Visualisez l’évolution des décisions prises','../assets/images/logo_mi_sbg.png'),(17,'comptes_rendus','Consultations des comptes rendus','fas fa-file-alt','Comptes Rendus','Consultez les comptes rendus des commissions','../assets/images/logo_mi_sbg.png'),(18,'soutenances','Candidature à la soutenance','fa fa-graduation-cap','Candidature à la soutenance','Soumettez votre candidature en quelques clics','../assets/images/logo_mi_sbg.png'),(19,'rapports','Gestion du rapport/mémoire','fa fa-file-alt','Rapports et mémoires','Gérez les documents de soutenance','../assets/images/logo_mi_sbg.png'),(20,'reclamations_etudiants','Réclamations étudiants','fas fa-bullhorn','Réclamations des étudiants','Soumettez ou traitez les réclamations','../assets/images/logo_mi_sbg.png'),(21,'profils','Profils et informations','fa-solid fa-circle-info','Profil utilisateur','Consultez et modifiez vos informations','../assets/images/logo_mi_sbg.png'),(22,'sauvegardes_et_restaurations','Sauvegardes et restauration des données','fas fa-cloud-upload-alt','Sauvegardes','Sauvegardez ou restaurez vos données','../assets/images/logo_mi_sbg.png'),(2,'ressources_humaines','Gestion des ressources humaines','fa-solid fa-user-tie','Gestion des ressources humaines','Gérez le personnel enseignant et administratif','../assets/images/logo_mi_sbg.png'),(23,'demandes_soutenances','Traitement des demandes de soutenance','fas fa-file-alt','Demandes de soutenance','Traitez les demandes soumises par les étudiants','../assets/images/logo_mi_sbg.png'),(24,'boites_messages','Boites de messages','fa-solid fa-inbox','Boîte de messagerie','Accédez à vos messages et notifications','../assets/images/logo_mi_sbg.png'),(25,'reclamations','Gestion des réclamations','fas fa-bullhorn','Réclamations','Gérez toutes les réclamations reçues','../assets/images/logo_mi_sbg.png');
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
INSERT INTO `type_utilisateur` VALUES (1,'Étudiant'),(2,'Enseignant simple'),(3,'Enseignant administratif'),(4,'Personnel administratif');
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
  `id_ens` int DEFAULT NULL,
  PRIMARY KEY (`id_ue`),
  KEY `fk_niv_etd` (`id_niv_etd`),
  KEY `fk_id_semestre` (`id_semestre`),
  KEY `fk_id_ac` (`id_annee_academique`),
  KEY `fk_ens_ue` (`id_ens`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ue`
--

LOCK TABLES `ue` WRITE;
/*!40000 ALTER TABLE `ue` DISABLE KEYS */;
INSERT INTO `ue` VALUES (3501,'Analyse et conception à objet',6,60,3,5,2524,0),(2901,'Gestion financière',3,30,5,9,0,0),(2902,'Management de projet et intégration d\'application',6,60,5,9,0,0),(2903,'Audit informatique',3,30,5,9,0,0),(2904,'Entrepreunariat',2,20,5,9,0,0),(2905,'Multimedia mobile',3,30,5,9,0,0),(2906,'Ingenierie des exigences et veille technologique',3,30,5,9,0,0),(2907,'Mathématiques financières',3,30,5,9,0,0),(2908,'Anglais',2,20,5,9,0,0),(1101,'Initiation à l\'informatique',4,40,1,1,0,22),(1103,'Outils bureautiques 1',2,20,1,1,0,23),(1104,'Mathématiques 1',5,50,1,1,0,0),(1105,'Mathématiques 2',5,50,1,1,0,0),(1106,'Organisation des entreprises',3,30,1,1,0,0),(1107,'Économie',5,50,1,1,0,0),(1108,'Électronique numérique',3,30,1,1,0,0),(1201,'Mathématiques 3',6,60,1,2,0,0),(1202,'Probabilités et Statistique 1',4,40,1,2,0,0),(1203,'Outils bureautiques 2',2,20,1,2,0,0),(1204,'Algorithmique et Programmation',5,50,1,2,0,0),(1205,'Atelier de maintenance',1,10,1,2,0,0),(1206,'Technique d\'expression et méthodologie de travail',2,20,1,2,0,0),(1207,'Intelligence économique',2,20,1,2,0,0),(1208,'Gestion des Ressources Humaines',2,20,1,2,0,0),(1209,'Logiciel de traitement d\'images ou de montage vidéo',2,20,1,2,0,0),(1210,'Anglais',3,30,1,2,0,0),(2301,'Programmation orientée objet',6,60,2,3,0,0),(2302,'Outils formels pour l\'informatique',2,20,2,3,0,0),(2303,'Probabilités et Statistique 2',4,40,2,3,0,0),(2304,'Analyse de données',3,30,2,3,0,0),(2305,'Comptabilité générale',6,60,2,3,0,0),(2306,'Anglais',3,30,2,3,0,0),(2401,'Mathématiques 5',2,20,2,4,0,0),(2402,'Données semi-structurées et base de données',6,60,2,4,0,NULL),(2403,'Programmation web',3,30,2,4,0,22),(2404,'Génie logiciel',6,60,2,4,0,0),(2405,'Programmation sous windows',4,40,2,4,0,0),(2406,'Contrôle budgétaire',3,30,2,4,0,0),(2407,'Initiation Python',2,20,2,4,0,0),(2408,'Projet',2,20,2,4,0,0),(3502,'Systèmes informatiques',6,60,3,5,0,0),(3503,'Base de modélisation par objets (UML)',3,30,3,5,0,0),(3504,'Programmation',5,50,3,5,0,0),(3505,'Base de données avancées',3,30,3,5,0,0),(3506,'Programmation de client web',3,30,3,5,0,0),(3507,'Algorithmique des graphes',3,30,3,5,0,0),(3508,'Comptabilité de gestion',4,40,3,5,0,0),(3601,'Files d\'attente et gestion de stock',3,30,3,6,0,0),(3602,'Analyse de données',3,30,3,6,0,0),(3603,'Programmation d\'application',3,30,3,6,0,0),(3604,'Réseaux informatiques',5,50,3,6,0,0),(3605,'Théorie des langages',3,30,3,6,0,0),(3606,'Gestion financière',3,30,3,6,0,0),(3607,'Anglais',3,30,3,6,0,0),(3608,'Projet',30,4,3,6,0,0),(3609,'Environnement juridique',3,30,3,6,0,0),(1701,'Modélisation système d\'information',5,50,4,7,0,0),(1702,'Compléments de mathématiques',4,40,4,7,0,0),(1703,'Intelligence artificielle',2,20,4,7,0,0),(1704,'Base de données avancées',4,40,4,7,0,0),(1705,'Programmation avancée Java',4,40,4,7,0,2),(1706,'Progiciel de comptabilité (SAGE)',2,20,4,7,0,0),(1707,'Management des entreprises',3,30,4,7,0,0),(1708,'Concurrence et coopération dans les systèmes et les réseaux ',4,40,4,7,0,0),(1709,'Internet/Intranet',2,20,4,7,0,0),(1801,'Base de données décisionnelles',3,30,4,8,0,0),(1802,'Programmation impérative et developpement d\'IHM',4,40,4,8,0,0),(1803,'Systèmes d\'information repartis',5,50,4,8,0,0),(1804,'Contrôle de gestion',3,30,4,8,0,0),(1805,'Comptabilité analytique',4,40,4,8,0,0),(1806,'Marketing',3,30,4,8,0,0),(1807,'Projet de developpement logiciel',5,50,4,8,0,3),(1808,'Anglais',3,30,4,8,0,0),(2307,'Mathématiques 4',6,60,2,3,0,0),(1211,'Initiation au langage R',1,10,1,2,0,0),(2409,'Données semi-structurées ',2,20,2,4,0,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur`
--

LOCK TABLES `utilisateur` WRITE;
/*!40000 ALTER TABLE `utilisateur` DISABLE KEYS */;
INSERT INTO `utilisateur` VALUES (1,'kouaB@univ.edu','e92f96f743c7bfbe187d586f17191e03ebb0fdde59771ca4dfbf7ab6aa7ca26d','Actif',NULL),(2,'brouP@univ.edu','4854286bb514f68044481d57dba9a2e566b79b4b6317f68c2e97b55197242800','Actif',NULL),(3,'wahM@univ.edu','d1ca26082383037ae8cd65eb6f56fbdd1dcb6c8b48e47bc937d2e26047c0c5ab','Actif',NULL),(4,'diarraM@univ.edu','83a4c6a74878358f75790333520b3f5463fb82e7d2e8e078a7bccac7c028493a','Actif',NULL),(5,'codjiaA@univ.edu','8610662eb326ba1125029b5fc34d50d1f0f6cf737ffe7edbed7ce003f8e43bf8','Actif',NULL),(6,'idaB@univ.edu','acec2454b5dcc5d741b13d5e703f252306663f107ba6e8ec3ceba3fa2d48e6b4','Actif',NULL),(7,'kouakouM@univ.edu','44abaac58f10d40e2c71635ff22b2b9d0f022705194bc6f15ea1ffa3f5a95b87','Actif',NULL),(8,'kouakouF@univ.edu','95d0c9dbe9524157bb22eded258711ff6e2a27c835a8717b263d9809a9ba2ed4','Actif',NULL),(9,'baillyB@univ.edu','d63539ffceb1864f36ac2104f8b92b9cb878c52eefd2f7019bdee0e0e9e70f41','Actif',NULL),(10,'bakayokoI@univ.edu','3409e586c8fc3b086f9432a784058123b77b65b5579bcc3f7f217fd5756734e5','Actif',NULL),(11,'seriMC@univ.edu','df8f597da9c13895791d3fdb81cb9112877b12ec7b26c9752126bc34f182c38f','Actif',NULL),(12,'yah@univ.edu','1df83c4230a9a05ef403b6e82866282b3236e4409ac95892a6e9f9a2f4bf09b1','Actif',NULL),(13,'durandK@univ.edu','4ec476c2d7b6bd0f316afbb2c0cdae3f8a816da2b15e7a2477d1155b0d4dde05','Actif',NULL),(14,'axelangegomez@gmail.com','37e8d160c2c1247cbb7cbb879080807a21743a4e0a68721ea6c7e624bf95b4ea','Actif',NULL),(15,'francky@etu.univ.edu','adb029bcca098ad49fb407588213f334f63ba6447fcb3890b6ff4b865c9904ee','Actif',NULL),(16,'yvesA@etu.univ.edu','f65ff48872eaae1ebbe688036fe211cfac21bee110f01612f6dee77f19514717','Actif',NULL),(17,'yaoAy@etu.univ.edu','343c8c1d3b9f5870aa26454463e3aa7c45c946632610c2ad665edb2bcc6755ac','Actif',NULL),(19,'yoliM@gmail.com','dc0be3be36050495fa1254b9e1ae017dec32db257d0ea1f9db3133d588bf57e2','Actif',NULL),(20,'saraE@univ.ci',NULL,'Actif',NULL),(21,'kouaR@gmail.com',NULL,'Inactif',NULL),(22,'konateN@univ.edu','ad09f5013f1b23b87864c833317138c80140c9913ec890d1af4d59c70b3a4d34','Actif',NULL),(23,'nindjinM@edu.ci','4f1d4cd882ce932af21632cd22b9923fd9aa31306ba3e00fd1cc018de6c895c0','Actif',NULL),(24,'toureM@univ.edu','2beb56a1e8d555ade0b6c72c589bbea3c650bd5c56af3a7fb16d9a2893b11113','Actif',NULL),(33,'kouakAst@gmail.com','$2y$10$v3fhpiygdZlXwak2jiCVauhLO7Fw6N5Ljm8V606BGwK2qnEb2C/pC','Inactif',NULL),(35,'noemietra27@gmail.com','50b1c44e6c63c6053d8a88b11f68855682e6807c3401b7e7afd71c3d2cb5cba3','Actif',NULL);
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
INSERT INTO `utilisateur_type_utilisateur` VALUES (1,3,'2025-05-20'),(2,3,'2025-05-19'),(3,3,'2025-05-19'),(4,3,'2025-05-04'),(5,3,'2025-05-04'),(6,3,'2025-05-04'),(7,3,'2025-05-04'),(8,2,'2025-05-04'),(9,2,'2025-05-04'),(10,2,'2025-05-04'),(11,4,'2025-05-04'),(12,4,'2025-05-04'),(13,4,'2025-05-04'),(14,1,'2025-05-21'),(15,1,'2025-05-04'),(16,1,'2025-05-04'),(17,1,'2025-05-04'),(18,1,'2025-05-04'),(19,2,'2025-06-01'),(20,4,'2025-06-01'),(21,4,'2025-06-01'),(22,2,'2025-06-02'),(23,2,'2025-06-02'),(24,2,'2025-06-01'),(35,1,'2025-06-22');
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
INSERT INTO `valider` VALUES (2,50,'2025-06-09','Rien à dire','Validé'),(3,50,'2025-06-09','Bon rapport !','Validé'),(4,50,'2025-06-09','Pas d\'accord','Rejeté'),(1,50,'2025-06-09','Super','Validé');
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

-- Dump completed on 2025-06-30 15:09:55
