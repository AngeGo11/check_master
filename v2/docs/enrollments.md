# Lot 4 — inscriptions, règlements et critères d’éligibilité

Ce lot reprend la gestion des inscriptions de Check Master : années académiques, tarifs par niveau, montant à payer, historique des règlements, reçus et examen des critères d’éligibilité. La structure de navigation, les logos, la palette, les quatre cartes de suivi et les tableaux restent ceux de l’interface existante.

## Origine des règles

Sources du projet historique au commit `c143941cbf446a851dc7ae1f6d09466cc3b1fee2` : `app/Views/inscriptions_etudiants.php`, `app/Controllers/InscriptionsEtudiantsController.php`, `app/Models/Reglement.php`, `app/Views/demandes_soutenances.php`, `app/Models/Soutenance.php` et `SYSTEME_ETUDIANTS_CHEVAL.md`. Aucune ligne personnelle ou écriture financière du dump historique n’est importée.

L’écran historique des demandes vérifie **trois critères** : scolarité soldée, niveau Master 2 et stage déclaré. Il n’effectue pas de calcul de notes dans cette décision. La v2 calcule ces critères à partir des données enregistrées, puis exige un examen explicite par une personne habilitée. Les notes et décisions de commission restent des modules distincts.

## Parcours et règles

| Écran | Comportement |
| --- | --- |
| Paramètres → Années et tarifs | Créer une année en attente, en cours ou terminée ; définir les tarifs en FCFA entiers par niveau |
| Inscriptions et règlements | Quatre indicateurs pour l’année sélectionnée, filtres par étudiant/carte, année, niveau, situation et date ; pagination de 20 lignes |
| Nouvelle inscription | Dossier existant, année en cours, niveau identique au dossier, date comprise dans l’année et non future ; tarif calculé côté serveur |
| Fiche d’inscription | Montant initial, payé et solde ; paiements en espèces ou par chèque, historique et reçus imprimables |
| Stage déclaré | Enregistrement par le service habilité de l’entreprise, de l’intitulé, des dates et de la référence du justificatif vérifié |
| Critères d’éligibilité | Affichage des trois critères calculés ; observation obligatoire avant l’enregistrement d’un examen |
| Mes inscriptions | Lecture des seules inscriptions du dossier explicitement rattaché au compte connecté, avec reçus et critères |

Une seule année peut être en cours. L’activation d’une autre clôture la précédente. Une année clôturée peut être rouverte explicitement ; ses anciennes inscriptions conservent leur tarif. Les dates ne peuvent plus changer après la première inscription. Aucune année n’est déduite automatiquement du calendrier ou du dump.

Un tarif est unique par année et niveau. Il est figé dans chaque inscription : sa modification concerne les inscriptions créées ensuite. L’année et le niveau d’un tarif existant ne sont pas modifiables. Les tarifs d’une année terminée sont en consultation.

Une inscription est unique par étudiant, année et niveau, même après annulation. Les statuts Normal et Redoublant sont pris en charge. **À cheval est bloqué explicitement** : le calcul historique dépend des matières à rattraper ; appliquer un tarif ordinaire serait incorrect. Les dossiers archivés et les discordances de niveau bloquent également les nouvelles opérations.

Les paiements sont des **écritures de sommes déjà reçues**, sans paiement bancaire ou encaissement automatique. Le montant est strictement positif, entier, et ne dépasse pas le solde. Le chèque exige un numéro. La date doit être comprise entre l’inscription et la fin de l’année, sans dépasser aujourd’hui.

Chaque soumission porte une clé unique. Une répétition identique par le même acteur retrouve l’écriture existante ; un contenu différent avec la même clé est refusé. Une annulation conserve l’écriture, son reçu marqué annulé, l’acteur et le motif ; elle reste possible après clôture pour corriger une erreur. Elle n’effectue **aucun remboursement**. Une inscription ne peut être annulée tant que des paiements non annulés subsistent. Le rétablissement conserve son montant initial et requiert une année en cours et un dossier compatible.

## Éligibilité et modifications ultérieures

Les critères sont évalués sur l’inscription consultée :

1. Total des paiements non annulés au moins égal au montant dû.
2. Master 2 à la fois sur l’inscription et sur le dossier actuel.
3. Stage déclaré avec justificatif référencé et date de début atteinte. Un stage terminé compte ; un stage futur ne compte pas encore.

L’inscription doit aussi être active, dans l’année en cours, pour un dossier non archivé et hors parcours À cheval. Une personne possédant `eligibility.review` enregistre l’examen et son observation. Le navigateur ne choisit pas le résultat. Le simple remplissage des trois critères laisse le statut « À examiner » jusqu’à cet examen.

Une correction de règlement, de stage, du dossier étudiant, de l’année ou de l’inscription rend l’examen précédent périmé. Un changement de date modifiant un critère, par exemple le début d’un stage, est également détecté. La fiche affiche alors « À examiner » et conserve le dernier résultat pour contexte. Par prudence, toute révision du dossier étudiant ou de l’année invalide l’examen, y compris une modification de coordonnées ou de libellé.

