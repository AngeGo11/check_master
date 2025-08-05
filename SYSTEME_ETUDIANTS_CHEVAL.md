# 🐎 Système de Gestion des Étudiants à Cheval

## 📋 Vue d'ensemble

Le système de gestion des étudiants à cheval permet de gérer efficacement les étudiants qui ont des matières à rattraper dans les niveaux antérieurs. Un étudiant à cheval est un étudiant qui, étant dans la classe supérieure, a des matières à rattraper dans la classe antérieure.

### 🎯 Fonctionnalités principales

- **Gestion des statuts** : Différencier les étudiants normaux des étudiants à cheval
- **Suivi des matières à rattraper** : Tracker quelles matières l'étudiant doit rattraper
- **Calcul automatique des frais** : Calculer les frais en fonction du nombre de matières à rattraper
- **Historique des inscriptions** : Garder une trace des inscriptions spécifiques
- **Flexibilité tarifaire** : Appliquer des tarifs différents selon le statut

## 🗄️ Structure de la base de données

### Tables principales

#### 1. `inscription_etudiant_cheval`
```sql
CREATE TABLE inscription_etudiant_cheval (
  id_inscription_cheval INT NOT NULL AUTO_INCREMENT,
  num_etd INT NOT NULL,
  id_ac INT NOT NULL,
  id_statut INT NOT NULL DEFAULT 2,
  promotion_principale INT NOT NULL,
  nombre_matieres_rattrapage INT NOT NULL DEFAULT 0,
  montant_inscription DECIMAL(10,2) NOT NULL,
  date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
  statut_paiement ENUM('En attente','Partiel','Complet') DEFAULT 'En attente',
  commentaire TEXT,
  PRIMARY KEY (id_inscription_cheval)
);
```

#### 2. `matieres_rattrapage`
```sql
CREATE TABLE matieres_rattrapage (
  id_rattrapage INT NOT NULL AUTO_INCREMENT,
  num_etd INT NOT NULL,
  id_ecue INT NOT NULL,
  id_ac INT NOT NULL,
  promotion_origine INT NOT NULL,
  promotion_actuelle INT NOT NULL,
  statut ENUM('En cours','Validée','Échouée') DEFAULT 'En cours',
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_validation DATETIME DEFAULT NULL,
  PRIMARY KEY (id_rattrapage)
);
```

#### 3. `frais_etudiant_cheval`
```sql
CREATE TABLE frais_etudiant_cheval (
  id_frais_cheval INT NOT NULL AUTO_INCREMENT,
  id_niv_etd INT NOT NULL,
  id_ac INT NOT NULL,
  nombre_matieres_rattrapage INT NOT NULL DEFAULT 1,
  montant_base DECIMAL(10,2) NOT NULL,
  montant_supplementaire DECIMAL(10,2) NOT NULL,
  montant_total DECIMAL(10,2) NOT NULL,
  description TEXT,
  PRIMARY KEY (id_frais_cheval)
);
```

## 🚀 Utilisation du système

### 1. Accès au système

Pour accéder au système de gestion des étudiants à cheval, utilisez l'URL :
```
http://votre-site.com/?page=etudiant_cheval
```

### 2. Inscription d'un étudiant à cheval

#### Étape 1 : Accéder au formulaire d'inscription
```
http://votre-site.com/?page=etudiant_cheval_inscription
```

#### Étape 2 : Remplir le formulaire
- Sélectionner l'étudiant
- Choisir l'année académique
- Définir la promotion principale
- Spécifier le nombre de matières à rattraper
- Calculer automatiquement les frais

#### Étape 3 : Validation
Le système vérifie automatiquement :
- Si l'étudiant n'est pas déjà inscrit à cheval pour cette année
- La cohérence des données
- Le calcul des frais

### 3. Gestion des matières à rattraper

#### Ajouter une matière
1. Accéder aux détails de l'étudiant
2. Cliquer sur "Ajouter une matière"
3. Sélectionner l'ECUE à rattraper
4. Définir la promotion d'origine et actuelle

#### Suivre le statut
- **En cours** : Matière en cours de rattrapage
- **Validée** : Matière validée avec succès
- **Échouée** : Matière échouée

### 4. Calcul automatique des frais

Le système calcule automatiquement les frais selon la formule :
```
Frais total = Montant de base + (Montant supplémentaire × Nombre de matières)
```

#### Configuration des frais
Les frais sont configurés par niveau d'étude et année académique dans la table `frais_etudiant_cheval`.

## 📊 Fonctionnalités avancées

### 1. Statistiques en temps réel
- Nombre total d'étudiants à cheval
- Répartition des paiements (Complet, Partiel, En attente)
- Moyenne des matières à rattraper par étudiant
- Montant total des inscriptions

### 2. Filtres et recherche
- Recherche par nom, prénom, email
- Filtrage par niveau d'étude
- Filtrage par statut de paiement
- Filtrage par année académique

