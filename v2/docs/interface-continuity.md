# Continuité de l’interface Check Master

La v2 améliore l’application existante. La migration technique vers Laravel et React ne constitue pas un changement d’identité ou de parcours métier.

## Référence

Version historique : commit c143941cbf446a851dc7ae1f6d09466cc3b1fee2.

| Élément existant | Reprise dans la v2 |
| --- | --- |
| Monogramme CM de public/assets/images/logo_cm_sbg.png | Même fichier, servi localement dans public/images/logo-check-master.png |
| Logo MIAGE de public/assets/images/logo_mi_sbg.png | Même fichier, servi localement dans public/images/logo-miage.png |
| Bleu principal #1a5276, bleu clair #2980b9, bleu foncé #163d5a | Connexion, en-tête, menu latéral et actions |
| Orange #ff8c00 et vert #4caf50 | Accents des indicateurs |
| Police Poppins | Même famille ; repli sur la police système |
| Connexion : illustration bleue à gauche, formulaire à droite, « Bienvenue ! » | Même composition, mêmes repères et libellés principaux |
| Menu latéral bleu, marque empilée, entrée active blanche | Structure des espaces commission et administration conservée |
| En-tête bleu et logo MIAGE | Même hiérarchie avec accès au profil |
| Étudiants inscrits, rapports soumis, demandes de soutenance, enseignants | Quatre indicateurs dans le même ordre |
| Évolution des étudiants, répartition, suivi des soutenances | Sections et intitulés repris |
| Dernier rapport, rapports en attente, commission d’évaluation | Trois panneaux conservés |

Sources : public/pageConnexion.php, app/Views/index_commission.php, app/Views/index_personnel_administratif.php, app/Views/index_etudiant.php, app/Views/dashboard.php et app/Controllers/MenuController.php.

## Améliorations de ce lot

- Espacements, alignements et tailles de texte harmonisés à partir des éléments existants.
- Focus clavier visible, lien d’accès direct au contenu, labels et erreurs de formulaire associés.
- Affichage du mot de passe à la demande et aide à la récupération du compte.
- Menu repliable sur mobile avec état accessible ; Échap le ferme et rend le focus au bouton.
- Cartes et formulaires adaptés aux petites largeurs, sans masquer leurs informations.
- Logos historiques conservés sans transformation. Poppins utilise Google Fonts comme dans l’application d’origine.

## État après le lot 3

Le tableau de bord commun reprend pour l’instant la structure commission/administration. Les types, groupes et permissions de gestion des comptes sont branchés. Les autorisations des autres modules et leurs écrans propres à chaque rôle restent à reprendre.

Les effectifs étudiants sont alimentés par les dossiers non archivés, uniquement pour les personnes habilitées à les consulter. Le premier indicateur s’appelle « Étudiants enregistrés » pour ne pas confondre les fiches avec les inscriptions administratives à venir. Le panneau « Évolution des étudiants » conserve sa place et présente la répartition actuelle par niveau, explicitement sans historique annuel. Les autres indicateurs affichent un tiret et « Données indisponibles ». Aucun chiffre, courbe ou rapport n’est simulé.

Les entrées actives sont le tableau de bord, le profil avec changement de mot de passe et la déconnexion. Selon les droits, « Étudiants », « Mon dossier étudiant » et « Paramètres généraux » sont aussi disponibles. Les paramètres regroupent utilisateurs, groupes et promotions/niveaux. La liste des comptes reprend les trois totaux, la recherche, les filtres et les actions de gestion.

La liste des étudiants conserve ses quatre cartes, les filtres promotion/niveau/statut, l’action « Ajouter un étudiant » et les colonnes Étudiant, N° Carte, Niveau, Promotion, Statut, Actions. Seul le total est calculé ; les trois indicateurs liés aux décisions restent indisponibles. Les fiches s’ouvrent dans la même structure bleue, avec formulaires accessibles et tableau défilant sur petit écran. La suppression est remplacée par un archivage réversible ; export, actions en masse et inscription aux matières à rattraper restent à migrer. Voir `student-records.md` pour les correspondances.

« Mot de passe oublié ? » ouvre une aide invitant à contacter l’administration. Le parcours de réinitialisation, les notifications et l’accueil public seront repris dans leurs lots respectifs.

## Règle pour chaque prochain écran

1. Lire la vue historique et son menu, puis identifier les actions, données et droits associés.
2. Préserver ses repères visuels, ses libellés métier et la place des actions principales.
3. Améliorer la hiérarchie, la lisibilité, l’accessibilité et le comportement mobile.
4. Vérifier le fonctionnement avec les vrais droits et données avant d’activer les commandes.
5. Documenter les différences restantes avec la version historique.

## Lot 4 — scolarité

