<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';


function getMessagesRecus($destinataire_id, $page = 1, $itemsPerPage = 6)
{
    global $pdo;
    $offset = ($page - 1) * $itemsPerPage;

    $sql = "SELECT m.*, 
            CASE 
                WHEN e_exp.nom_ens IS NOT NULL THEN CONCAT(e_exp.nom_ens, ' ', e_exp.prenoms_ens)
                WHEN et_exp.nom_etd IS NOT NULL THEN CONCAT(et_exp.nom_etd, ' ', et_exp.prenom_etd)
                WHEN pa_exp.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_exp.nom_personnel_adm, ' ', pa_exp.prenoms_personnel_adm)
                ELSE 'Inconnu'
            END as expediteur_nom,
            u_exp.login_utilisateur as expediteur_email
            FROM messages m
            JOIN utilisateur u_exp ON m.expediteur_id = u_exp.id_utilisateur
            LEFT JOIN enseignants e_exp ON u_exp.login_utilisateur = e_exp.email_ens
            LEFT JOIN etudiants et_exp ON u_exp.login_utilisateur = et_exp.email_etd
            LEFT JOIN personnel_administratif pa_exp ON u_exp.login_utilisateur = pa_exp.email_personnel_adm
            WHERE m.destinataire_id = ? 
            AND m.statut != 'supprimé' AND m.destinataire_type='individuel'
            ORDER BY m.date_creation DESC
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $destinataire_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



/**
 * Récupère la liste des contacts de l'utilisateur
 * @return array Liste des contacts
 */
