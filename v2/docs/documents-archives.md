# Documents, comptes rendus et archives personnelles

Branche : `refactor/v2-documents`, issue du parcours annuel. Ce lot prolonge les vues historiques de rédaction et d’archivage dans la navigation, les cartes, les tableaux et la palette existants.

## Parcours livrés

- L’étudiant prépare un document dans l’éditeur structuré (titres, paragraphes, listes, citations) ou importe un PDF de 20 Mo maximum. La rédaction est disponible avant l’éligibilité. Chaque enregistrement conserve une nouvelle version et un PDF privé, avec auteur, date et note de version.
- Depuis une version, l’étudiant éligible peut déposer ce PDF dans le parcours du mémoire. Le paiement intégral, le niveau Master 2 et la déclaration du stage ouvrent immédiatement le dépôt. Une date future de stage ne le bloque pas. Le dépôt reprend les contrôles et l’historique existants ; il ne transmet ni n’approuve le rapport.
- Un membre de commission habilité rédige un compte rendu des commissions terminées auxquelles il a effectivement participé. Il choisit un à dix rapports distincts, saisis explicitement, avec une synthèse par rapport. Il ne peut pas rédiger de compte rendu sur son propre dossier étudiant.
- Le brouillon reste privé à son auteur. La publication explicite crée une nouvelle version PDF ; le brouillon et les publications précédentes restent conservés. Une correction crée d’abord un nouveau brouillon, sans remplacer la publication visible.
- Les services habilités consultent les comptes rendus publiés. L’étudiant consulte et télécharge uniquement son extrait individuel : ni notes communes, ni titre interne, ni autres dossiers du compte rendu ne sont transmis à son interface ou à son PDF.
- « Mes archives » conserve des références personnelles vers des versions accessibles de documents, de rapports déposés ou d’extraits. Retirer/restaurer une archive ne supprime jamais le document. Les droits de la source sont revérifiés à chaque lecture et téléchargement, même après archivage.

## Droits par groupe

| Groupe | Accès par défaut |
| --- | --- |
| Étudiant (1) | Documents personnels, dépôt si éligible, extraits publiés de son rapport, archives personnelles |
| Chargé de communication (2) | Comptes rendus publiés, rapports accessibles, archives personnelles |
| Scolarité (3) | Comptes rendus publiés, rapports accessibles, archives personnelles |
| Responsable filière (4), enseignant (5), directeur (6), personnel administratif (7) | Archives personnelles ; seules les sources déjà autorisées peuvent y être ajoutées |
| Administrateur (8) | Comptes rendus publiés, rapports accessibles, audit des opérations ; aucun accès aux brouillons privés des étudiants |
| Commission (9) | Rédaction/publication de ses comptes rendus, consultation selon les rapports effectivement attribués, archives personnelles |

Permissions ajoutées : `documents.self` exige `reports.self`, `documents.minutes` exige `reports.assigned`, `documents.view` exige `reports.view`, `documents.archive` conserve le périmètre des sources. Le catalogue comporte 52 permissions et 100 attributions initiales. Le type compatible et le groupe réel restent nécessaires. Les changements de droits sont pris en compte par les routes, pas seulement par le menu. Les tableaux de bord présentent les accès et compteurs correspondants à l’espace sélectionné.

## Données et PDF

Le SQL fourni distingue les documents de réunion, les comptes rendus et les archives. Les documents de réunion restent gérés par le lot Réunions. Pour éviter les jointures historiques qui associaient des étudiants par déduction, `work_document_entries` lie explicitement une version de compte rendu à la version du rapport et au tour de commission examiné.

`work_documents` porte le propriétaire et la révision ; `work_document_versions` conserve le contenu et le PDF ; `personal_archives` porte le signet propre à chaque utilisateur ; `document_events` apporte la neuvième source de la piste d’audit. Les textes des documents, chemins privés et synthèses ne figurent pas dans l’audit.

Les écritures utilisent le verrou partagé des modules existants, une clé d’opération contre les doubles envois et une révision contre les formulaires périmés. Les sources de commission sont revérifiées avant enregistrement/publication. Une erreur après écriture du fichier annule les métadonnées et retire uniquement le nouveau fichier non référencé.

