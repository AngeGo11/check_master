# Guide d'installation OnlyOffice pour GSCV+

## Vue d'ensemble

Ce guide explique comment configurer OnlyOffice Document Server pour l'édition de rapports dans GSCV+.

## Option 1 : Utilisation d'un serveur OnlyOffice public (Recommandé pour les tests)

### 1. Configuration rapide
- Utilisez un serveur OnlyOffice public comme `https://onlyoffice.github.io/sdkjs-plugins/example/`
- Modifiez l'URL dans `app/Views/rapports.php` ligne ~720

### 2. Avantages
- Configuration rapide
- Pas d'installation locale requise
- Idéal pour les tests et le développement

### 3. Inconvénients
- Limité en fonctionnalités
- Pas de contrôle total sur les données
- Dépendant d'un service externe

## Option 2 : Installation locale d'OnlyOffice Document Server

### 1. Prérequis
- Docker installé
- Au moins 4GB de RAM disponible
- Ports 80 et 443 disponibles

### 2. Installation avec Docker

```bash
# Créer un réseau Docker
docker network create onlyoffice

# Lancer OnlyOffice Document Server
docker run -i -t -d -p 80:80 --name onlyoffice-document-server \
    -v /app/onlyoffice/DocumentServer/logs:/var/log/onlyoffice \
    -v /app/onlyoffice/DocumentServer/data:/var/www/onlyoffice/Data \
    -v /app/onlyoffice/DocumentServer/lib:/var/lib/onlyoffice \
    -v /app/onlyoffice/DocumentServer/db:/var/lib/postgresql \
    onlyoffice/documentserver
```

### 3. Configuration du serveur

#### A. Accéder à l'interface d'administration
- Ouvrez `http://localhost` dans votre navigateur
- Connectez-vous avec les identifiants par défaut

#### B. Configurer les domaines autorisés
- Allez dans Paramètres > Intégration
- Ajoutez votre domaine : `localhost` ou votre domaine de production

#### C. Configurer le stockage de fichiers
- Allez dans Paramètres > Stockage
- Configurez le stockage local ou cloud selon vos besoins

### 4. Modification de la configuration dans GSCV+

Dans `app/Views/rapports.php`, modifiez l'URL du serveur :

```javascript
// Ligne ~720
const onlyofficeServerUrl = 'http://localhost'; // Pour installation locale
// ou
const onlyofficeServerUrl = 'https://votre-domaine.com'; // Pour production
```

## Option 3 : Utilisation d'un service cloud OnlyOffice

### 1. Services recommandés
- **OnlyOffice Cloud** : https://www.onlyoffice.com/cloud.aspx
- **Nextcloud avec OnlyOffice** : https://nextcloud.com/onlyoffice/

### 2. Configuration
- Créez un compte sur le service choisi
- Obtenez l'URL de votre serveur OnlyOffice
- Modifiez la configuration dans le code

## Configuration des fichiers de traitement

### 1. Vérifier les permissions
```bash
chmod 755 public/assets/traitements/
chmod 644 public/assets/traitements/onlyoffice_callback.php
chmod 644 public/assets/traitements/save_document.php
```

### 2. Tester la connectivité
```bash
curl -X POST http://localhost/GSCV+/public/assets/traitements/onlyoffice_callback.php
```

## Dépannage

### Problème : L'éditeur ne se charge pas
**Solution :**
1. Vérifiez que l'URL du serveur OnlyOffice est correcte
2. Vérifiez les logs du serveur OnlyOffice
3. Utilisez l'éditeur de fallback en cas d'erreur

### Problème : Erreur de sauvegarde
**Solution :**
1. Vérifiez les permissions des dossiers de sauvegarde
2. Vérifiez la configuration de la base de données
3. Consultez les logs PHP

### Problème : Le modèle ne se charge pas
**Solution :**
1. Vérifiez que le fichier `storage/templates/modele_rapport_de_stage.docx` existe
2. Vérifiez les permissions du fichier
3. Testez l'accès direct au fichier via URL

## Sécurité

### 1. Configuration HTTPS (Production)
```javascript
const onlyofficeServerUrl = 'https://votre-domaine.com';
```

### 2. Validation des données
- Tous les fichiers de traitement incluent une validation des données
- Les fichiers sont sauvegardés avec des noms uniques
- Les permissions sont configurées de manière sécurisée

### 3. Logs et monitoring
- Les erreurs sont loggées dans `storage/logs/`
- Surveillez les logs pour détecter les problèmes

## Support

Pour toute question ou problème :
1. Consultez la documentation OnlyOffice : https://helpcenter.onlyoffice.com/
2. Vérifiez les logs d'erreur
3. Testez avec l'éditeur de fallback

## Notes importantes

- L'éditeur de fallback est toujours disponible en cas de problème avec OnlyOffice
- Les documents sont sauvegardés en format DOCX et HTML
- La configuration peut être modifiée selon vos besoins spécifiques 