# Réclamations étudiantes

Ce lot reprend les écrans historiques `reclamations.php` (étudiant) et `reclamations_etudiants.php` (service) : dépôt, cartes de suivi, filtres, tableau et historique des réponses. La palette Check Master, Poppins, les logos et la navigation sont conservés.

## Parcours

1. L’étudiant ouvre **Mes réclamations**, choisit l’une de ses inscriptions et décrit sa demande. Un justificatif PDF de 10 Mo maximum peut être ajouté.
2. La scolarité consulte les demandes, prend en charge un dossier, répond et le clôt, ou le transmet à un responsable de filière explicitement choisi.
3. Le responsable de filière désigné consulte uniquement les dossiers qui lui sont affectés. Il peut ajouter un complément et enregistrer la réponse finale.
4. L’étudiant suit les étapes et les réponses dans son dossier ; il peut compléter une demande ouverte. Toutes les réponses et tous les motifs sont visibles par l’étudiant et les personnes habilitées au suivi.

Une transmission n’est pas une résolution. Les états sont **En attente**, **En cours à la scolarité**, **Transmise à la filière**, **Traitée**, **Retirée par l’étudiant**. La scolarité peut répondre directement à une demande en attente. Une fois transmise, la réponse finale appartient au responsable de filière affecté ; la scolarité peut réaffecter la demande avec un motif. La réaffectation retire l’accès lié à l’ancienne affectation, sans retirer d’autres droits de consultation éventuellement détenus par le compte.

Avant prise en charge, l’étudiant peut retirer sa demande avec un motif. Une demande retirée reste consultable et n’est pas rouverte : une nouvelle demande peut être déposée. La scolarité peut rouvrir une demande traitée avec un motif ; elle revient alors à la scolarité et son affectation de filière est retirée. L’ensemble de l’historique est conservé.

Le rattachement étudiant utilise exclusivement `students.user_id`. Une adresse e-mail identique ne suffit pas. L’inscription ne doit pas être annulée et le dossier ne doit pas être archivé au moment d’un nouveau dépôt. Une année passée reste recevable : aucune date limite de recours n’a été inventée. Le paiement, le stage et les notes ne conditionnent pas le dépôt d’une réclamation. L’annulation ultérieure d’une inscription ou l’archivage du dossier conserve le suivi existant.

## Lien avec les notes

Depuis un bulletin publié, **Déposer une réclamation sur ce bulletin** préremplit son inscription et sa publication. L’étudiant sélectionne de une à vingt UE/ECUE parmi celles de cette publication, puis décrit le problème. Le formulaire général permet aussi de choisir une publication conservée.

Les noms et codes proviennent du résultat publié et sont figés avec la demande. Les versions corrigées ensuite ne remplacent pas la version contestée. Un bulletin en préparation sans publication n’est pas sélectionnable ; pendant une correction, les publications précédentes restent disponibles. Le droit `grades.self` est nécessaire pour sélectionner un bulletin.

Pour une note absente, un bulletin non encore publié ou une demande administrative, l’étudiant peut déposer sans bulletin et préciser les matières dans sa description. Le sujet libre est conservé pour reprendre l’usage historique et ne pas bloquer ces demandes.

**Traiter une réclamation ne modifie aucune note.** Une correction est effectuée séparément dans le module Évaluations par une personne autorisée, puis publiée selon le parcours existant. Le responsable de filière voit les matières désignées et la demande ; son affectation à une réclamation ne lui donne pas accès aux autres notes du bulletin, aux finances ni aux autres dossiers étudiants.

## Accès

| Permission | Attribution initiale | Périmètre |
| --- | --- | --- |
| `claims.self` | Étudiant | Dépôt et suivi de ses propres inscriptions |
| `claims.view` | Scolarité, administrateur plateforme | Consultation de toutes les réclamations |
| `claims.manage` | Scolarité | Prise en charge, réponses, transmission, réaffectation et réouverture |
| `claims.assigned` | Responsable de filière | Consultation et traitement des dossiers qui lui sont explicitement affectés |

L’administrateur possède initialement la consultation. Les droits sont vérifiés côté serveur, avec compatibilité du type et du groupe ; le droit de gestion exige la consultation. Les autres groupes n’héritent pas de ces droits. Le catalogue compte désormais 42 permissions et 79 attributions initiales.

Un étudiant possédant aussi des rôles administratifs ne peut pas traiter sa propre demande ni être son destinataire de filière. Ce contrôle vise à la fois le compte déposant et le rattachement étudiant actuel. Les destinataires proposés sont des responsables de filière actifs, compatibles et habilités, hors demandeur. Aucune affectation à une cohorte n’est supposée : le modèle actuel ne décrit pas encore cette relation.

