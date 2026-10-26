# Validation annuelle et parcours à cheval

Le parcours complète les bulletins, les inscriptions et la piste d’audit. L’interface conserve la structure, la palette, la typographie et les composants des lots précédents.

## Décisions et origine des règles

Le code historique contient une classification automatique fondée notamment sur 48 crédits, mais compte des crédits inscrits avant de tester la validation. Il utilise aussi des dénominateurs fixes 30/60 et une formule M2 dépendant d’une table absente du SQL fourni. Ces calculs ne constituent pas un règlement pédagogique fiable.

Ce lot fournit donc un **bilan des publications et une décision explicite du jury**, consignée par la scolarité avec référence de procès-verbal et motif. Aucun seuil d’admission, compensation automatique ou diplôme supplémentaire n’est inventé.

| Décision | Réinscription proposée |
| --- | --- |
| Année validée | Niveau immédiatement supérieur ; aucune matière antérieure encore due |
| Passage à cheval autorisé | Niveau immédiatement supérieur ; matières choisies dans les bulletins publiés et matières antérieures restant dues |
| Ajourné / redoublement | Même niveau ; les matières antérieures encore dues restent suivies |

La sélection du jury détermine les matières à reprendre. Les notes inférieures à 10 ne déclenchent pas automatiquement un statut. Une UE sans ECUE se reprend comme une matière ; une UE composée se reprend par ECUE sélectionné, sans facturer en plus son parent UE. Le redoublement couvre les enseignements du niveau par la scolarité de base ; il ne crée pas une deuxième facturation de chacune de ses matières.

Les moyennes utilisent les notes et crédits des publications immuables, sans repartir des moyennes semestrielles arrondies. Les crédits affichés « UE ≥ 10 » sont un indicateur de résultat, pas une décision de passage. Aucun brouillon de notes n’est divulgué. Un semestre connu non publié, ou un bulletin rouvert pour correction, bloque la nouvelle décision. Une correction ou un résultat de rattrapage intervenu après une décision impose une version actualisée avant réinscription.

**M2 :** le catalogue fourni contient S9, sans S10. Le bilan reste indiqué comme partiel, avec moyenne annuelle indisponible. Une décision explicite sur ce périmètre exige la confirmation du caractère partiel et un procès-verbal justifiant la décision. Cette confirmation ne peut pas contourner un bulletin manquant dans un semestre connu. Une validation M2 ne crée ni niveau 6 ni diplôme.

## Rôles

| Droit | Attribution initiale |
| --- | --- |
| `progression.view` | Scolarité et administrateur plateforme |
| `progression.manage` | Scolarité ; attribuable aussi à l’administrateur |
| `progression.self` | Étudiant, avec `grades.self` et rattachement explicite à son dossier |
| `progression.fees` | Administrateur plateforme |

La réinscription exige également `enrollments.manage`. L’administrateur consulte les parcours et configure les tarifs ; il ne consigne pas les décisions par défaut. Les responsables de niveau/filière et enseignants ne reçoivent pas une vue globale des résultats du seul fait de leur titre, faute d’affectations de périmètre dans ce schéma. Les contrôles exigent un groupe compatible et sont revérifiés dans la transaction. Un compte multigroupe ne peut pas délibérer sur son propre dossier.

L’étudiant retrouve ses décisions, motifs, versions et résultats de rattrapage dans **Mon parcours annuel**. La scolarité et l’administrateur disposent de **Parcours annuels**, avec recherche, année et décision. Le tableau de bord, les paramètres et les fiches d’inscription donnent accès au module selon les droits.

## Frais de réinscription

Le document historique `SYSTEME_ETUDIANTS_CHEVAL.md` donne la formule **base + supplément × nombre de matières**. Une autre version de l’ancien contrôleur suppose des prix ECUE et un forfait de secours de 25 000 FCFA, mais la colonne de prix et les tables de rattrapage sont absentes du SQL fourni. Aucun de ces montants historiques n’est importé automatiquement.

- La **base** provient du tarif v2 déjà configuré pour le niveau d’accueil et la nouvelle année.
- Le **supplément unitaire** est configuré explicitement dans Paramètres → Tarifs de rattrapage, par année et niveau d’accueil.
- Sans supplément configuré, une réinscription avec matières à reprendre est bloquée. Un tarif de zéro est possible, mais doit être enregistré explicitement.
- Le montant, les révisions tarifaires et le supplément de chaque matière sont figés à la création. Un tarif modifié ou une matière validée ultérieurement ne réécrit pas le montant initial.
- Le calcul est serveur. Un devis périmé est rejeté ; le montant envoyé par un client ne remplace jamais ce calcul.
- Les paiements passent par le registre existant, avec reçus et annulations. La réinscription n’enregistre aucun paiement automatique.

