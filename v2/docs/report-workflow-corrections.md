# Sprint correctif — stage étudiant et validation unanime du mémoire

Ce sprint corrige les règles des lots 4 et 5 selon la demande du 22 octobre 2026. Il ne démarre aucun autre module. La déclaration du stage appartient à l’étudiant, l’éligibilité au dépôt est automatique, le chargé de communication transmet le document et chaque membre de la commission doit l’approuver.

## Référence de la base

Structure et catalogues examinés dans le fichier joint `check_master_db (8).sql`, empreinte SHA-256 `921bf93160eea570c2109d0852d1c5063883c4dbf70c0a8cb3b0ae4276c66454`. Le fichier joint reste inchangé et n’est pas ajouté au dépôt. Aucun compte, mot de passe, dossier ou commentaire réel n’est importé.

Les groupes, types compatibles et menus viennent de `groupe_utilisateur`, `type_utilisateur`, `type_a_groupe`, `traitement` et `rattacher`. Les parcours s’appuient sur `faire_stage`, `deposer`, `rapport_etudiant`, `partage_rapport` et `valider`. La demande métier actuelle remplace l’ancienne approbation individuelle de `approuver`.

Le catalogue `rattacher` décrit des menus, pas des droits détaillés d’écriture ou un périmètre de données. La v2 traduit les fonctions demandées en permissions serveur explicites ; elle ne déduit jamais une appartenance à un groupe du seul type d’utilisateur. Les libellés de `niveau_acces_donnees` ne sont pas utilisés comme des autorisations implicites.

## Interfaces par groupe dans ce sprint

Tous les comptes actifs ont leur profil et leur changement de mot de passe. Les modules disponibles s’ajoutent selon leurs permissions ; les autres menus historiques ne sont pas présentés comme opérationnels.

| Groupe historique | Type compatible | Interface et actions disponibles |
| --- | --- | --- |
| 1 — Étudiant | Étudiant | Son dossier, ses inscriptions et reçus, déclaration/modification/retrait de son stage, dépôt du rapport, versions, suivi des transmissions et avis |
| 2 — Chargé de communication | Personnel administratif | Consultation des étudiants, liste des rapports déposés, transmission à toute la commission, suivi des avis ; aucun vote au titre de ce groupe |
| 3 — Responsable scolarité | Personnel administratif | Dossiers étudiants, années et tarifs, inscriptions et règlements ; le stage reste déclaré par l’étudiant |
| 4 — Secrétaire | Personnel administratif | Consultation des dossiers étudiants ; aucune transmission ni décision de commission |
| 5 — Enseignant sans responsabilité administrative | Enseignant simple | Profil ; aucun accès aux rapports sans appartenance explicite à la commission |
| 6 — Responsable niveau | Enseignant administratif | Profil ; aucun droit de vote déduit de cette fonction |
| 7 — Responsable filière | Enseignant administratif | Profil ; aucun droit de vote déduit de cette fonction |
| 8 — Administrateur plateforme | Enseignant administratif | Gestion des comptes, groupes et paramètres, consultation des dossiers et du suivi des rapports ; aucun remplacement du chargé de communication ou des votes |
| 9 — Commission de validation | Enseignant simple ou administratif | Rapports dont la version courante lui a été transmise, téléchargements autorisés, son propre avis et suivi des avis de la commission |

Les fonctions historiques de messagerie, réunions, notes, comptes rendus, archives, ressources humaines et soutenances ne sont pas développées dans ce sprint. Le responsable scolarité reçoit les droits des modules de scolarité existants ; le chargé de communication et le secrétaire reçoivent la consultation des étudiants correspondant à leur menu historique. Les droits déjà délégués sur ces modules sont conservés, sauf l’ancien droit `reports.review`, supprimé.

Les nouvelles permissions sont `internships.self` (groupe 1), `reports.forward` (groupe 2), `reports.assigned` et `reports.vote` (groupe 9). Leur affectation à un autre groupe est refusée. `reports.forward` exige `reports.view`, `reports.vote` exige `reports.assigned`, et `internships.self` exige `students.self`. Les actions relisent également le groupe métier et le type compatible côté serveur. Une personne cumulant plusieurs fonctions ne reçoit les droits correspondants qu’avec les appartenances explicites requises.

## Stage et éligibilité immédiate

L’étudiant déclare son stage dans **Mes inscriptions et stage**, sur son inscription active. Il saisit l’entreprise, l’intitulé et les dates ; la référence de convention est facultative et n’est pas présentée comme un justificatif vérifié par le service. Le compte enregistrant la déclaration est l’étudiant lui-même. Les anciennes déclarations et leur auteur historique sont conservés.

