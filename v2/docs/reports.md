# Lot 5 — dépôt, versions et approbation administrative des mémoires

Ce lot poursuit le parcours du lot 4 : un étudiant dont l’inscription possède un examen d’éligibilité favorable peut déposer son mémoire au format PDF. Le service habilité consulte le document, demande une correction avec un commentaire ou donne une approbation administrative. Chaque version et chaque décision restent consultables.

## Continuité avec Check Master

La référence reste le commit historique `c143941cbf446a851dc7ae1f6d09466cc3b1fee2`. L’écran étudiant reprend les trois cartes de `app/Views/rapports.php` : **Création du rapport**, **Statut du dépôt**, **Commentaires**. Les logos, les bleus, Poppins, le menu latéral, les boutons et les tableaux restent ceux du socle amélioré. Le formulaire et l’historique prolongent ces cartes sur la même page.

Les opérations historiques d’approbation, de retour et de commentaire présentes dans `app/Controllers/EtudiantsController.php` deviennent des décisions attachées à une version précise. « Correction demandée » rend explicite la possibilité d’un nouveau dépôt. Le tableau de bord affiche désormais les nombres réels de mémoires et de dossiers en attente aux personnes autorisées ; le panneau du dernier rapport est limité au dossier personnel pour l’étudiant.

Le dépôt de PDF est disponible. L’éditeur de document intégré, la validation par une commission, l’affectation des jurys, les demandes de soutenance, les notes, les notifications par e-mail et l’import des anciens fichiers restent à reprendre.

## Parcours et règles

| Situation | Action possible | Résultat |
| --- | --- | --- |
| Aucun mémoire, inscription éligible | L’étudiant dépose un PDF et son thème | Version 1, « En attente d’approbation » |
| En attente d’approbation | Le service habilité demande une correction avec un commentaire | « Correction demandée » ; le PDF précédent est conservé |
| Correction demandée, inscription éligible | L’étudiant dépose son PDF corrigé | Nouvelle version, retour en attente d’approbation |
| En attente d’approbation, inscription toujours éligible, PDF intact | Le service habilité approuve avec un commentaire | « Approuvé » ; aucun nouveau dépôt dans ce lot |

L’approbation est **administrative**. Elle ne constitue pas une validation par le jury. Un seul dossier de mémoire existe par étudiant ; ses versions sont numérotées sans écrasement. Une version conserve son thème, son inscription et l’identifiant de l’examen d’éligibilité ayant permis le dépôt. Après une correction, une autre inscription personnelle éligible de l’année en cours peut être sélectionnée ; les versions antérieures gardent leur inscription d’origine.

Le dépôt et l’approbation recalculent les trois critères du lot 4 et exigent un examen favorable encore valide. Un dossier archivé, une inscription annulée, une année fermée, un parcours À cheval ou un examen périmé bloquent ces opérations. Le simple fait de remplir les critères ne remplace pas l’examen explicite. Déposer un PDF ne modifie pas la révision de l’inscription et ne périme donc pas cet examen.

Une demande de correction reste possible si l’éligibilité est devenue périmée ou si le fichier est indisponible. Le nouveau dépôt demandera à nouveau une éligibilité valide. Une approbation déjà enregistrée demeure une décision historique si la situation change ensuite ; l’écran montre la situation actuelle. Le futur parcours de commission devra la vérifier à nouveau.

## Accès et documents

| Permission | Accès | Attribution initiale |
| --- | --- | --- |
| `reports.self` avec type Étudiant | Déposer et lire uniquement le mémoire du dossier explicitement rattaché au compte | Groupe Étudiant |
| `reports.view` | Liste, recherche, historique, commentaires et téléchargement de tous les mémoires | Administrateurs plateforme |
| `reports.review` | Approbation ou demande de correction | Administrateurs plateforme |

La délégation de `reports.review` exige aussi `reports.view`. Les droits de scolarité et de gestion des étudiants ne donnent pas automatiquement accès aux PDF. Le déposant et le compte actuellement rattaché au dossier ne peuvent pas examiner ce mémoire, même avec un second rôle d’administration. Un changement de rattachement ou de droits est pris en compte dès la requête suivante. Aucun rattachement n’est déduit d’une égalité d’adresses e-mail.

Les fichiers doivent porter l’extension `.pdf`, avoir un type MIME PDF détecté, commencer par la signature PDF et peser au maximum 20 Mo (20 × 1 024 × 1 024 octets). Ces contrôles ne constituent ni une analyse complète du format ni une analyse antivirus. Une empreinte SHA-256 et la taille sont conservées ; toute altération constatée bloque le téléchargement et l’approbation.

Le disque local dédié `reports` pointe vers `storage/app/private/reports`. Les noms sont aléatoires, indépendants du nom choisi par l’étudiant. Les PDF ne sont pas placés dans `public/` et ne nécessitent aucun lien de stockage public. Le téléchargement passe par les routes authentifiées, vérifie l’appartenance de la version au mémoire et impose une pièce jointe avec `nosniff` et `Cache-Control: private, no-store`. Les réponses de page ne contiennent ni chemin interne, ni empreinte, ni ancienne clé de dépôt, ni identifiant du compte déposant. La consultation des mémoires n’expose pas les montants de scolarité.

