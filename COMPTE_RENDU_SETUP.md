# Système de Comptes Rendus - GSCV+

## Description

Ce système permet aux responsables de créer et gérer des comptes rendus pour les rapports de stage validés ou rejetés. Il inclut un éditeur riche en temps réel avec sauvegarde automatique et export PDF.

## Fonctionnalités

### 1. Page de Consultation (`consultations.php`)
- ✅ **Système de comptage de votes** : Affiche le nombre d'évaluations terminées par rapport au total
- ✅ **Affichage des évaluations** : Section dépliable pour voir les commentaires de chaque membre de la commission
- ✅ **Design moderne** : Interface inspirée de l'image fournie avec cartes de progression
- ✅ **Bouton de redirection** : Vers la page de rédaction de compte rendu

### 2. Page de Rédaction (`redaction_compte_rendu.php`)
- ✅ **Sélection multiple** : Possibilité de sélectionner plusieurs rapports validés/rejetés
- ✅ **Éditeur riche** : Barre d'outils complète avec formatage
- ✅ **Sauvegarde automatique** : Toutes les 30 secondes et avant fermeture
- ✅ **Export PDF** : Fonctionnalité d'impression en PDF
- ✅ **Template automatique** : Génération de modèle avec les informations des rapports
- ✅ **Liste des comptes rendus** : Affichage des comptes rendus existants

## Installation

### 1. Base de données

La table `compte_rendu` existe déjà dans votre base de données avec la structure suivante :

```sql
CREATE TABLE `compte_rendu` (
  `id_cr` int NOT NULL AUTO_INCREMENT,
  `id_rapport_etd` int NOT NULL,
  `nom_cr` varchar(100) DEFAULT NULL,
  `date_cr` date DEFAULT NULL,
  `fichier_cr` varchar(255) NOT NULL,
  PRIMARY KEY (`id_cr`),
  KEY `fk_cr_rapport_etd` (`id_rapport_etd`)
);
```

Si vous voulez ajouter des index pour améliorer les performances, exécutez :
```sql
-- Exécuter le contenu du fichier storage/data/update_compte_rendu_table.sql
```

### 2. Structure des fichiers

```
app/
├── Controllers/
│   └── CompteRenduController.php    # Contrôleur principal
├── Models/
│   └── CompteRendu.php              # Modèle de données
└── Views/
    ├── consultations.php            # Page de consultation
    └── redaction_compte_rendu.php   # Page de rédaction

storage/
├── data/
│   └── update_compte_rendu_table.sql # Script de mise à jour
└── uploads/
    └── compte_rendu/                # Dossier pour les fichiers HTML
```

### 3. Permissions

Assurez-vous que le dossier `storage/uploads/compte_rendu/` est accessible en écriture par le serveur web.

## Utilisation

### 1. Accès à la page de consultation

Naviguez vers `?page=consultations` pour voir :
- Les rapports avec leur statut d'évaluation
- Le comptage des votes de la commission
- Les détails des évaluations de chaque membre

### 2. Création d'un compte rendu

1. Cliquez sur "Rédiger un compte rendu" dans la page de consultation
2. Sélectionnez un ou plusieurs rapports validés/rejetés
3. Rédigez votre compte rendu avec l'éditeur riche
4. Sauvegardez ou exportez en PDF

### 3. Gestion des comptes rendus

- **Voir** : Cliquez sur l'icône œil pour consulter
- **Télécharger** : Cliquez sur l'icône téléchargement
- **Modifier** : Accédez à la page de rédaction

## API du Contrôleur

### CompteRenduController

```php
// Récupérer tous les comptes rendus
$controller->index()

// Récupérer avec informations de l'étudiant
$controller->indexWithAuthor()

// Récupérer un compte rendu par ID
$controller->show($id)

// Créer un nouveau compte rendu
$controller->createCompteRendu($titre, $contenu, $date, $auteur_id, $fichier_path, $rapport_ids)

// Récupérer les rapports validés/rejetés
$controller->getRapportsValidesOuRejetes()

// Vérifier les permissions (utilise la table enseignants)
$controller->getResponsableCompteRendu($user_id)

// Récupérer les informations d'un enseignant
$controller->getEnseignantInfo($enseignant_id)
```

