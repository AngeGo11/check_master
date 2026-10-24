# Tableaux de bord par groupe utilisateur

Branche : `refactor/v2-role-dashboards`, basée sur le lot soutenances et notes. La navigation, les logos, la police Poppins, la palette bleue, les cartes et les panneaux Check Master sont conservés. Le contenu, les actions et l’ordre des priorités changent selon le groupe.

## Vues disponibles

| Groupe | Indicateurs et priorités |
| --- | --- |
| Étudiant | Solde de son inscription courante, stage déclaré, critères réels du dépôt, état de son mémoire, bulletins publiés, candidature et convocation de soutenance, messages personnels |
| Administrateur plateforme | Comptes actifs et suivi des accès, répartition réelle par groupe, dossiers étudiants par niveau, enseignants, mémoires et plannings accessibles |
| Responsable scolarité | Inscriptions de l’année en cours, scolarités à régulariser, bulletins en brouillon/correction, demandes de soutenance et planning |
| Chargé de communication | Mémoires à transmettre en priorité, dossiers en commission, corrections et acceptations, derniers dépôts |
| Commission de validation | Avis personnels restant à rendre sur la transmission actuelle, mémoires reçus, invitations à confirmer et réunions, éventuelles présidences de jury |
| Secrétaire | Derniers dossiers étudiants, dossiers sans compte rattaché à faire vérifier, effectifs et messagerie personnelle |
| Enseignant | Préparation des prochains jurys, fiche professionnelle, résultats à consigner s’il préside |
| Responsable de niveau | Décisions de jury à finaliser en priorité, séances affectées et fiche professionnelle |
| Responsable de filière | Bilan des résultats de ses jurys, décisions attendues et soutenances affectées |

Les vues de niveau et de filière restent limitées aux affectations de jury existantes. Le modèle v2 ne rattache pas encore un responsable à un niveau ou à une filière déterminés : aucune population étudiante globale ne lui est attribuée par supposition. Le tableau de bord n’ajoute pas de droits et ne crée pas d’affectations.

## Comptes avec plusieurs groupes

Un sélecteur « Mes espaces » présente uniquement les groupes du compte compatibles avec ses types utilisateur. Le choix est conservé dans l’URL, par exemple `/dashboard?space=student`. Le premier espace est choisi selon l’ordre : administration, scolarité, communication, commission, filière, niveau, enseignant, secrétariat, étudiant.

Changer de vue adapte la synthèse affichée. Cela ne modifie ni les groupes ni les permissions du compte. Les modules de destination gardent leurs contrôles d’accès habituels. Un administrateur également étudiant peut consulter son parcours étudiant sans y recevoir les agrégats d’administration ; sa vue commission reste limitée à ses transmissions et sa vue jury à ses affectations.

Un paramètre d’URL ne permet jamais de sélectionner un groupe absent ou incompatible : le serveur refuse avec 403. Sans groupe compatible, un accueil personnel propose le profil et les services réellement autorisés.

## Données et calculs

- Chaque bloc et chaque action sont conditionnés par les permissions et règles métier existantes, côté serveur. Les blocs sans droit ne sont pas envoyés au navigateur.
- Aucune donnée fictive, aucun graphique de tendance sans historique, aucun panneau générique « indisponible » pour un module non autorisé.
- Effectifs étudiants : dossiers non archivés ; aucune inscription supplémentaire ne multiplie le décompte.
- Répartition des comptes : comptes actifs, types compatibles, un compte au maximum par groupe. Un compte appartenant à plusieurs groupes apparaît dans chacun ; cette répartition ne doit pas être additionnée comme un total unique.
- Scolarité : année courante et inscriptions non annulées ; règlements annulés exclus du payé. Les dossiers archivés sont exclus des tâches de recouvrement et de préparation des bulletins.
- Étudiant : rattachement explicite par identifiant de compte, jamais par adresse e-mail. Une inscription manquante et un solde nul ont des affichages distincts. Les critères du dépôt sont ceux du service Eligibility ; aucun bulletin ni visa administratif supplémentaire n’est exigé.
- Notes : seulement la dernière publication de chaque bulletin. Une correction non publiée reste invisible à l’étudiant.
- Commission : seuls les mémoires dont la version actuelle a été transmise au membre sont montrés. « Mes avis à rendre » vérifie la dernière transmission ouverte, l’absence d’avis personnel et l’absence de conflit avec le déposant ; un avis d’une ancienne transmission ne dispense pas de voter de nouveau.
- Jury : affectations actuelles seulement ; résultats attendus exclusivement pour les séances terminées que le compte préside. Les séances annulées ou ayant déjà un résultat ne sont pas comptées.
- Réunions : invitations et réunions accessibles, non annulées ; les invitations à confirmer sont personnelles, futures et encore sans réponse.
- Messages : uniquement les messages reçus non lus de la boîte personnelle, hors corbeille/archives/brouillons. L’ouverture du tableau de bord ne marque pas les messages comme lus.
- Listes de dossiers et agendas limitées à cinq entrées, compteurs calculés sur tout le périmètre. Les liens ouvrent le module complet.
- Les réponses portent `Cache-Control: private, no-store`. Aucun modèle brut, document, mot de passe, chemin de stockage ou contenu de message n’est transmis dans les nouveaux blocs.

## Recette sur Mac

Depuis la racine du dépôt, après avoir enregistré les modifications locales et ouvert Docker Desktop :

```bash
git fetch origin
git switch --track origin/refactor/v2-role-dashboards
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà, utiliser `git switch refactor/v2-role-dashboards` puis `git pull --ff-only`. Conserver le même dossier/projet Docker et le volume de données. Ouvrir http://localhost:8085.

1. Se connecter successivement avec les neuf groupes de test : vérifier l’intitulé, les indicateurs, les accès rapides et les priorités.
2. Avec un étudiant rattaché, vérifier le solde, les critères de stage/dépôt, les publications et sa convocation. Avec un autre étudiant, vérifier qu’aucun de ces dossiers n’apparaît.
3. Publier un bulletin puis préparer une correction : le tableau étudiant doit conserver la publication précédente.
4. Avec la commission, rendre un avis : « Mes avis à rendre » diminue, même si les autres membres n’ont pas encore voté.
5. Avec un enseignant président, attendre la fin du créneau : le dossier apparaît dans les résultats à consigner. Un examinateur ne reçoit pas cette tâche.
6. Avec un compte multigroupe, changer de vue et recharger la page ; essayer une vue non attribuée pour vérifier le refus.
7. Retirer un droit ou une affectation de jury puis recharger : le bloc ou le dossier correspondant disparaît.
8. Vérifier les écrans vides, l’affichage mobile, le clavier et les liens d’accès rapide.

Les tests automatisés couvrent ces périmètres et les régressions des lots précédents. La recette visuelle réelle sur Mac reste à effectuer par l’utilisateur. Aucune migration ni extension des permissions n’est nécessaire pour ce lot.