## Parcours et historique

1. Publier les bulletins de l’année dans Évaluations des étudiants.
2. Ouvrir le parcours de l’inscription, examiner les résultats et consigner la décision, le PV et les éventuelles matières à reprendre.
3. Configurer la nouvelle année et ses tarifs, puis la passer en cours. Son début doit suivre la fin de l’année d’origine.
4. Revenir à la dernière décision du dernier parcours et confirmer le devis et la date. L’inscription, le rattachement des matières et le niveau/statut étudiant changent dans une seule transaction.
5. Consigner progressivement les résultats des matières dans le parcours de la nouvelle inscription : en cours, validée, à reprendre, avec note facultative, référence et motif.
6. Les matières non validées sont reprises à l’année suivante sans dupliquer leur origine. Les matières validées restent dans leur historique et ne sont plus facturées comme matières dues.

Les décisions sont versionnées. Une correction avant réinscription conserve les versions précédentes. Une décision ayant déjà produit une inscription est figée pour préserver la cohérence financière et pédagogique : la gestion d’une annulation complète en cascade n’est pas proposée. Une inscription annulée reste restaurable selon les contrôles existants ; elle n’est jamais recréée en doublon.

Les dossiers avec une décision annuelle ne peuvent plus changer de niveau/statut dans l’éditeur générique ni contourner le parcours par une inscription ordinaire. Les comptes et dossiers historiques « À cheval » sans bulletins v2 ni décision source nécessitent toujours une reprise de données contrôlée ; ce lot ne fabrique pas leurs résultats.

Le stage est toujours déclaré par l’étudiant. Pour une inscription à cheval créée par ce parcours, le dépôt de mémoire reste immédiatement disponible lorsque les **trois critères** sont remplis : scolarité complète (suppléments compris), M2, stage déclaré. Aucun avis annuel ou résultat de rattrapage supplémentaire ne conditionne ce dépôt. La transmission et l’approbation unanime de la commission restent le parcours des mémoires.

## Données et traçabilité

Migration additive : `annual_decisions`, `retake_fees`, `retake_subjects`, `enrollment_retake`, `retake_events`, et rattachement de l’inscription à sa décision source. Les publications sources, origines des matières, suppléments appliqués et événements sont conservés. Les contraintes uniques, révisions, empreintes de bilan/devis et clés d’opération protègent les doubles clics et modifications périmées. Les écritures utilisent le verrou transactionnel commun aux permissions et autres modules.

Les actions annuelles, réinscriptions, tarifs et résultats sont intégrés au journal académique de la piste d’audit, avec identifiants de référence. L’audit général ne reçoit pas les notes ou motifs privés. La suppression des historiques et le retour arrière destructif de cette migration ne sont pas proposés.

## Recette sur Mac

Depuis le dépôt existant, après avoir sauvegardé toute modification locale :

```bash
git fetch origin
git switch --track origin/refactor/v2-annual-progression
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe, utiliser `git switch refactor/v2-annual-progression` puis `git pull --ff-only`. Les volumes et historiques existants sont conservés par le script d’installation.

Sur **http://localhost:8085**, utiliser des dossiers et comptes fictifs :

1. Administrateur : configurer les programmes et tarifs ; vérifier l’accès en lecture au parcours et les tarifs de rattrapage.
2. Scolarité : publier les deux bulletins d’un niveau L1 à M1, examiner le bilan et publier une décision à cheval avec une matière et une référence de PV.
3. Étudiant : vérifier la décision et le motif dans son espace, sans possibilité de modifier le résultat.
4. Nouvelle année en cours : vérifier un devis avec scolarité 200 000 FCFA et supplément 25 000 FCFA ; une matière donne 225 000 FCFA. Ce sont des valeurs de test à configurer, pas des tarifs de l’établissement.
5. Confirmer une seule réinscription. Vérifier le niveau/statut, la matière, puis saisir le règlement dans la fiche d’inscription. Rejouer une requête ne doit pas produire de doublon.
6. Consigner un résultat de rattrapage, puis une correction motivée. Vérifier les deux événements, le montant initial conservé et le bulletin d’origine inchangé.
7. Vérifier les refus avec un autre étudiant, un enseignant non habilité, une permission retirée, un bulletin rouvert, un devis périmé et une décision remplacée.
8. Sur M2, vérifier le signalement du périmètre partiel et l’exigence de confirmation/PV ; aucun S10 ni diplôme automatique.
9. Tester les vues sur écran étroit, au clavier, et les historiques paginés.

La suite automatisée vérifie ces parcours avec des données fictives ; la recette visuelle réelle sur Mac reste à réaliser.
