# Piste d’audit

La v2 rassemble les actions déjà enregistrées dans une page **Piste d’audit**, accessible par le menu, les paramètres et le raccourci du tableau de bord administrateur. Les logos, Poppins, couleurs, cartes et tableaux de Check Master sont conservés.

## Accès

| Permission | Groupe par défaut | Usage |
| --- | --- | --- |
| `audit.view` | Administrateur plateforme (8), avec type compatible | Liste, indicateurs et détail |
| `audit.export` | Administrateur plateforme (8), avec type compatible | Export CSV ; exige aussi `audit.view` |

Ces droits sont réservés à ce groupe. Une attribution incorrecte à un autre groupe ne donne pas accès, même insérée directement en base. Les permissions sont vérifiées sur chaque requête. Le groupe administrateur reste protégé dans l’éditeur des permissions. Les comptes désactivés, sessions révoquées et mots de passe temporaires suivent les contrôles existants. Aucun droit de lecture de messages, bulletins ou pièces jointes n’est accordé par l’audit.

## Actions couvertes

| Module affiché | Source conservée |
| --- | --- |
| Comptes et accès | `access_audits` |
| Dossiers étudiants | `student_audits` |
| Inscriptions et règlements | `enrollment_audits` |
| Rapports et mémoires | `report_audits` |
| Réunions et messagerie | `collaboration_audits` |
| Ressources humaines | `staff_audits` |
| Notes et soutenances | `academic_audits` |
| Réclamations | `claim_events` |

Il s’agit des opérations métier conservées par les modules v2. Les connexions, tentatives refusées et anciennes entrées de l’application historique ne sont pas comptabilisées. Les consultations et exports du journal ne créent pas d’événement supplémentaire. Ce lot n’importe aucune donnée personnelle du SQL historique.

Les événements ne sont ni copiés ni fusionnés en une nouvelle table : une lecture commune préserve les huit historiques. La migration ajoute les deux permissions et des index date/identifiant et auteur/date. Aucun événement n’est modifié ou supprimé par les routes d’audit. Le retour arrière de cette migration retire seulement les droits et index ajoutés ; les historiques restent en place. Ce journal n’est pas une archive cryptographique et reste administrable au niveau de la base de données.

## Recherche et détail

- Module, action enregistrée, nom d’utilisateur ou identifiant exact tel que `#42`, et dates de début/fin combinables.
- Les caractères `%`, `_`, `!` et apostrophes dans le nom sont traités comme du texte, pas comme des opérateurs de recherche.
- Le filtre « Sans compte associé » retrouve les événements système et ceux dont le compte n’est plus disponible ; ces cas sont distingués dans la colonne utilisateur. Les filtres se cumulent.
- Les bornes de dates sont inclusives et utilisent l’heure d’Abidjan (UTC), comme les heures affichées et l’export.
- Les cartes comptent les actions, les auteurs identifiés distincts, les modules et la dernière action de toute la sélection appliquée. Les entrées sans auteur ne gonflent pas le nombre d’utilisateurs.
- Le journal présente 25 lignes par page, de la plus récente à la plus ancienne ; module puis identifiant départagent les horodatages identiques. La clé `module:identifiant` distingue des lignes portant le même numéro dans des tables différentes.
- Le retour depuis un détail conserve les filtres et le numéro de page. Le lien sur un compte applique son identifiant exact.

Le nom affiché provient du compte actuel ; à défaut, le nom conservé lors d’une réclamation est utilisé. Il ne s’agit donc pas d’un instantané systématique de l’identité au moment de l’action. Le détail affiche des références, les noms de champs modifiés, les droits avant/après et les changements de statut disponibles. Il ne renvoie jamais le JSON brut. Les valeurs des notes, coordonnées personnelles modifiées, messages, motifs libres, mots de passe, empreintes, clés d’opération et chemins de fichiers sont exclus.

## Export CSV

L’export reprend **toutes les pages des filtres appliqués**, dans le même ordre que le tableau. Cliquer sur « Appliquer les filtres » après une modification du formulaire avant d’exporter. Le fichier contient les références, dates UTC, modules, actions et auteurs ; les détails privés ne sont pas exportés.

Le fichier utilise UTF-8 avec BOM, séparateur point-virgule et échappement des guillemets/retours à la ligne. Les cellules susceptibles d’être interprétées comme des formules sont neutralisées. Au-delà de **10 000 événements**, un message invite à affiner la sélection, sans fichier partiel ni troncature silencieuse. Une sélection vide produit seulement les en-têtes. L’export est limité à six requêtes par minute par compte. Liste, détail et téléchargement demandent au navigateur de ne pas mettre les données en cache.

## Tester sur Mac

Depuis le dépôt existant, après avoir sauvegardé les modifications locales éventuelles :

```bash
git fetch origin
git switch --track origin/refactor/v2-audit-trail
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà, utiliser `git switch refactor/v2-audit-trail` puis `git pull --ff-only` avant de lancer les scripts. Le script d’installation conserve le volume MySQL et applique les nouvelles migrations. Ne pas remplacer la base de travail par le SQL historique pour tester ce lot.

Ouvrir **http://localhost:8085** et se connecter avec le compte administrateur déjà créé. La création d’un administrateur depuis le terminal reste décrite dans [le guide des accès](access-control.md).

1. Ouvrir Piste d’audit depuis le menu et vérifier les cartes, colonnes et filtres. Une base sans activité doit afficher un état vide clair.
2. Modifier un dossier fictif ou un compte de test dans les modules existants. Revenir au journal : l’action, l’auteur et la référence doivent apparaître. Les valeurs personnelles modifiées ne doivent pas apparaître dans son détail.
3. Filtrer par module, action et auteur, puis sur une seule journée. Vérifier que la date de fin est incluse et que les cartes correspondent aux lignes de toutes les pages.
4. Avec plus de 25 actions, aller à la deuxième page, consulter une ligne et revenir. Exporter la sélection et vérifier que le fichier contient aussi les lignes des autres pages.
5. Vérifier un message et une réclamation fictifs : le journal indique l’opération et la référence, sans contenu privé ni justificatif.
6. Se connecter en étudiant, scolarité ou membre de commission. Le menu d’audit doit être absent et les URL `/audit`, `/audit/export` et les détails doivent être refusés.
7. Vérifier la navigation au clavier et l’affichage sur écran étroit : filtres lisibles, tableau défilable et détail sur une colonne.

Les tests automatisés couvrent les droits, les huit sources, les dates, les filtres combinés, l’ordre/pagination, les redactions, le CSV et sa limite, l’absence de routes de modification et la limitation des téléchargements. `scripts/check.sh` exécute aussi les requêtes d’audit en lecture seule sur le MySQL de Docker, en complément des tests fonctionnels SQLite, puis le contrôle HTTP Nginx/PHP/connexion. La recette visuelle réelle sur Mac reste à effectuer.
