<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

/**
 * Fonctions de génération et vérification des matricules
 */

/**
 * Génère un code court pour une spécialité.
 * @param string $lib_specialite Libellé de la spécialité.
 * @return string Code court de la spécialité.
 */
function genererCodeSpecialite($lib_specialite)
{
    $codes = [
        'Informatique' => 'INF',
        'Mathématiques Appliquées' => 'MAT',
        'Réseaux et Télécommunications' => 'R-T',
        'Intelligence Artificielle' => 'IA',
        'Génie Logiciel' => 'GL',
        'Cybersécurité' => 'CYB',
        'Statistique et Décisionnel' => 'STAT',
        'Big Data' => 'BIGD'
        // Ajoutez d'autres mappings si nécessaire
    ];

    // Cherche un code exact
    if (isset($codes[$lib_specialite])) {
        return $codes[$lib_specialite];
    }

    // Sinon, génère un code simple (ex: premier mot + initiales des mots suivants)
    $mots = explode(' ', $lib_specialite);
    $code = strtoupper(substr($mots[0], 0, 3));
    for ($i = 1; $i < count($mots); $i++) {
        $code .= strtoupper(substr($mots[$i], 0, 1));
    }
    return substr($code, 0, 4); // Limite à 4 caractères
}

/**
 * Génère un code court ou une initiale pour un poste administratif.
 * @param string $lib_poste Libellé du poste.
 * @return string Code court ou initiale du poste.
 */
function genererCodePoste($lib_poste)
{
    $codes = [
        'Secrétaire' => 'SEC',
        'Chargé de communication' => 'COM',
        'Responsable scolarité' => 'SCO',
        'Directeur de filière' => 'DIR',
        'Responsable de master' => 'RM',
        'Responsable de licence' => 'RL',
        // Ajoutez d'autres mappings si nécessaire
    ];

    // Cherche un code exact
    if (isset($codes[$lib_poste])) {
        return $codes[$lib_poste];
    }

    // Sinon, utilise la première lettre ou une abréviation simple
    $mots = explode(' ', $lib_poste);
    $code = strtoupper(substr($mots[0], 0, 3));
    for ($i = 1; $i < count($mots); $i++) {
        $code .= strtoupper(substr($mots[$i], 0, 1));
    }
    return substr($code, 0, 4); // Limite à 4 caractères
}

/**
 * Génère un matricule pour un enseignant.
 * Format: ENS-YY-SPE-NNN
 * @param PDO $pdo Connexion à la base de données.
 * @param int $id_specialite ID de la spécialité.
 * @param string $date_entree Date d'entrée en fonction (format YYYY-MM-DD).
 * @return string Le matricule généré.
 * @throws Exception En cas d'erreur ou si spécialité introuvable.
 */
function genererMatriculeEnseignant($pdo, $id_specialite, $date_entree)
{
    try {
        // Année d'entrée (YY)
        $yy = date('y', strtotime($date_entree));

        // Code de spécialité (SPE)
        $stmt_spe = $pdo->prepare("SELECT lib_spe FROM specialite WHERE id_spe = ?");
        $stmt_spe->execute([$id_specialite]);
        $specialite = $stmt_spe->fetch(PDO::FETCH_ASSOC);

        if (!$specialite) {
            throw new Exception("Spécialité introuvable avec ID : " . $id_specialite);
        }
        $code_specialite = genererCodeSpecialite($specialite['lib_spe']);

        // Compter les enseignants avec la même spécialité et la même année d'entrée
        $annee_entree_yy = date('Y', strtotime($date_entree));
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) FROM enseignants e
            JOIN enseignant_specialite es ON e.id_ens = es.id_ens
            WHERE es.id_spe = :id_spe AND YEAR(e.date_entree_fonction) = :annee
        ");
        $stmt_count->execute(['id_spe' => $id_specialite, 'annee' => $annee_entree_yy]);
        $count = $stmt_count->fetchColumn();
        $nnn = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "ENS-" . $yy . "-" . $code_specialite . "-" . $nnn;
    } catch (Exception $e) {
        throw new Exception("Erreur génération matricule enseignant: " . $e->getMessage());
    }
}

/**
 * Génère un matricule pour un étudiant.
 * Format: ETD-YYYY-NNN
 * @param PDO $pdo Connexion à la base de données.
 * @param string $yyyy Année en cours (format YYYY-MM-DD).
 * @return string Le matricule généré.
 * @throws Exception En cas d'erreur.
 */