Les écrans d’inscriptions reprennent les quatre cartes Total inscriptions, Partiellement payé, Tout payé et Rien payé ainsi que les colonnes carte/étudiant, niveau, référence, montants, date, statut et actions. Les compteurs sont explicitement limités à l’année sélectionnée et excluent les annulations. Le numéro de reçu appartient à chaque règlement, accessible depuis l’historique de l’inscription. Les critères de scolarité, Master 2 et stage sont affichés avec leur résultat calculé. Années et tarifs restent dans les paramètres généraux. Les styles d’impression masquent la navigation uniquement dans le reçu imprimé.

## État après le lot 5

Les inscriptions, règlements et critères d’éligibilité du lot 4 sont disponibles. Le lot 5 reprend les trois cartes de `app/Views/rapports.php` : Création du rapport, Statut du dépôt et Commentaires. Le dépôt PDF, le statut et l’historique des versions prolongent ces cartes, avec les composants de formulaire et les couleurs existants. La liste administrative utilise les mêmes cartes de totaux, filtres, tableaux et boutons que les autres modules.

Les indicateurs « Rapports soumis », « Rapports en attente » et le panneau « Dernier rapport » utilisent maintenant les données réelles, selon les permissions. L’étudiant voit uniquement son dernier mémoire. Les soutenances et commissions restent indisponibles. L’approbation administrative est explicitement distinguée de la future validation par la commission.

La comparaison visuelle sur Mac, notamment sur mobile, reste à effectuer avec le [scénario du lot 5](reports.md).

## Correction du parcours stage et mémoire — 22 octobre 2026

Les trois cartes de l’étudiant, les couleurs et les composants sont conservés. Le stage est désormais déclaré depuis l’espace étudiant. Le dépôt s’ouvre automatiquement lorsque les critères sont remplis. Le suivi indique « À transmettre », « En commission », « Correction demandée » ou « Accepté à l’unanimité ». Le chargé de communication voit la liste complète des destinataires et le bouton de transmission ; chaque membre reçoit un formulaire d’avis personnel et un suivi des approbations. Les compteurs et les listes de la commission sont limités aux versions transmises. L’ancienne approbation administrative ne donne plus accès à une acceptation.

## Lot demandé — Réunions et messagerie

Les vues historiques de réunions et de messagerie restent la référence : menu bleu, mêmes logos et police Poppins, cartes de statut, filtres et tableaux. Les réunions disposent d'une liste et d'un calendrier mensuel ; la messagerie reprend les dossiers Réception, Non lus, Envoyés et Archives, complétés par Brouillons et Corbeille. Les droits déterminent les actions visibles. Les trois prochaines réunions accessibles alimentent le panneau de commission existant du tableau de bord.

Les confirmations sont personnelles et les formulaires signalent les changements concurrents. L'ouverture d'un message conserve son état jusqu'à l'action explicite « Marquer comme lu ». L'annulation d'une réunion, le retrait d'un document et la corbeille conservent les historiques. La comparaison visuelle dans Safari/Chrome sur Mac et mobile reste à effectuer avec le [scénario du lot](meetings-messaging.md).


## Ressources humaines

La vue `app/Views/ressources_humaines.php` fournit les repères des onglets Enseignants et Personnel administratif. Les fiches, filtres, cartes et tableaux prolongent les composants existants. Les catalogues, l’historique daté des grades/fonctions, les spécialités multiples, les photos privées et le rattachement de compte se consultent dans le même cadre. L’archivage réversible conserve les fiches. Le quatrième indicateur du tableau de bord affiche désormais le nombre réel d’enseignants non archivés pour les personnes habilitées.

La consultation personnelle utilise « Ma fiche professionnelle ». Les actions restent déterminées par les droits serveur. Le [guide RH](human-resources.md) décrit les différences avec les tables historiques, les limites et la recette visuelle sur Mac.

## Soutenances et notes

Les écrans d’évaluations et de soutenances reprennent la structure historique : quatre cartes, filtres, tableaux et formulaires dans la navigation existante. Poppins, logos et palette restent identiques. Les améliorations ajoutent les états explicites (brouillon, publié, correction, jury), des moyennes justifiables et des versions conservées. Aucun résultat fictif n’est affiché dans le tableau de bord. Les bulletins et convocations disposent d’une feuille de style d’impression sans menus ni actions de gestion.


## Tableaux de bord par groupe

Le tableau de bord conserve la structure Check Master : titre d’accueil, cartes d’indicateurs, panneaux et profil. Les neuf vues adaptent les données, les accès rapides et l’ordre des tâches au métier du groupe. Les blocs non autorisés sont absents. Les effectifs et répartitions réels sont présentés en barres lisibles ; aucun graphique temporel n’est inventé. Un sélecteur d’espaces permet aux comptes multigroupes de garder des vues distinctes. Les critères de dépôt de l’étudiant restent visibles et les listes ouvrent les écrans métier déjà livrés.
