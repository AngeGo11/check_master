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

## Limites explicites du lot 1

Le tableau de bord commun reprend pour l’instant la structure commission/administration. Les rôles et les autorisations métier ne sont pas encore branchés. Cette structure ne remplace pas la future reprise des écrans propres à chaque rôle.

Les indicateurs ne disposent d’aucune donnée historique : ils affichent un tiret et « Données indisponibles ». Les graphiques et listes indiquent l’ouverture future des services. Aucun chiffre, courbe ou rapport n’est simulé.

Les seules entrées actives sont le tableau de bord, le profil en lecture et la déconnexion. Les autres menus seront repris avec leurs modules et leurs permissions ; leur ordre et leur regroupement suivront le MenuController historique.

« Mot de passe oublié ? » ouvre une aide invitant à contacter l’administration. Le parcours de réinitialisation, les notifications et l’accueil public seront repris dans leurs lots respectifs.

## Règle pour chaque prochain écran

1. Lire la vue historique et son menu, puis identifier les actions, données et droits associés.
2. Préserver ses repères visuels, ses libellés métier et la place des actions principales.
3. Améliorer la hiérarchie, la lisibilité, l’accessibilité et le comportement mobile.
4. Vérifier le fonctionnement avec les vrais droits et données avant d’activer les commandes.
5. Documenter les différences restantes avec la version historique.