La déclaration de stage peut être remplacée ou retirée avec un motif. Aucun fichier n’est téléversé dans ce lot. Aucun e-mail n’est envoyé automatiquement. L’examen ne crée pas de dépôt de mémoire : le module de dépôt reste à reprendre.

## Droits et intégrité

| Permission | Portée |
| --- | --- |
| `schooling.view` / `schooling.manage` | Consultation / configuration des années et tarifs |
| `enrollments.view` | Toutes les inscriptions, leurs règlements, reçus et critères |
| `enrollments.manage` | Création, annulation/rétablissement, déclaration et correction des stages |
| `payments.manage` | Enregistrement et annulation des écritures de paiement |
| `eligibility.review` | Enregistrement de l’examen des critères |
| `students.self` + type Étudiant | Lecture de ses inscriptions uniquement, par rattachement explicite |

Les six nouveaux droits sont initialement attribués au seul groupe Administrateur plateforme. Les droits de gestion exigent la consultation correspondante ; `payments.manage` et `eligibility.review` exigent `enrollments.view`. Les droits pédagogiques du lot 3 n’accordent pas implicitement de pouvoirs financiers.

Les écritures utilisent une transaction et le même ordre de verrouillage que les comptes et dossiers étudiants. Les droits sont relus après acquisition du verrou partagé ; solde, références, contexte et révision sont vérifiés dans la transaction. Le verrou global privilégie l’intégrité à ce stade ; un débit élevé nécessitera une étude de granularité.

Les contraintes uniques et clés étrangères protègent les références. Les révisions refusent les formulaires périmés. `enrollment_audits` conserve acteurs, actions, références, montants et motifs des corrections ; les valeurs du dossier d’identité et les coordonnées du compte ne sont pas recopiées. Les examens sont conservés dans `eligibility_reviews`. L’écran de piste d’audit et la consultation complète des examens historiques restent à reprendre.

L’accès personnel relit le rattachement à chaque requête et exige le type Étudiant. Un e-mail identique, un identifiant ajouté à l’URL ou un reçu appartenant à une autre inscription ne donne aucun accès. L’historique et les reçus ne contiennent ni clé de soumission déjà utilisée, ni identifiant de l’opérateur de caisse, ni secret de compte. Une nouvelle clé est fournie au formulaire de règlement du service habilité.

## Mettre à jour et tester sur Mac

À la racine du dépôt existant, après enregistrement des modifications locales :

```bash
git fetch origin
git switch --track origin/refactor/v2-enrollments
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà, remplacer la commande `switch` par `git switch refactor/v2-enrollments` puis `git pull --ff-only`. Conserver le même dossier et projet Compose que pour les lots précédents pour retrouver le volume de données. Les migrations ajoutent les nouvelles tables et permissions sans effacer les comptes ou dossiers. Ne pas supprimer le volume.

Ouvrir `http://localhost:8085` et se connecter avec le compte administrateur nominatif déjà créé. Pour une première installation, suivre le README.

Scénario avec des données fictives :

1. Créer une année couvrant la date du test, la passer « En cours », puis définir un tarif Master 2 de **100 000 FCFA**.
2. Créer ou retrouver un dossier de démonstration, Master 2, statut Normal, non archivé. Noter sa carte.
3. Créer son inscription. Vérifier le tarif affiché, puis enregistrer **40 000 FCFA** en espèces : le solde doit être **60 000 FCFA**.
4. Essayer **60 001 FCFA** : le paiement doit être refusé. Enregistrer **60 000 FCFA** par chèque avec un numéro de démonstration. Ouvrir et imprimer le reçu.
5. Déclarer un stage ayant commencé et une référence de justificatif fictive. Vérifier que les trois critères sont remplis et que le statut reste « À examiner ».
6. Enregistrer l’examen avec une observation : le statut devient « Éligible ». Corriger un règlement par annulation motivée : l’écriture reste visible, le solde augmente et un nouvel examen devient nécessaire.
7. Avec un compte étudiant explicitement rattaché au dossier, consulter **Mes inscriptions** et le reçu. Vérifier l’absence des formulaires de gestion et l’impossibilité de consulter un autre dossier.
8. Vérifier les tableaux et formulaires en largeur mobile, les erreurs, le clavier, le menu et l’aperçu d’impression.

`EnrollmentsTest` complète les tests des lots précédents : permissions, années et tarifs, montants, dates, doublons, répétition de soumission, corrections, révisions, trois critères, péremption des décisions, isolation des reçus, pagination et CSRF. Les tests applicatifs utilisent SQLite en mémoire. Le workflow Docker construit PHP/Node, applique les migrations sur MySQL, compile TypeScript/Vite et vérifie HTTP. Les essais visuels sur Mac et à deux connexions MySQL simultanées restent à réaliser.

## Suite du parcours

Le [lot 5](reports.md) utilise cet examen d’éligibilité pour le dépôt privé des mémoires PDF, leurs versions et leur approbation administrative. Son guide fournit la branche de mise à jour et le scénario de test suivant.