## Modèle de données

### Table `compte_rendu` (structure existante)

| Champ | Type | Description |
|-------|------|-------------|
| `id_cr` | INT | Clé primaire auto-incrémentée |
| `id_rapport_etd` | INT | ID du rapport étudiant associé |
| `nom_cr` | VARCHAR(100) | Nom/titre du compte rendu |
| `date_cr` | DATE | Date de création du compte rendu |
| `fichier_cr` | VARCHAR(255) | Chemin vers le fichier du compte rendu |

### Tables liées utilisées

- **`rapport_etudiant`** : Rapports des étudiants
- **`etudiants`** : Informations des étudiants
- **`enseignants`** : Informations des enseignants
- **`deposer`** : Table de liaison pour les dépôts
- **`valider`** : Table de liaison pour les validations
- **`approuver`** : Table de liaison pour les approbations

## Fonctionnalités JavaScript

### Éditeur de texte
- **Formatage** : Gras, italique, souligné
- **Titres** : H1, H2, H3
- **Listes** : Ordonnées et non-ordonnées
- **Alignement** : Gauche, centre, droite
- **Police** : Sélection de la police et taille

### Sauvegarde automatique
- Sauvegarde toutes les 30 secondes
- Sauvegarde avant fermeture de la page
- Récupération automatique des brouillons

### Export PDF
- Impression directe vers PDF
- Mise en page optimisée pour l'impression
- Sauts de page automatiques

## Personnalisation

### Couleurs de l'application

Les couleurs principales sont définies dans Tailwind CSS :

```css
primary: '#1a5276'        // Bleu principal
primary-light: '#2c7aa7'  // Bleu clair
accent: '#4caf50'         // Vert
success: '#4caf50'        // Vert de succès
warning: '#f39c12'        // Orange d'avertissement
danger: '#e74c3c'         // Rouge d'erreur
```

### Modifier le template de compte rendu

Le template est généré dans la fonction `getCompteRenduTemplate()` du fichier `redaction_compte_rendu.php`.

## Limitations actuelles

### Structure de la base de données
- **Un compte rendu = Un rapport** : La structure actuelle ne permet qu'un rapport par compte rendu
- **Pas de table users** : Le système utilise la table `enseignants` pour les permissions

### Solutions possibles pour plusieurs rapports par compte rendu

Si vous voulez permettre plusieurs rapports par compte rendu, vous pouvez :

1. **Créer une table de liaison** :
```sql
CREATE TABLE `compte_rendu_rapports` (
  `id_cr` int NOT NULL,
  `id_rapport_etd` int NOT NULL,
  PRIMARY KEY (`id_cr`, `id_rapport_etd`)
);
```

2. **Ajouter un champ JSON** :
```sql
ALTER TABLE `compte_rendu` 
ADD COLUMN `rapport_ids` JSON DEFAULT NULL;
```

3. **Utiliser un champ texte avec séparateurs** :
```sql
ALTER TABLE `compte_rendu` 
ADD COLUMN `rapport_ids` TEXT DEFAULT NULL;
```

## Dépannage

### Problèmes courants

1. **Erreur de permissions** : Vérifiez les droits d'écriture sur `storage/uploads/compte_rendu/`
2. **Table manquante** : La table `compte_rendu` existe déjà dans votre base
3. **Erreur de connexion** : Vérifiez la configuration de la base de données
4. **Erreur de clé étrangère** : Vérifiez que les rapports existent dans `rapport_etudiant`

### Logs

Les erreurs sont enregistrées dans :
- `storage/logs/php-error.log` pour les erreurs PHP
- Logs de la base de données pour les erreurs SQL

## Support

Pour toute question ou problème, consultez :
1. Les logs d'erreur
2. La documentation de l'API
3. Les commentaires dans le code source