Les gestionnaires peuvent consulter la déclaration, mais les anciens points d’entrée d’écriture administrative sont retirés. Les URL personnelles vérifient le rattachement explicite : un identifiant appartenant à un autre étudiant, une égalité d’e-mails ou des champs de propriétaire ajoutés au formulaire ne donnent aucun droit d’écriture. Le retrait d’une déclaration exige un motif et conserve une trace d’audit.

Les trois critères de dépôt sont :

1. La scolarité de l’inscription est intégralement réglée.
2. L’inscription et le dossier correspondent au niveau Master 2.
3. Un stage est déclaré pour cette inscription.

Un stage déclaré peut avoir une date de début future : sa déclaration est le critère, sans délai supplémentaire. Le contexte doit toujours être valide : dossier non archivé, inscription non annulée, année en cours et parcours pris en charge. Le parcours À cheval conserve sa limite du lot 4.

Dès que les critères sont remplis, l’étudiant peut déposer son PDF. Aucun examen administratif ni confirmation manuelle n’est demandé. Les observations administratives existantes restent dans l’historique et n’influencent pas cette autorisation. Une modification de coordonnées ou une ancienne observation périmée ne bloque donc plus un étudiant dont les critères sont réunis.

Les critères sont relus sous verrou lors de chaque dépôt, y compris d’une correction, et un instantané est attaché à la version. Un paiement annulé ou un stage retiré empêche un nouveau dépôt jusqu’à régularisation. Les votes portent sur la version transmise ; ils ne réintroduisent pas une approbation administrative de l’éligibilité.

## Transmission et unanimité

| État | Action | État suivant |
| --- | --- | --- |
| Aucun rapport / correction demandée | Dépôt par l’étudiant éligible | À transmettre |
| À transmettre | Transmission par le chargé de communication | En commission |
| En commission | Un membre approuve ; d’autres avis sont attendus | En commission |
| En commission | Tous les membres approuvent | Accepté à l’unanimité |
| En commission | Un membre demande une correction avec commentaire | Correction demandée ; transmission clôturée |
| Correction demandée | Nouveau PDF, puis nouvelle transmission | Nouveaux avis attendus de tous les membres |

La transmission se fait dans l’application. Aucun message ni e-mail externe n’est envoyé automatiquement. Une commission vide bloque la transmission. Le formulaire affiche **tous** les membres actifs et de type compatible du groupe 9 ; il ne permet pas de sélectionner un sous-ensemble. Un compte avec mot de passe temporaire reste dans cette liste et doit personnaliser son mot de passe avant de voter.

Chaque transmission fige la version et la liste nominative des membres attendus. Chaque membre dispose d’un seul avis personnel sur cette transmission. Deux membres peuvent voter depuis des formulaires ouverts simultanément : l’avis du premier ne périme pas celui du second. Une répétition strictement identique n’ajoute rien ; une tentative de remplacer un avis enregistré est refusée.

Une seule demande de correction suffit à empêcher l’acceptation et clôture la transmission. Le commentaire reste visible par l’étudiant. Après le nouveau dépôt, aucun avis de l’ancienne version n’est repris. Le déposant et le titulaire actuel du dossier ne peuvent pas approuver leur propre document ; si l’un d’eux est membre de la commission, la transmission est bloquée au lieu de l’exclure discrètement du nombre d’avis requis.

Une modification de la composition pendant l’examen suspend les nouveaux votes. Le chargé de communication doit explicitement relancer la transmission, avec un motif, à la composition actualisée. Les votes antérieurs restent dans une transmission marquée « remplacée » ; tous les membres doivent revoter. Désactiver ou retirer un membre ne peut donc pas transformer automatiquement un dossier en dossier accepté. Une acceptation déjà terminée reste une décision historique sur sa version et sa commission.

## Accès aux versions

Le chargé de communication et les personnes disposant de `reports.view` consultent les dépôts. Les membres de commission disposent d’une lecture limitée aux versions effectivement transmises : un nouveau dépôt reste invisible à la commission jusqu’à sa transmission. Un nouveau membre ne reçoit pas automatiquement accès aux versions antérieures qui ne lui avaient pas été transmises. Le tableau de bord et les compteurs appliquent la même portée.

L’étudiant conserve ses propres versions et leurs retours. Les PDF restent privés, limités à 20 Mo, vérifiés par leur type, signature, taille et empreinte, servis uniquement comme téléchargements authentifiés. Les chemins internes, empreintes, coordonnées des votants et clés de dépôt ne sont pas exposés dans les pages. Les votes montrent seulement le nom, la décision, le commentaire et la date nécessaires au suivi.

## Structure et conservation des données