function getContacts($userId, $page = 1, $itemsPerPage = 6, $search = '')
{
    global $pdo;
    $offset = ($page - 1) * $itemsPerPage;
    $where = ["u.id_utilisateur != ?", "u.statut_utilisateur = 'Actif'"];
    $params = [$userId];
    if ($search !== '') {
        $where[] = "(CONCAT_WS(' ', e.nom_ens, e.prenoms_ens) LIKE ? OR CONCAT_WS(' ', et.nom_etd, et.prenom_etd) LIKE ? OR CONCAT_WS(' ', pa.nom_personnel_adm, pa.prenoms_personnel_adm) LIKE ? OR u.login_utilisateur LIKE ? OR e.email_ens LIKE ? OR et.email_etd LIKE ? OR pa.email_personnel_adm LIKE ? )";
        $search_param = "%$search%";
        $params = array_merge($params, array_fill(0, 7, $search_param));
    }
    $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT DISTINCT 
                u.id_utilisateur,
                CASE 
                    WHEN tu.lib_tu = 'Enseignant simple' THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                    WHEN tu.lib_tu = 'Étudiant' THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                    WHEN tu.lib_tu = 'Personnel administratif' THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                    ELSE u.login_utilisateur
                END as nom_complet,
                CASE 
                    WHEN tu.lib_tu = 'Enseignant simple' THEN e.email_ens
                    WHEN tu.lib_tu = 'Étudiant' THEN et.email_etd
                    WHEN tu.lib_tu = 'Personnel administratif' THEN pa.email_personnel_adm
                    ELSE u.login_utilisateur
                END as email,
                CASE 
                    WHEN tu.lib_tu = 'Enseignant simple' THEN e.photo_ens
                    WHEN tu.lib_tu = 'Étudiant' THEN et.photo_etd
                    WHEN tu.lib_tu = 'Personnel administratif' THEN pa.photo_personnel_adm
                    ELSE NULL
                END as photo,
                tu.lib_tu
            FROM utilisateur u
            LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur
            LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            $where_sql
            ORDER BY nom_complet ASC
            LIMIT ? OFFSET ?";
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getContactDetails($email)
{
    global $pdo;
    $sql = "SELECT 
                u.id_utilisateur,
                tu.lib_tu as type_utilisateur,
                u.statut_utilisateur as statut,
                
                CASE 
                    WHEN tu.lib_tu = 'Enseignant simple' OR tu.lib_tu = 'Enseignant administratif' THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                    WHEN tu.lib_tu = 'Étudiant' THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                    WHEN tu.lib_tu = 'Personnel administratif' THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                    ELSE 'Inconnu'
                END as nom_complet,
                CASE 
                    WHEN tu.lib_tu = 'Enseignant simple' OR tu.lib_tu = 'Enseignant administratif' THEN e.email_ens
                    WHEN tu.lib_tu = 'Étudiant' THEN et.email_etd
                    WHEN tu.lib_tu = 'Personnel administratif' THEN pa.email_personnel_adm
                    ELSE u.login_utilisateur
                END as email,
                CASE 
                    WHEN tu.lib_tu = 'Enseignant simple' OR tu.lib_tu = 'Enseignant administratif' THEN e.photo_ens
                    WHEN tu.lib_tu = 'Étudiant' THEN et.photo_etd
                    WHEN tu.lib_tu = 'Personnel administratif' THEN pa.photo_personnel_adm
                    ELSE NULL
                END as photo
            FROM utilisateur u
            LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur
            LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            WHERE u.login_utilisateur = ?
            AND u.statut_utilisateur = 'Actif'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sendMessage($expediteur_id, $destinataire_id, $objet, $contenu, $type_message = 'chat', $categorie = 'general', $priorite = 'normale') {
    global $pdo;
    
    try {
        // Vérifier la connexion à la base de données
        if (!$pdo) {
            throw new Exception("Erreur de connexion à la base de données");
        }

        // Préparer la requête SQL
        $sql = "INSERT INTO messages (
                    expediteur_id, 
                    destinataire_id, 
                    destinataire_type,
                    objet, 
                    contenu, 
                    type_message,
                    categorie,
                    priorite,
                    statut,
                    date_envoi
                ) VALUES (
                    :expediteur_id,
                    :destinataire_id,
                    'individuel',
                    :objet,
                    :contenu,
                    :type_message,
                    :categorie,
                    :priorite,
                    'non lu',
                    CURRENT_TIMESTAMP
                )";

        // Préparer et exécuter la requête
        $stmt = $pdo->prepare($sql);
        
        // Lier les paramètres
        $stmt->bindParam(':expediteur_id', $expediteur_id, PDO::PARAM_INT);
        $stmt->bindParam(':destinataire_id', $destinataire_id, PDO::PARAM_INT);
        $stmt->bindParam(':objet', $objet, PDO::PARAM_STR);
        $stmt->bindParam(':contenu', $contenu, PDO::PARAM_STR);
        $stmt->bindParam(':type_message', $type_message, PDO::PARAM_STR);
        $stmt->bindParam(':categorie', $categorie, PDO::PARAM_STR);
        $stmt->bindParam(':priorite', $priorite, PDO::PARAM_STR);

        // Exécuter la requête
        $result = $stmt->execute();

        if ($result) {
            return [
                'success' => true,
                'message_id' => $pdo->lastInsertId(),
                'message' => 'Message envoyé avec succès'
            ];
        } else {
            throw new Exception("Erreur lors de l'insertion du message");
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO dans sendMessage: " . $e->getMessage());
        throw new Exception("Erreur de base de données : " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Erreur dans sendMessage: " . $e->getMessage());
        throw $e;
    }
}




/**
 * Compte le nombre de messages non lus
 * @param int $destinataire_id ID du destinataire
 * @return int
 */
function compterMessagesNonLus($destinataire_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE destinataire_id = ? 
            AND statut = 'non lu'
        ");
        
        $stmt->execute([$destinataire_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des messages non lus : " . $e->getMessage());
        return 0;
    }
}

function getTotalMessages($destinataire_id) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) 
            FROM messages 
            WHERE destinataire_id = ? 
            AND statut != 'supprimé' 
            AND destinataire_type='individuel'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$destinataire_id]);
    return $stmt->fetchColumn();
}

function getTotalContacts($userId, $search = '') {
    global $pdo;
    $where = ["u.id_utilisateur != ?", "u.statut_utilisateur = 'Actif'"];
    $params = [$userId];
    if ($search !== '') {
        $where[] = "(CONCAT_WS(' ', e.nom_ens, e.prenoms_ens) LIKE ? OR CONCAT_WS(' ', et.nom_etd, et.prenom_etd) LIKE ? OR CONCAT_WS(' ', pa.nom_personnel_adm, pa.prenoms_personnel_adm) LIKE ? OR u.login_utilisateur LIKE ? OR e.email_ens LIKE ? OR et.email_etd LIKE ? OR pa.email_personnel_adm LIKE ? )";
        $search_param = "%$search%";
        $params = array_merge($params, array_fill(0, 7, $search_param));
    }
    $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT COUNT(DISTINCT u.id_utilisateur)
            FROM utilisateur u
            LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur
            LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            $where_sql";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchColumn();
}