## Cohérence des écritures

- Le verrou d’administration partagé avec les lots précédents précède la relecture des permissions, du rattachement, de l’inscription et de l’éligibilité. Les révisions refusent un formulaire devenu ancien, notamment après un dépôt corrigé.
- La clé de dépôt rend une répétition identique sans effet supplémentaire. Réutiliser cette clé avec un autre contenu, thème, compte ou inscription est refusé. La limitation HTTP autorise six tentatives de dépôt par minute et par compte.
- Une décision vise uniquement la dernière version en attente. Une contrainte unique interdit deux décisions sur la même version.
- La transaction écrit dossier, version, décision et audit. En cas d’échec après écriture du PDF, le nouveau fichier non référencé est supprimé. La transaction ne rejoue pas automatiquement une écriture de fichier.
- Un arrêt brutal du processus peut laisser un fichier privé sans référence : un futur outil de rapprochement devra vérifier les références avant tout nettoyage. Les sauvegardes doivent conserver ensemble la base et le répertoire privé ; les PDF ne sont pas contenus dans le volume MySQL.
- L’audit conserve acteur, mémoire, version, action et références de décision, sans copier les titres, commentaires ou contenus des documents. Son écran de consultation reste à réaliser.

## Mettre à jour sur Mac

Ouvrir Docker Desktop. Depuis la racine du même dépôt que celui utilisé pour le lot 4, après avoir enregistré les modifications locales :

```bash
git fetch origin
git switch --track origin/refactor/v2-reports
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà, utiliser `git switch refactor/v2-reports`, puis `git pull --ff-only` à sa place. Conserver le même dossier et projet Docker pour retrouver les comptes, inscriptions et règlements existants. Ne pas supprimer les volumes ni `storage/app/private/reports`. Pour une première installation, suivre le [README](../README.md).

Ouvrir **http://localhost:8085**. La migration ajoute les tables et les trois permissions ; elle n’importe aucun compte, mémoire, PDF ou commentaire historique. Aucun compte réel n’a été créé pour ces tests.

## Scénario de vérification

Utiliser deux comptes de démonstration distincts : un administrateur nominatif et un étudiant. Une fenêtre privée permet d’ouvrir la session étudiant en parallèle.

1. Avec l’administrateur, créer au besoin le compte étudiant dans les paramètres, lui affecter le type et le groupe **Étudiant**, puis le rattacher explicitement à sa fiche. L’étudiant doit personnaliser son mot de passe temporaire à sa première connexion.
2. Préparer une fiche non archivée, niveau **Master 2**, statut **Normal**, et une inscription dans une année couvrant la date du test, marquée **En cours**. Configurer un tarif de **100 000 FCFA**, solder l’inscription, déclarer un stage déjà commencé puis enregistrer un examen d’éligibilité favorable. Le [guide du lot 4](enrollments.md) détaille ce parcours.
3. Avec l’étudiant, ouvrir **Mon rapport / Mémoire**. Vérifier les trois cartes, choisir l’inscription, saisir le thème et déposer un petit PDF fictif. La version 1 doit apparaître en attente d’approbation ; la télécharger pour vérifier son contenu.
4. Avec l’administrateur, ouvrir **Rapports / Mémoires**, filtrer sur la carte de cet étudiant, consulter le document et demander une correction avec un commentaire précis.
5. Avec l’étudiant, vérifier le commentaire, déposer un PDF modifié et vérifier que les versions 1 et 2 sont toutes deux téléchargeables. Le commentaire reste attaché à la version 1.
6. Avec l’administrateur, télécharger la version 2 et enregistrer une approbation. Avec l’étudiant, vérifier le statut **Approuvé**, l’historique et l’absence de nouveau formulaire de dépôt.
7. Avec un autre compte étudiant rattaché à un autre dossier, reprendre l’URL de téléchargement : l’accès doit être refusé. Vérifier aussi qu’un document non PDF est refusé sur un dossier encore ouvert au dépôt.
8. Sur un autre dossier en attente, modifier une information d’inscription ou retirer le stage après le dépôt : l’approbation doit être bloquée jusqu’à régularisation et nouvel examen. Une demande de correction doit rester possible.

Contrôler les mêmes écrans sur une largeur mobile, le menu repliable, le focus clavier, les messages d’erreur et les boutons de téléchargement. Les éléments visuels doivent rester reconnaissables par rapport à l’application existante.

## Vérifications automatisées et limites

`ReportDepositsTest` ajoute 33 scénarios : permissions, propriété des dossiers et versions, fichiers privés, validation des fichiers, éligibilité à jour, absence de doublons, révisions, corrections, approbation, conservation des commentaires, échecs de stockage/base, intégrité des téléchargements, filtres, pagination, tableau de bord, CSRF et limitation des dépôts. Les tests des lots précédents sont conservés.

La suite applicative utilise SQLite en mémoire et des PDF fictifs. Le workflow Docker applique les migrations à MySQL, vérifie Composer, compile TypeScript/Vite puis contrôle la disponibilité HTTP. Le contrôle visuel sur Mac et un essai de concurrence avec deux connexions MySQL simultanées restent à réaliser. Le résultat précis de chaque exécution est suivi dans la pull request de ce lot.