### 3. Export des données
Export CSV des données des étudiants à cheval avec :
- Informations personnelles
- Niveau et promotion
- Nombre de matières
- Montant d'inscription
- Statut de paiement

### 4. Historique des inscriptions
Suivi complet de l'historique des inscriptions à cheval d'un étudiant sur plusieurs années.

## 🔧 Configuration

### 1. Configuration des frais

Pour configurer les frais pour un niveau et une année :

```sql
INSERT INTO frais_etudiant_cheval 
(id_niv_etd, id_ac, nombre_matieres_rattrapage, montant_base, montant_supplementaire, montant_total, description)
VALUES 
(3, 2524, 1, 860000.00, 50000.00, 910000.00, 'Frais L3 avec 1 matière à rattraper');
```

### 2. Exemple de configuration complète

```sql
-- Licence 1
INSERT INTO frais_etudiant_cheval VALUES (1, 2524, 1, 780000.00, 40000.00, 820000.00, 'Frais L1');

-- Licence 2  
INSERT INTO frais_etudiant_cheval VALUES (2, 2524, 1, 800000.00, 45000.00, 845000.00, 'Frais L2');

-- Licence 3
INSERT INTO frais_etudiant_cheval VALUES (3, 2524, 1, 860000.00, 50000.00, 910000.00, 'Frais L3');

-- Master 1
INSERT INTO frais_etudiant_cheval VALUES (4, 2524, 1, 925000.00, 55000.00, 980000.00, 'Frais M1');

-- Master 2
INSERT INTO frais_etudiant_cheval VALUES (5, 2524, 1, 975000.00, 60000.00, 1035000.00, 'Frais M2');
```

## 📱 Interface utilisateur

### 1. Page principale
- **Dashboard** avec statistiques
- **Liste des étudiants** avec filtres
- **Actions rapides** (voir détails, ajouter matière, calculer frais)

### 2. Page de détail
- **Informations de l'étudiant**
- **Liste des matières à rattraper**
- **Historique des inscriptions**
- **Actions sur les matières** (valider, échouer, supprimer)

### 3. Formulaire d'inscription
- **Sélection de l'étudiant**
- **Configuration de l'inscription**
- **Calcul automatique des frais**

## 🔒 Sécurité et validation

### 1. Validation des données
- Vérification de l'existence de l'étudiant
- Contrôle de la cohérence des promotions
- Validation des montants

### 2. Contrôles d'accès
- Vérification des droits utilisateur
- Logs des actions effectuées
- Traçabilité des modifications

## 📈 Exemples d'utilisation

### Scénario 1 : Étudiant L3 avec matières L1 et L2

1. **Inscription** : Étudiant en L3 inscrit à cheval
2. **Ajout des matières** : 
   - Mathématiques (L1)
   - Physique (L2)
3. **Calcul des frais** : 860,000 + (50,000 × 2) = 960,000 FCFA
4. **Suivi** : Validation progressive des matières

### Scénario 2 : Étudiant M2 avec matières M1

1. **Inscription** : Étudiant en M2 inscrit à cheval
2. **Ajout des matières** :
   - Méthodologie de recherche (M1)
3. **Calcul des frais** : 975,000 + (60,000 × 1) = 1,035,000 FCFA
4. **Validation** : Une fois la matière validée, l'étudiant peut passer au niveau supérieur

## 🛠️ Maintenance

### 1. Sauvegarde des données
```bash
# Sauvegarde de la base de données
mysqldump -u username -p database_name > backup_etudiants_cheval.sql
```

### 2. Nettoyage des données
```sql
-- Supprimer les inscriptions obsolètes (plus de 5 ans)
DELETE FROM inscription_etudiant_cheval 
WHERE date_inscription < DATE_SUB(NOW(), INTERVAL 5 YEAR);

-- Archiver les matières validées anciennes
UPDATE matieres_rattrapage 
SET statut = 'archivé' 
WHERE statut = 'Validée' 
AND date_validation < DATE_SUB(NOW(), INTERVAL 3 YEAR);
```

## 📞 Support

Pour toute question ou problème avec le système de gestion des étudiants à cheval :

1. **Documentation technique** : Consultez les commentaires dans le code
2. **Logs d'erreur** : Vérifiez les fichiers de log PHP
3. **Base de données** : Vérifiez l'intégrité des données

## 🔄 Évolutions futures

### Fonctionnalités prévues
- [ ] Interface mobile responsive
- [ ] Notifications automatiques
- [ ] Intégration avec le système de paiement
- [ ] Rapports avancés
- [ ] API REST pour intégration externe

### Améliorations techniques
- [ ] Cache Redis pour les performances
- [ ] Validation côté client
- [ ] Export PDF des rapports
- [ ] Système de workflow pour les validations

---

**Version** : 1.0  
**Date** : Août 2025  
**Auteur** : Équipe GSCV+ 