Les PDF sont stockés dans `storage/app/private/documents`, sans URL publique. Taille et SHA-256 sont vérifiés au téléchargement et au dépôt. Le rendu utilise Dompdf verrouillé par Composer, un modèle Blade fixe et des textes échappés. Aucun HTML utilisateur n’est exécuté ; les ressources distantes, PHP et JavaScript sont désactivés dans le moteur PDF. Les téléchargements demandent une session et interdisent la mise en cache. Les extraits sont générés avec le seul contenu individuel autorisé.

Sauvegarder ensemble la base et les fichiers privés de tous les modules. Le cache `document-runtime` est régénérable. La migration ne reprend aucune donnée personnelle historique et refuse une suppression descendante destructive ; un retour arrière demande une restauration cohérente.

## Installer la branche sur Mac

Depuis le même dossier de dépôt que les lots précédents, après avoir enregistré les modifications locales :

```bash
git fetch origin
git switch refactor/v2-documents
git pull --ff-only
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si Git ne trouve pas encore la branche locale, utiliser `git switch --track origin/refactor/v2-documents`. Ouvrir http://localhost:8085 avec les comptes de test existants. Le script conserve la base, les fichiers et les secrets ; ne pas supprimer les volumes Docker. Il installe la dépendance PDF, compile l’interface et applique la migration additive.

## Recette fonctionnelle

1. **Étudiant non éligible** : ouvrir Documents, rédiger deux sections, enregistrer puis corriger. Vérifier les deux versions et leurs PDF. Le dépôt reste fermé, la rédaction est utilisable. Importer aussi un PDF ; un faux PDF doit être refusé.
2. **Étudiant éligible** : avec une inscription M2 intégralement payée et un stage déclaré, ouvrir une version et choisir Déposer ce PDF comme mémoire. Vérifier le PDF dans Mon mémoire. Répéter l’envoi ne doit pas créer de doublon.
3. **Chargé de communication** : transmettre normalement ce mémoire à la commission. Chaque membre doit approuver pour obtenir l’acceptation. La rédaction d’un compte rendu n’accorde aucun avis à sa place.
4. **Commission** : après clôture d’une commission, ouvrir Documents puis Rédiger un compte rendu. Sélectionner le rapport, saisir des notes communes et une synthèse individuelle, enregistrer le brouillon puis publier avec une référence. Si plusieurs rapports sont sélectionnés, remplir une synthèse distincte pour chacun.
5. **Confidentialité** : avec deux comptes étudiants différents, vérifier que chacun ne voit que son extrait. Un lien direct vers le compte rendu complet ou l’extrait de l’autre étudiant est refusé. Les services voient la publication, jamais le brouillon de correction suivant.
6. **Archives** : archiver une version, la retirer, filtrer les archives retirées et la restaurer. La version source doit rester téléchargeable. Après retrait de la permission de consultation ou d’une attribution, l’archive ne rend pas la source accessible.
7. **Concurrence** : ouvrir deux formulaires du même document ; enregistrer le premier puis envoyer le second. Une erreur de révision doit demander de recharger, sans écraser la version.
8. **Mac/mobile** : dans Safari ou Chrome, vérifier menu, éditeur, sélecteur de rapports, tableaux, pagination et téléchargement. La page reprend les couleurs et logos existants. Le retour sans enregistrer n’est pas une sauvegarde automatique.

`scripts/check.sh` exécute les tests métier, les requêtes réelles MySQL de versions/sources/extraits/archives et la disponibilité HTTP. Les tests transactionnels utilisent SQLite en mémoire. Le workflow Docker produit également trois PDF entièrement fictifs (document long, brouillon et extrait) dans l’artefact `document-pdf-samples` pour contrôler les accents, marges et sauts de page.

## Limites explicites

L’éditeur est structuré, avec sauvegarde explicite : ce n’est pas un traitement de texte DOCX ni une édition collaborative en direct. Il ne dépend d’aucun serveur OnlyOffice public. Un PDF importé reste un fichier ; ses pages ne sont pas converties en texte éditable. La mise en forme PDF utilise une police incorporée adaptée aux accents, tandis que l’application conserve Poppins. La reprise et le rapprochement des anciennes archives, comptes rendus, comptes et fichiers restent le prochain lot ; aucun import automatique incertain n’est effectué ici.