function genererNumCarteEtudiant($pdo, $id_ac)
{
    try {
        // Vérifier si l'année académique existe
        $sql = "SELECT id_ac FROM annee_academique WHERE id_ac = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_ac]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Année académique non trouvée.");
        }

        // Compter les étudiants inscrits pour cette année académique
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) FROM etudiants 
            WHERE id_ac = :annee
        ");
        $stmt_count->execute(['annee' => $id_ac]);
        $count = $stmt_count->fetchColumn();

        // Génération du numéro : ETD-AAAA-XXX
        $nnn = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return "ETD-" . $id_ac . "-" . $nnn;
    } catch (Exception $e) {
        throw new Exception("Erreur génération matricule étudiant: " . $e->getMessage());
    }
}


/**
 * Génère un matricule pour le personnel administratif.
 * Format: ADM-YY-SRV-NNN
 * @param PDO $pdo Connexion à la base de données.
 * @param int $id_poste ID du groupe utilisateur (poste).
 * @param string $date_embauche Date d'embauche (format YYYY-MM-DD).
 * @return string Le matricule généré.
 * @throws Exception En cas d'erreur ou si poste introuvable.
 */
function genererMatriculePersonnelAdmin($pdo, $id_poste, $date_embauche)
{
    try {
        // Année d'embauche (YY)
        $yy = date('y', strtotime($date_embauche));

        // Code du poste/service (SRV)
        $stmt_poste = $pdo->prepare("SELECT lib_gu FROM groupe_utilisateur WHERE id_gu = ?");
        $stmt_poste->execute([$id_poste]);
        $poste = $stmt_poste->fetch(PDO::FETCH_ASSOC);

        if (!$poste) {
            throw new Exception("Poste administratif introuvable avec ID : " . $id_poste);
        }
        $code_poste = genererCodePoste($poste['lib_gu']);

        // Compter le personnel avec le même poste et la même année d'embauche
        $annee_embauche_yy = date('Y', strtotime($date_embauche));
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) FROM personnel_administratif p
            JOIN utilisateur u ON p.email_personnel_adm = u.login_utilisateur
            JOIN posseder po ON u.id_utilisateur = po.id_util
            WHERE po.id_gu = :id_poste AND YEAR(p.date_embauche) = :annee
        ");
        $stmt_count->execute(['id_poste' => $id_poste, 'annee' => $annee_embauche_yy]);
        $count = $stmt_count->fetchColumn();
        $nnn = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "ADM-" . $yy . "-" . $code_poste . "-" . $nnn;
    } catch (Exception $e) {
        throw new Exception("Erreur génération matricule personnel admin: " . $e->getMessage());
    }
}

/**
 * Fonction générique pour générer un matricule.
 * @param PDO $pdo Connexion à la base de données.
 * @param string $type_personnel Type de personnel ('enseignant', 'etudiant', 'personnel administratif').
 * @param array $params Paramètres spécifiques (ex: ['id_specialite' => ..., 'date' => ...], ['date' => ...], ['id_poste' => ..., 'date' => ...]).
 * @return string Le matricule généré.
 * @throws Exception Si le type est inconnu ou si les paramètres sont insuffisants.
 */
function genererMatricule($pdo, $type_personnel, $params = [])
{
    switch (strtolower($type_personnel)) {
        case 'enseignant':
            if (!isset($params['id_specialite']) || !isset($params['date'])) {
                throw new Exception("Paramètres insuffisants pour générer le matricule enseignant.");
            }
            return genererMatriculeEnseignant($pdo, $params['id_specialite'], $params['date']);

        case 'etudiant':
            if (!isset($params['date'])) {
                throw new Exception("Paramètre date requis pour générer le matricule étudiant.");
            }
            return genererNumCarteEtudiant($pdo, $params['date']);

        case 'personnel administratif':
            if (!isset($params['id_poste']) || !isset($params['date'])) {
                throw new Exception("Paramètres insuffisants pour générer le matricule personnel administratif.");
            }
            return genererMatriculePersonnelAdmin($pdo, $params['id_poste'], $params['date']);

        default:
            throw new Exception("Type de personnel non reconnu: " . $type_personnel);
    }
}

/**
 * Vérifie si un matricule existe déjà dans la base de données.
 * @param PDO $pdo Connexion à la base de données.
 * @param string $matricule Le matricule à vérifier.
 * @param string $type_personnel Type de personnel ('enseignant', 'etudiant', 'personnel administratif').
 * @return bool True si le matricule existe, False sinon.
 * @throws Exception En cas d'erreur ou si le type est inconnu ou la colonne matricule n'existe pas.
 */
