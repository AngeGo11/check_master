# Système d'Envoi d'Email des Comptes Rendus - GSCV+

## Vue d'ensemble

Le système d'envoi d'email permet d'envoyer automatiquement les comptes rendus par email avec le fichier en pièce jointe. Cette fonctionnalité est intégrée dans l'interface de consultation des comptes rendus.

## Fonctionnalités

### 1. Envoi d'Email depuis la Liste des Comptes Rendus
- **Bouton d'envoi** : Icône d'enveloppe violette à côté de chaque compte rendu
- **Modal de saisie** : Formulaire pour saisir l'email du destinataire, l'objet et un message optionnel
- **Validation** : Vérification de l'email et des champs requis

### 2. Envoi d'Email depuis les Détails
- **Bouton d'envoi** : Disponible dans la vue détaillée de chaque compte rendu
- **Même interface** : Modal identique à celle de la liste

### 3. Historique des Emails
- **Bouton d'historique** : Icône d'historique dans l'en-tête de la section comptes rendus
- **Tableau des envois** : Affichage de tous les emails envoyés avec dates, destinataires, etc.
- **Limitation** : Affichage des 100 derniers envois

## Configuration Technique

### Configuration SMTP
Le système utilise PHPMailer avec la configuration suivante :
```php
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'axelangegomez2004@gmail.com';
$mail->Password = 'yxxhpqgfxiulawhd';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
```

### Structure de l'Email
- **En-tête** : Logo UFHB avec gradient bleu
- **Corps** : Informations détaillées sur le compte rendu
- **Pièce jointe** : Fichier du compte rendu
- **Signature** : Signature institutionnelle

### Base de Données
Une table `email_logs` est créée automatiquement pour tracer les envois :
```sql
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cr_id INT NOT NULL,
    email_destinataire VARCHAR(255) NOT NULL,
    sujet VARCHAR(255),
    user_id INT,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cr_id (cr_id),
    INDEX idx_date_envoi (date_envoi)
);
```

## Utilisation

### 1. Envoyer un Compte Rendu par Email

1. **Accéder à la page** : Aller dans "Consultations" > "Consultation des Comptes Rendus"
2. **Cliquer sur l'icône d'enveloppe** : À côté du compte rendu souhaité
3. **Remplir le formulaire** :
   - Email du destinataire (obligatoire)
   - Objet de l'email (pré-rempli)
   - Message additionnel (optionnel)
4. **Envoyer** : Cliquer sur "Envoyer"

### 2. Consulter l'Historique

1. **Cliquer sur "Historique des emails"** : Bouton violet dans l'en-tête
2. **Visualiser le tableau** : Tous les envois avec dates et détails
3. **Fermer la modal** : Cliquer sur la croix

## Sécurité

### Vérifications
- **Authentification** : Seuls les utilisateurs connectés peuvent envoyer des emails
- **Permissions** : Restriction aux groupes autorisés (5, 6, 7, 8, 9)
- **Validation** : Vérification de l'existence du fichier avant envoi
- **Sanitisation** : Protection contre les injections XSS dans les messages

### Logs
- **Enregistrement automatique** : Chaque envoi est tracé dans la base de données
- **Gestion d'erreurs** : Logs détaillés en cas de problème
- **Non-bloquant** : Les erreurs de log n'empêchent pas l'envoi

## Fichiers Modifiés

### Interface Utilisateur
- `app/Views/consultations.php` : Ajout des boutons et modals

### Logique Métier
- `public/ajax_consultations.php` : Gestion des actions AJAX
- `app/Models/ConsultationModel.php` : Méthodes de base de données

### Configuration
- Utilise la configuration PHPMailer existante dans `config/mail.php`

## Dépannage

### Problèmes Courants

1. **Email non envoyé**
   - Vérifier la configuration SMTP
   - Contrôler les logs d'erreur PHP
   - S'assurer que le fichier existe

2. **Erreur de permissions**
   - Vérifier les droits d'accès à la base de données
   - Contrôler les permissions de création de table

3. **Fichier introuvable**
   - Vérifier le chemin du fichier dans la base de données
   - S'assurer que le fichier existe sur le serveur

### Logs de Débogage
Les erreurs sont enregistrées dans :
- Logs PHP : `storage/logs/php-error.log`
- Logs d'application : Messages d'erreur dans la console

## Évolutions Futures

### Améliorations Possibles
- **Templates d'email** : Système de modèles personnalisables
- **Envoi en lot** : Envoi multiple à plusieurs destinataires
- **Notifications** : Alertes en temps réel du statut d'envoi
- **Statistiques** : Tableaux de bord des envois
- **Archivage** : Sauvegarde automatique des emails envoyés

### Optimisations
- **File d'attente** : Système d'envoi asynchrone
- **Cache** : Mise en cache des templates d'email
- **Compression** : Compression des pièces jointes volumineuses 