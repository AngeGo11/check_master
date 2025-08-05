# Système de Mise à Jour Automatique des Statuts des Réunions

## Vue d'ensemble

Ce système permet de mettre à jour automatiquement les statuts des réunions selon l'heure actuelle :

- **Programmée** : Avant l'heure de début de la réunion
- **En cours** : Pendant la durée de la réunion
- **Terminée** : Après la fin de la réunion

## Fonctionnalités

### 1. Mise à jour manuelle
- Bouton "Mettre à jour statuts" dans l'interface des réunions
- Mise à jour immédiate de tous les statuts
- Notification visuelle du résultat

### 2. Mise à jour automatique en temps réel
- Vérification automatique toutes les minutes
- Mise à jour silencieuse en arrière-plan
- Rechargement automatique de la page si des changements sont détectés

### 3. Script cron (optionnel)
- Script autonome pour mise à jour via cron
- Logs détaillés des changements
- Exécution sans authentification

## Configuration

### Configuration automatique (recommandée)
Le système fonctionne automatiquement dès le chargement de la page des réunions.

### Configuration cron (optionnelle)
Pour une mise à jour plus robuste, configurez le script cron :

1. **Ajouter la tâche cron** :
```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne pour exécuter toutes les minutes
* * * * * php /chemin/vers/GSCV+/public/assets/traitements/cron_update_meeting_status.php
```

2. **Vérifier les permissions** :
```bash
# Rendre le script exécutable
chmod +x /chemin/vers/GSCV+/public/assets/traitements/cron_update_meeting_status.php
```

3. **Créer le dossier de logs** :
```bash
mkdir -p /chemin/vers/GSCV+/storage/logs
chmod 755 /chemin/vers/GSCV+/storage/logs
```

## Structure de la base de données

Le système utilise la table `reunions` avec les colonnes suivantes :
- `id` : Identifiant unique
- `titre` : Titre de la réunion
- `date_reunion` : Date de la réunion
- `heure_debut` : Heure de début
- `duree` : Durée en heures (ex: 1.5 pour 1h30)
- `status` : Statut actuel (programmée, en cours, terminée, annulée)
- `updated_at` : Timestamp de dernière mise à jour

## Logs

### Logs automatiques
- Fichier : `storage/logs/meeting_status_updates.log`
- Format : `YYYY-MM-DD HH:MM:SS - Message`
- Contenu : Détails des changements de statut

### Exemple de log
```
2025-01-15 14:30:00 - Réunion #REU001 (ID: 1) : statut changé de 'programmée' vers 'en cours'
2025-01-15 14:30:00 - Mise à jour terminée. 1 réunion(s) mise(s) à jour.
```

## Fichiers créés/modifiés

### Nouveaux fichiers
- `public/assets/traitements/update_meeting_status_auto.php` : API de mise à jour
- `public/assets/traitements/cron_update_meeting_status.php` : Script cron
- `storage/logs/meeting_status_updates.log` : Fichier de logs

### Fichiers modifiés
- `app/Views/reunions.php` : Interface avec bouton de mise à jour et vérification automatique

## Utilisation

### Interface utilisateur
1. Aller sur la page des réunions
2. Cliquer sur "Mettre à jour statuts" pour une mise à jour manuelle
3. Les statuts se mettent à jour automatiquement toutes les minutes

### API
```javascript
// Mise à jour manuelle via JavaScript
fetch('./assets/traitements/update_meeting_status_auto.php', {
    method: 'POST'
})
.then(response => response.json())
.then(data => console.log(data));
```

## Dépannage

### Problèmes courants

1. **Les statuts ne se mettent pas à jour**
   - Vérifier les permissions du dossier `storage/logs`
   - Vérifier la connexion à la base de données
   - Consulter les logs d'erreur PHP

2. **Erreur de permissions**
   ```bash
   chmod 755 storage/logs
   chmod 644 storage/logs/meeting_status_updates.log
   ```

3. **Script cron ne fonctionne pas**
   - Vérifier le chemin absolu du script
   - Vérifier les permissions d'exécution
   - Tester manuellement le script

### Test manuel
```bash
# Tester le script cron manuellement
php /chemin/vers/GSCV+/public/assets/traitements/cron_update_meeting_status.php
```

## Sécurité

- Le script cron n'a pas besoin d'authentification
- Les logs ne contiennent que les informations de statut
- Aucune donnée sensible n'est exposée

## Performance

- Vérification optimisée : seulement les réunions programmées ou en cours
- Mise à jour conditionnelle : seulement si le statut a changé
- Logs avec verrouillage pour éviter les conflits 