function matriculeExiste($pdo, $matricule, $type_personnel)
{
    try {
        $table = '';
        $colonne_matricule = '';

        switch (strtolower($type_personnel)) {
            case 'enseignant':
                $table = 'enseignants';
                $colonne_matricule = 'matricule_ens';
                // Vérifier si la colonne matricule_ens existe
                $stmt_check = $pdo->query("SHOW COLUMNS FROM " . $table . " LIKE '" . $colonne_matricule . "'");
                if ($stmt_check->rowCount() == 0) {
                    // La colonne n'existe pas encore - assume pas de doublon
                    return false;
                }
                break;
            case 'etudiant':
                $table = 'etudiants';
                $colonne_matricule = 'matricule_etd';
                // Vérifier si la colonne matricule_etd existe
                $stmt_check = $pdo->query("SHOW COLUMNS FROM " . $table . " LIKE '" . $colonne_matricule . "'");
                if ($stmt_check->rowCount() == 0) {
                    // La colonne n'existe pas encore - assume pas de doublon
                    return false;
                }
                break;
            case 'personnel administratif':
                $table = 'personnel_administratif';
                $colonne_matricule = 'matricule_personnel_adm';
                // Vérifier si la colonne matricule_personnel_adm existe
                $stmt_check = $pdo->query("SHOW COLUMNS FROM " . $table . " LIKE '" . $colonne_matricule . "'");
                if ($stmt_check->rowCount() == 0) {
                    // La colonne n'existe pas encore - assume pas de doublon
                    return false;
                }
                break;
            default:
                throw new Exception("Type de personnel inconnu pour la vérification du matricule: " . $type_personnel);
        }

        // Si la colonne existe, préparer et exécuter la requête de vérification
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . $table . " WHERE " . $colonne_matricule . " = ?");
        $stmt->execute([$matricule]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la vérification du matricule: " . $e->getMessage());
    }
}

/**
 * Génère un matricule unique en gérant les doublons.
 * @param PDO $pdo Connexion à la base de données.
 * @param string $type_personnel Type de personnel ('enseignant', 'etudiant', 'personnel administratif').
 * @param array $params Paramètres spécifiques nécessaires pour la génération.
 * @return string Le matricule unique généré.
 * @throws Exception En cas d'échec de génération après plusieurs tentatives.
 */
function genererMatriculeUnique($pdo, $type_personnel, $params = [])
{
    $tentatives = 0;
    $max_tentatives = 10; // Limite pour éviter une boucle infinie

    do {
        $matricule = genererMatricule($pdo, $type_personnel, $params);
        $tentatives++;

        // Si le matricule n'existe pas, le retourner
        if (!matriculeExiste($pdo, $matricule, $type_personnel)) {
            return $matricule;
        }

        // Si le matricule existe et on approche de la limite, ajouter un timestamp
        if ($tentatives >= $max_tentatives) {
            $timestamp = date('His');
            $matricule .= '-' . $timestamp;
            // Effectuer une dernière vérification avec le timestamp (très peu probable qu'il y ait doublon)
            if (!matriculeExiste($pdo, $matricule, $type_personnel)) {
                return $matricule;
            }
            // Si exceptionnellement cela existe encore, jeter une erreur
            throw new Exception("Échec de la génération d'un matricule unique après " . $max_tentatives . " tentatives.");
        }

        // Attendre un court instant avant de réessayer pour éviter les collisions
        usleep(10000); // 0.01 seconde

    } while ($tentatives <= $max_tentatives); // Boucle jusqu'à trouver un unique ou atteindre la limite

    // Si la boucle se termine sans retourner (ne devrait pas arriver si max_tentatives est atteint)
    throw new Exception("Erreur interne lors de la génération du matricule unique.");
}





/**
 * Génère un code niveau pour les UE
 * @param string $niveau_etude Niveau d'étude (L1, L2, L3, M1, M2)
 * @return string Code niveau
 */
function genererCodeNiveauUE($niveau_etude)
{
    $codes_niveau = [
       
        'Licence 1' => '1',
        'Licence 2' => '2',
        'Licence 3' => '3',
        'Master 1' => '4',
        'Master 2' => '5'
       
    ];
    
    return $codes_niveau[$niveau_etude] ?? '0';
}

/**
 * Génère un code UE complet
 * Format: [NIVEAU][SEMESTRE][NUMERO_SEQUENTIEL]
 * @param PDO $pdo Connexion à la base de données
 * @param string $niveau Niveau d'étude
 * @param int $semestre Semestre (1 ou 2)
 * @return string Code UE généré
 */
function genererCodeUE($pdo, $niveau, $semestre = null)
{
    try {
        // Génération du code niveau
        $code_niveau = genererCodeNiveauUE($niveau);
        
        // Compter les UE existantes pour ce niveau et ce semestre
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) FROM ue 
            WHERE id_ue LIKE :pattern
        ");
        $pattern = $code_niveau . $semestre . '%';
        $stmt_count->execute(['pattern' => $pattern]);
        $count = $stmt_count->fetchColumn();
        
        // Génération du numéro séquentiel
        $numero_seq = str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        
        return $code_niveau . $semestre . $numero_seq;
        
    } catch (Exception $e) {
        throw new Exception("Erreur génération code UE: " . $e->getMessage());
    }
}