La migration `2026_10_22_000000_correct_report_workflow.php` complète le schéma v2 au lieu d’importer ou d’écraser le dump historique.

| Élément | Structure v2 |
| --- | --- |
| Dossier / versions PDF | `student_reports` → `report_versions`, déjà présents |
| Éligibilité au dépôt | `report_versions.eligibility_snapshot` ; l’ancien lien vers un examen manuel devient facultatif |
| Transmission d’une version | `report_rounds` : version, émetteur, numéro, état, motif, dates |
| Membres attendus | `report_round_members` : une ligne unique par transmission et compte, avec nom historique |
| Avis individuel | `report_votes` : une seule ligne par membre de transmission |
| Anciennes décisions administratives | `report_decisions`, conservées et étiquetées « Ancien avis administratif » |
| Traçabilité | `report_audits` et `enrollment_audits`, sans copie du contenu des PDF |

Les clés étrangères, contraintes uniques et index évitent les affectations et avis en double ainsi que les votes portant sur une mauvaise version. Les écritures métier utilisent le verrou transactionnel partagé avec les changements de droits. Les tests couvrent les refus de formulaires anciens, les répétitions et les échecs de transaction.

Les anciens rapports au statut `approved` du lot 5 redeviennent **À transmettre**, avec révision incrémentée ; leur document et ancien commentaire sont conservés. Ils doivent passer par la commission et ne sont jamais considérés comme acceptés à l’unanimité sur la seule base de l’ancien avis.

Cette migration n’expose pas de retour arrière destructif supprimant les votes. Un retour au code précédent nécessite une sauvegarde cohérente de la base et de `storage/app/private/reports`. Le script habituel de mise à jour conserve les données existantes.

## Mise à jour et test sur Mac

Depuis la racine du dépôt existant, après avoir enregistré les modifications locales et ouvert Docker Desktop :

```bash
git fetch origin
git switch fix/v2-report-workflow
git pull --ff-only
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Ouvrir **http://localhost:8085**. Conserver le même dossier/projet Docker, le volume MySQL et les PDF privés. Pour une première installation, suivre le [README](../README.md).

Utiliser un administrateur nominatif existant et quatre comptes de démonstration créés depuis les paramètres : un Étudiant (type 1, groupe 1), un Chargé de communication (type 4, groupe 2) et deux membres de Commission (type 2 ou 3, groupe 9). Chaque compte doit personnaliser son mot de passe temporaire. Les essais automatisés ne créent aucun de ces comptes réels.

1. Avec l’administrateur ou le responsable scolarité, préparer un dossier Master 2 Normal, lier explicitement son compte étudiant, configurer une année en cours et un tarif, inscrire l’étudiant puis solder sa scolarité.
2. Avec l’étudiant, ouvrir **Mes inscriptions et stage**, déclarer le stage et vérifier que l’éligibilité devient **Éligible** sans action administrative supplémentaire.
3. Ouvrir **Mon rapport / Mémoire** et déposer un PDF fictif. Le statut est **À transmettre**. Les comptes de commission ne doivent pas encore voir ce dépôt.
4. Avec le chargé de communication, ouvrir **Rapports / Mémoires**, consulter le PDF puis transmettre à la liste complète des deux membres. Le statut devient **En commission**.
5. Avec le premier membre, télécharger le PDF et approuver. Le suivi affiche **1 / 2** ; le rapport reste **En commission**.
6. Avec le second membre, approuver. Le suivi affiche **2 / 2** et le statut devient **Accepté à l’unanimité**. L’administrateur et le chargé de communication n’ont aucun bouton de vote au titre de leur seul groupe.
7. Sur un autre dépôt, faire demander une correction par un membre. Avec l’étudiant, vérifier le commentaire et déposer une version corrigée. Après transmission, vérifier que le compteur repart à **0 / 2**, que les anciennes versions et leurs votes restent visibles et qu’aucune ancienne approbation ne compte pour la nouvelle version.
8. Vérifier qu’un autre étudiant ne peut déclarer le stage ou télécharger le PDF du premier, qu’un enseignant hors commission n’accède pas aux dossiers et qu’un membre récemment ajouté ne voit pas les anciens documents non transmis.

Le style existant est conservé : trois cartes de l’écran étudiant, palette, logos, navigation et tableaux. Les modifications portent sur les actions et libellés de ce parcours. Le contrôle visuel sur Mac et un essai avec deux connexions MySQL simultanées restent à réaliser. La CI valide les migrations sur MySQL, le build TypeScript/Vite, la suite fonctionnelle SQLite et la disponibilité HTTP ; le résultat exact est indiqué dans la PR du sprint.
