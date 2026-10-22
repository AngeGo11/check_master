# Lot 5 demandé — Réunions et messagerie

Branche : `refactor/v2-meetings-messaging`, construite sur `fix/v2-report-workflow`. Ce lot améliore les fonctions et les repères visuels existants. Les anciens guides appelaient le module mémoires « lot 5 » ; la désignation de ce document suit la demande actuelle.

## Périmètre et droits

Les tables historiques `reunions`, `participants`, `documents`, `messages` et les catalogues de groupes/actions du fichier joint `check_master_db (8).sql` servent de référence. Le fichier est inchangé (SHA-256 `921bf93160eea570c2109d0852d1c5063883c4dbf70c0a8cb3b0ae4276c66454`). Les données personnelles historiques ne sont pas importées. Les appartenances aux groupes sont explicites et doivent correspondre au type d'utilisateur.

| Groupe | Messagerie | Réunions |
| --- | --- | --- |
| Étudiant, scolarité, secrétaire, enseignant, responsable niveau/filière | Boîte personnelle ; jusqu'à 20 destinataires par message | Aucun accès par défaut |
| Chargé de communication | Boîte personnelle ; sélection de groupes, jusqu'à 200 destinataires | Aucun accès par défaut |
| Commission de validation | Boîte personnelle ; sélection de groupes, jusqu'à 200 destinataires | Réunions créées ou auxquelles la personne est invitée ; chaque organisateur gère ses propres séances |
| Administrateur plateforme | Sa boîte personnelle ; sélection de groupes | Consultation globale pour le suivi ; organisation seulement avec appartenance explicite supplémentaire à la commission |

Permissions ajoutées : `messages.use`, `messages.broadcast`, `meetings.view`, `meetings.manage`. Le droit de diffusion exige celui de messagerie ; la gestion des réunions exige la consultation. Les droits spécifiques aux réunions et à la diffusion restent limités aux groupes indiqués. Les comptes désactivés ou sans appartenance compatible sont exclus des destinataires. Un mot de passe temporaire permet de recevoir une invitation mais doit être personnalisé avant toute consultation.

## Réunions

- Liste filtrée, cartes de statut, calendrier mensuel et trois prochaines séances sur le tableau de bord. Les heures sont saisies et affichées à Abidjan (`Africa/Abidjan`), stockées en UTC.
- Titre, ordre du jour, priorité ordinaire/urgente, lieu ou lien, début futur et durée de 15 à 720 minutes. Jusqu'à 200 participants, organisateur compris ; celui-ci est toujours inclus.
- Invitations dans la messagerie interne. Chaque participant confirme ou décline sa propre participation. Les créneaux qui se chevauchent pour des participants déjà invités sont refusés ; une invitation déclinée ou une réunion annulée libère le créneau. Une confirmation ultérieure revérifie les conflits.
- Modification avec motif avant le début : les réponses sont réinitialisées, les invitations actualisées et les anciennes réponses conservées dans l'audit. Les invités retirés perdent l'accès à la fiche et aux documents ; leur ancienne invitation reste dans leur boîte, sans lien accessible. Une réinvitation exige une nouvelle réponse.
- Annulation motivée, conservée dans l'historique et annoncée aux participants. L'état « En cours » puis « Terminée » découle de l'heure ; une consultation n'écrit pas en base.
- PDF de séance privés, 20 Mo maximum, ajoutés par l'organisateur et téléchargés par les participants courants ou l'administrateur. Type, taille et empreinte sont vérifiés. Un retrait motivé désactive le téléchargement et conserve le fichier et l'audit. Des documents peuvent être ajoutés après la séance ; une séance annulée n'accepte plus de nouveaux documents.
- Export `.ics` d'une séance ou du mois, limité aux réunions accessibles. L'export mensuel contient toutes les réunions accessibles du mois, indépendamment des filtres de recherche/statut. Le calendrier affiche au plus 500 séances et signale cette limite ; un export de plus de 500 est refusé explicitement. L'utilisateur importe lui-même le fichier dans son calendrier.

Un mémoire à l'ordre du jour doit avoir une transmission de commission courante, ouverte, déjà reçue par chaque participant. L'association n'accorde aucun nouvel accès au mémoire, ne transmet aucun rapport et ne vote pas. Les approbations restent individuelles et l'acceptation reste unanime. Si les droits sur le rapport sont retirés, son lien disparaît de la réunion.

## Messagerie interne

Réception, Non lus, Envoyés, Brouillons, Archives et Corbeille ; recherche dans l'objet, le contenu et le nom de l'expéditeur, filtres de priorité/catégorie, pagination et compteur personnel de non-lus. Le contenu est affiché en texte, sans exécution HTML.

La recherche de contacts propose les comptes actifs autorisés. Pour les groupes habilités à diffuser, le choix d'un groupe affiche une liste nominative que l'expéditeur peut modifier. Seuls les contacts affichés et envoyés explicitement sont destinataires ; une modification ultérieure du groupe n'étend pas l'envoi. Si un destinataire devient indisponible, l'envoi entier est refusé pour permettre de corriger la liste.

Un brouillon est visible uniquement par son auteur et ne crée aucune copie destinataire. Un envoi est immuable et un double clic ne le duplique pas. Chaque personne gère son propre état lu/non lu, ses archives et sa corbeille ; ces actions ne modifient jamais la boîte des autres, y compris lorsque l'administrateur agit sur sa propre boîte. Ouvrir un message ne le marque pas automatiquement comme lu : le bouton dédié permet de le décider.