/**
 * Génère un code ECUE (Élément Constitutif d'UE)
 * Format: [id_ue][LETTRE]
 * @param string $id_ue Code de l'UE parent
 * @param int $numero_ecue Numéro de l'ECUE dans l'UE
 * @return string Code ECUE généré
 */
function genererCodeECUE($id_ue, $numero_ecue)
{
    $lettres = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    $lettre = $lettres[$numero_ecue - 1] ?? 'Z';
    
    return $id_ue . $lettre;
}

/**
 * Génère un code ECUE (Élément Constitutif d'UE) unique
 * Format: [PREFIXE_NIVEAU][NUMERO_SEQUENTIEL] (ex: L11, L12, M21, etc.)
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_ue ID de l'UE parente
 * @return string Code ECUE généré
 * @throws Exception En cas d'erreur.
 */
function genererCodeECUEUnique($pdo, $id_ue)
{
    try {
        // Récupérer les informations du niveau d'étude de l'UE parente
        $stmt_ue = $pdo->prepare("
            SELECT ne.lib_niv_etd
            FROM ue u
            JOIN niveau_etude ne ON u.id_niv_etd = ne.id_niv_etd
            WHERE u.id_ue = ?
        ");
        $stmt_ue->execute([$id_ue]);
        $niveau_lib = $stmt_ue->fetchColumn();

        if (!$niveau_lib) {
            throw new Exception("Niveau d'étude introuvable pour l'UE parente ID: " . $id_ue);
        }

        // Créer le préfixe du code (ex: "L1", "M2")
        $prefix_niveau = strtoupper(substr($niveau_lib, 0, 1)); // L ou M
        preg_match('/\\d+/', $niveau_lib, $matches);
        $chiffre_niveau = $matches[0] ?? '0';
        $code_prefix = $prefix_niveau . $chiffre_niveau;
        $prefix_len = strlen($code_prefix);

        // Trouver le plus grand numéro séquentiel pour ce préfixe
        $stmt_max = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(id_ecue, " . ($prefix_len + 1) . ") AS UNSIGNED)) 
            FROM ecue 
            WHERE id_ecue LIKE ?
        ");
        $stmt_max->execute([$code_prefix . '%']);
        $last_num = $stmt_max->fetchColumn();
        
        $next_num = ($last_num ?? 0) + 1;

        // Retourner le code complet
        return $code_prefix . $next_num;

    } catch (Exception $e) {
        throw new Exception("Erreur lors de la génération du code ECUE unique : " . $e->getMessage());
    }
}

/**
 * Vérifie si un code UE existe déjà
 * @param PDO $pdo Connexion à la base de données
 * @param string $id_ue Code UE à vérifier
 * @return bool True si existe, False sinon
 */
function codeUEExiste($pdo, $id_ue)
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ue WHERE id_ue = ?");
        $stmt->execute([$id_ue]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Génère un code UE unique
 * @param PDO $pdo Connexion à la base de données
 * @param int $niveau ID du niveau d'étude
 * @param int $semestre ID du semestre
 * @return string Code UE unique
 */
function genererCodeUEUnique($pdo, $niveau, $semestre = null)
{
    try {
        // Récupérer le libellé du niveau
        $stmt = $pdo->prepare("SELECT lib_niv_etd FROM niveau_etude WHERE id_niv_etd = ?");
        $stmt->execute([$niveau]);
        $niveau_lib = $stmt->fetchColumn();

        // Extraire le numéro du niveau (ex: "Licence 1" -> "1")
        preg_match('/\d+/', $niveau_lib, $matches);
        $numero_niveau = $matches[0] ?? '0';

        // Extraire le numéro du semestre (ex: "Semestre 1" -> "1")
        $numero_semestre = $semestre;

        // Chercher le plus grand code existant pour ce niveau et semestre
        $stmt = $pdo->prepare("SELECT id_ue FROM ue WHERE id_niv_etd = ? AND id_semestre = ?");
        $stmt->execute([$niveau, $semestre]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $max = 0;
        foreach ($ids as $id) {
            // On prend les deux derniers chiffres (le numéro séquentiel)
            $num = intval(substr($id, -2));
            if ($num > $max) $max = $num;
        }

        // Générer le code UE suivant
        $id_ue = $numero_niveau . $numero_semestre . str_pad($max + 1, 2, '0', STR_PAD_LEFT);

        // Vérification finale (très rare mais sûr)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ue WHERE id_ue = ?");
        $stmt->execute([$id_ue]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de générer un code UE unique, veuillez réessayer.");
        }

        return $id_ue;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de la génération du code UE : " . $e->getMessage());
    }
}