Les listes personnelles et de service sont distinctes. Un compte scolarité/administration et filière peut sélectionner **Service scolarité** ou **Mes transmissions de filière**. Les tableaux de bord étudiant, scolarité, administration et filière affichent un compteur et cinq demandes de leur périmètre. Les autres vues restent inchangées. La sélection d’un espace ne change pas les permissions du compte.

## Données, fichiers et historique

- `student_claims` conserve l’inscription, l’auteur, la publication éventuelle, les matières désignées, le texte d’origine, l’état, l’affectation et la révision.
- `claim_events` ajoute chaque dépôt, complément, traitement ou transmission, avec acteur, rôle, motif, date, états et destinataire au moment de l’action. Aucune réponse n’écrase la précédente ; l’historique est paginé par 20 événements.
- L’ancien enum SQL ne décrivait que `En attente` et `Traitée`, alors que le code historique utilisait d’autres valeurs. La v2 stocke des états cohérents et distingue la transmission du traitement final. Aucune donnée personnelle du dump n’est importée.
- Le texte initial et le justificatif sont conservés ; les précisions passent par les compléments. Il n’y a pas de suppression définitive ni de remplacement silencieux de justificatif.
- Le disque `claims` est privé, dans `storage/app/private/claims`, avec nom généré. Seuls les PDF sont acceptés, après vérification du format, de la taille et de la signature. Le téléchargement exige encore l’accès au dossier, vérifie taille et empreinte SHA-256, force le téléchargement et interdit le cache partagé. Les chemins internes et empreintes ne sont pas exposés dans les pages.
- Les transitions sont transactionnelles avec le verrou commun aux accès et aux dossiers. Les droits et la révision sont relus avant écriture. Un formulaire périmé demande un rechargement ; une répétition identique du même envoi retrouve le résultat sans doublon. Une clé réutilisée avec un autre contenu est refusée.
- Le justificatif est retiré si la transaction de dépôt échoue. Une erreur de stockage ne crée pas de dossier partiel.

Les listes sont paginées par 20, avec recherche, année et statut. Les quatre compteurs suivent l’année et la recherche avant le filtre de statut ; cette portée figure sous les cartes. Le total inclut les demandes retirées.

Ce lot ne programme pas de courriel, de message automatique, de rappel ni de délai de recours. Les informations se consultent dans le module et les tableaux de bord. L’éligibilité au mémoire et l’approbation unanime de la commission sont inchangées.

## Tester sur Mac

Depuis la racine du dépôt existant, après enregistrement des éventuelles modifications locales, avec Docker Desktop ouvert :

```bash
git fetch origin
git switch --track origin/refactor/v2-student-claims
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà, utiliser `git switch refactor/v2-student-claims`, puis `git pull --ff-only`. Garder le même dossier/projet Docker pour retrouver la base et les fichiers privés. Les migrations ajoutent deux tables et quatre permissions sans supprimer les données existantes. Ne pas importer le dump ni supprimer le volume MySQL.

Ouvrir **http://localhost:8085** et utiliser des comptes fictifs explicitement rattachés :

1. **Étudiant** : créer une demande sans bulletin et avec un PDF. La retrouver dans le tableau de bord et le suivi ; vérifier la description, le fichier et le premier événement.
2. **Étudiant / notes** : ouvrir un bulletin publié, cliquer sur le dépôt de réclamation, désigner une UE/ECUE et envoyer. Vérifier que la publication sélectionnée reste la même après une correction publiée par la scolarité.
3. **Scolarité** : filtrer la liste, prendre en charge une demande, ajouter une réponse, puis la traiter. Rouvrir avec un motif et vérifier la conservation de la réponse finale précédente.
4. **Scolarité / filière** : transmettre un autre dossier à un responsable précis. Un second responsable ne doit pas pouvoir le lire. Réaffecter au second responsable et vérifier la perte d’accès du premier. Le destinataire actuel ajoute sa réponse finale.
5. **Étudiant** : ajouter un complément à une demande ouverte ; retirer une autre demande encore en attente. Les demandes retirées restent consultables. Le retrait après prise en charge n’est plus proposé.
6. **Confidentialité** : vérifier qu’un autre étudiant ne peut pas ouvrir le détail ou le PDF, que l’administrateur initial consulte sans traiter et qu’un compte multigroupe ne peut pas traiter sa propre demande.
7. **Deux onglets** : ouvrir le même dossier, agir dans le premier puis envoyer depuis le second ; le second doit demander un rechargement. Vérifier aussi l’affichage sur petit écran, la navigation clavier et les filtres sans résultat.

`StudentClaimsTest` couvre ces règles, les revocations, les droits incompatibles, les publications conservées, les PDF invalides ou altérés, les échecs de stockage, les doubles envois, les filtres et les périmètres des tableaux de bord. La CI Docker valide TypeScript/Vite, Composer, la suite PHP, les migrations MySQL et HTTP. La recette réelle sur Docker Desktop Mac reste à réaliser par l’utilisateur.