« Répondre » vise l'expéditeur du message reçu. « Transférer » prépare le contenu de ce seul message, modifiable avant envoi, sans partager l'accès au message source. L'historique affiche uniquement les messages déjà accessibles à la personne. Les notifications de réunion proposent le lien de la séance seulement si les droits sont toujours valides. Elles ne se traitent pas comme des demandes de réponse par message : la confirmation se fait dans la fiche de réunion.

## Base de données et conservation

La migration crée des tables transactionnelles séparées pour réunions, participants, ordre du jour, documents, messages, copies destinataires et audit. Elle remplace le modèle historique de messages MyISAM à état partagé par un état personnel par destinataire. Les clés étrangères, contraintes uniques, index de boîte et de planning, références d'opération et révisions protègent la cohérence et les répétitions. Les écritures relisent les permissions sous le verrou transactionnel partagé avec l'administration des comptes.

Les fichiers sont dans `storage/app/private/meetings`, à côté des mémoires dans `storage/app/private/reports`. Sauvegarder ensemble la base, ces deux dossiers et la configuration. Les messages placés dans la corbeille, les invitations retirées, les annulations et les documents retirés ne sont pas supprimés physiquement. Le retour arrière de cette migration exige une restauration cohérente, pour éviter de supprimer les échanges et leurs historiques.

Les limites de ce lot sont explicites : pas de SMTP, SMS, rappel automatique ni synchronisation avec un compte calendrier externe ; aucun envoi réel effectué pendant le développement. Les documents de séance sont des PDF génériques. Le module formel de comptes rendus, son parcours de publication et sa règle « un compte rendu peut concerner plusieurs rapports, un rapport un seul compte rendu » restent à implémenter dans un lot dédié. Aucun changement au stage déclaré par l'étudiant ni à l'approbation unanime des mémoires.

## Installer et tester sur Mac

Dans le dépôt existant, enregistrer d'abord ses modifications locales, puis :

```bash
git fetch origin
git switch refactor/v2-meetings-messaging
git pull --ff-only
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche n'est pas encore locale, `git switch --track origin/refactor/v2-meetings-messaging` permet de la créer explicitement. Ouvrir **http://localhost:8085**. Conserver le même dossier, `.env`, les fichiers privés et le volume MySQL pour retrouver les données du sprint précédent. Le script applique la nouvelle migration et reconstruit les écrans. Il n'importe pas la base historique et ne recrée pas les comptes existants.

Utiliser des comptes fictifs : deux membres de commission avec type compatible, un administrateur, un étudiant, un chargé de communication. Personnaliser leurs mots de passe temporaires. Utiliser des profils de navigateur séparés pour vérifier plusieurs rôles sans confondre les sessions.

1. Commission A : planifier une séance future avec B. Vérifier les cartes, la liste, le calendrier et les prochaines séances du tableau de bord.
2. Commission B : ouvrir l'invitation dans Messagerie, marquer le message comme lu, suivre le lien et confirmer sa participation. Vérifier que A voit cette réponse, mais ne peut pas répondre à la place de B.
3. A : ajouter un PDF fictif, puis reprogrammer la séance avec motif. B reçoit une actualisation et doit confirmer à nouveau. Retirer B : il conserve la notification mais ne peut plus ouvrir la fiche ni le PDF.
4. A : essayer un créneau chevauchant une invitation existante, puis un créneau adjacent. Vérifier le refus du premier. Exporter un fichier `.ics`, l'importer manuellement dans Calendrier sur Mac et vérifier l'heure, le titre et la durée.
5. Administrateur : consulter la séance et ses documents ; vérifier l'absence des actions d'organisation. Étudiant : aucun accès à l'URL de la réunion.
6. Étudiant : enregistrer un brouillon destiné au chargé de communication, le reprendre puis l'envoyer. Le destinataire ne doit rien recevoir avant l'envoi. Vérifier réponse, transfert vers un autre contact, recherche et non-lus.
7. Destinataire : archiver, mettre à la corbeille puis restaurer le message. Vérifier que le message demeure dans Envoyés chez son auteur et que l'administrateur ne peut pas lire une boîte qui n'est pas la sienne.
8. Chargé de communication : sélectionner le groupe Commission, vérifier les destinataires nominatifs, retirer une personne et envoyer un message fictif. Vérifier que seuls les destinataires conservés le reçoivent.
9. Si un mémoire a été transmis dans le sprint précédent, l'associer à une séance dont tous les participants l'ont reçu. Confirmer les présences : le mémoire doit rester en commission et attendre ses approbations dans son propre dossier.
10. Annuler la séance avec motif, vérifier l'avis reçu et l'état exporté. Vérifier aussi les écrans sur une petite fenêtre : filtres, formulaires, tableaux défilants et calendrier.

## Validation

Le workflow GitHub lance `setup.sh` et `check.sh` dans Docker : compilation TypeScript/Vite, migrations sur MySQL, tests PHP sur SQLite en mémoire et disponibilité HTTP via Nginx. Les tests ajoutés couvrent les invitations, scopes, formulaires obsolètes, révocations, conflits horaires, isolation des PDF, annulation transactionnelle des écritures en erreur, calendriers, brouillons et copies personnelles. Ils utilisent des données fictives et aucun service de messagerie externe.

Cette validation ne remplace pas une vérification visuelle sur le Mac ni un test de charge avec écritures simultanées sur MySQL.
