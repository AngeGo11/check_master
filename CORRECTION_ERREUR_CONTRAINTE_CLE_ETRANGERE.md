# Correction de l'erreur de contrainte de clé étrangère

## Problème identifié

L'erreur suivante se produisait lors de l'affectation d'utilisateurs :

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: 
a foreign key constraint fails (`check_master_db`.`utilisateur`, 
CONSTRAINT `fk_niveau_acces` FOREIGN KEY (`id_niveau_acces`) 
REFERENCES `niveau_acces_donnees` (`id_niveau_acces`))
```

## Causes du problème

1. **Incohérence des noms de champs** : Le formulaire utilisait des noms de champs différents de ceux attendus par le contrôleur
2. **Gestion incorrecte des valeurs vides** : Les chaînes vides (`""`) étaient passées à la base de données au lieu de `NULL`
3. **Absence de validation** : Aucune vérification n'était faite pour s'assurer que les valeurs de niveau d'accès existent dans la table de référence

## Corrections apportées

### 1. Correction des noms de champs dans le contrôleur

**Fichier :** `app/Views/listes/liste_utilisateurs.php`

**Avant :**
```php
$result = $utilisateurController->assignMultipleUsers(
    $_POST['selected_inactive_users'],
    $_POST['assign_type_utilisateur'] ?? null,
    $_POST['assign_groupe_utilisateur'] ?? null,
    $_POST['assign_niveau_acces'] ?? null
);
```

**Après :**
```php
// Traitement des valeurs pour éviter les erreurs de contrainte
$type_utilisateur = !empty($_POST['id_type_utilisateur']) ? $_POST['id_type_utilisateur'] : null;
$groupe_utilisateur = !empty($_POST['id_GU']) ? $_POST['id_GU'] : null;
$niveau_acces = !empty($_POST['id_niveau_acces']) ? $_POST['id_niveau_acces'] : null;

$result = $utilisateurController->assignMultipleUsers(
    $_POST['selected_inactive_users'],
    $type_utilisateur,
    $groupe_utilisateur,
    $niveau_acces
);
```

### 2. Amélioration de la gestion des niveaux d'accès dans le modèle

**Fichier :** `app/Models/Utilisateur.php`

**Avant :**
```php
if ($niveau_acces !== null && $niveau_acces !== 0) {
    $stmt = $this->db->prepare("UPDATE utilisateur SET id_niveau_acces = ? WHERE id_utilisateur = ?");
    $stmt->execute([$niveau_acces, $id_utilisateur]);
}
```

**Après :**
```php
if ($niveau_acces !== null && $niveau_acces !== 0 && $niveau_acces !== '') {
    // Vérifier que le niveau d'accès existe dans la table de référence
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM niveau_acces_donnees WHERE id_niveau_acces = ?");
    $stmt->execute([$niveau_acces]);
    if ($stmt->fetchColumn() > 0) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET id_niveau_acces = ? WHERE id_utilisateur = ?");
        $stmt->execute([$niveau_acces, $id_utilisateur]);
    } else {
        throw new Exception("Le niveau d'accès sélectionné n'existe pas dans la base de données");
    }
} else {
    // Si aucun niveau d'accès n'est sélectionné, mettre NULL
    $stmt = $this->db->prepare("UPDATE utilisateur SET id_niveau_acces = NULL WHERE id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
}
```

### 3. Amélioration de la gestion des erreurs

**Fichier :** `app/Views/listes/liste_utilisateurs.php`

- Ajout de messages d'erreur détaillés
- Affichage des erreurs spécifiques pour chaque utilisateur
- Interface utilisateur améliorée avec des détails d'erreur pliables

### 4. Validation côté client

**Fichier :** `app/Views/listes/liste_utilisateurs.php`

Ajout d'une fonction JavaScript `validateForm()` qui :
- Vérifie qu'au moins un utilisateur est sélectionné
- Vérifie que les champs requis sont remplis
- Demande confirmation avant l'envoi

## Tests

Un script de test a été créé : `test_affectation_utilisateurs.php`

Ce script vérifie :
1. Les niveaux d'accès disponibles dans la base de données
2. Les utilisateurs inactifs disponibles
3. L'affectation avec des valeurs valides
4. L'affectation avec un niveau d'accès NULL

## Résultat

Après ces corrections :
- ✅ Les erreurs de contrainte de clé étrangère sont éliminées
- ✅ Les valeurs vides sont correctement gérées (NULL au lieu de chaînes vides)
- ✅ La validation empêche les erreurs côté client
- ✅ Les messages d'erreur sont plus informatifs
- ✅ L'interface utilisateur est plus robuste

## Utilisation

1. Exécuter le script de test : `php test_affectation_utilisateurs.php`
2. Tester l'affectation d'utilisateurs via l'interface web
3. Vérifier que les erreurs ne se reproduisent plus

## Notes importantes

- Les utilisateurs peuvent maintenant être affectés sans niveau d'accès (NULL)
- La validation côté serveur empêche les valeurs invalides
- Les erreurs sont maintenant plus explicites et aident au débogage 