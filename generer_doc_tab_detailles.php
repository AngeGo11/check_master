<?php
$pdo = new PDO("mysql:host=localhost;dbname=check_master_db", "root", "");
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Définir les désignations des tables
$tableDesignations = [
    'action' => 'ACTION',
    'enseignants' => 'ENSEIGNANTS',
    'grade' => 'GRADE',
    'annee_academique' => 'ANNEE ACADEMIQUE',
    'ecue' => 'ECUE',
    'etudiants' => 'ETUDIANTS',
    'filieres' => 'FILIERES',
    'matieres' => 'MATIERES',
    'niveaux' => 'NIVEAUX',
    'notes' => 'NOTES',
    'reclamations' => 'RECLAMATIONS',
    'reunions' => 'REUNIONS',
    'stages' => 'STAGES',
    'utilisateurs' => 'UTILISATEURS',
    'consultations' => 'CONSULTATIONS',
    'archives' => 'ARCHIVES',
    'documents' => 'DOCUMENTS',
    'evaluations' => 'EVALUATIONS',
    'presences' => 'PRESENCES',
    'emplois_temps' => 'EMPLOIS DU TEMPS'
];

// Définir les désignations des colonnes
$columnDesignations = [
    // Table enseignants
    'id_ens' => 'Code de l\'enseignant',
    'nomens' => 'Nom de l\'enseignant',
    'prenomsens' => 'Prénoms de l\'enseignant',
    'emailens' => 'Email de l\'enseignant',
    'dateentfonc' => 'Date d\'entrée en fonction',
    'numtelens' => 'Numéro de téléphone',
    'datenaissens' => 'Date de naissance',
    'sexeens' => 'Sexe de l\'enseignant',
    'photoens' => 'Photo de l\'enseignant',
    'mdpens' => 'Mot de passe',
    
    // Table action
    'id_action' => 'Code de l\'action',
    'libelle_action' => 'Libellé de l\'action',
    'description_action' => 'Description de l\'action',
    
    // Table grade
    'id_grade' => 'Code du grade',
    'libelle_grade' => 'Libellé du grade',
    'description_grade' => 'Description du grade',
    
    // Table etudiants
    'id_etud' => 'Code de l\'étudiant',
    'nometud' => 'Nom de l\'étudiant',
    'prenomsetud' => 'Prénoms de l\'étudiant',
    'emailetud' => 'Email de l\'étudiant',
    'datenaissetud' => 'Date de naissance',
    'sexeetud' => 'Sexe de l\'étudiant',
    'photoetud' => 'Photo de l\'étudiant',
    'mdpetud' => 'Mot de passe',
    'matricule' => 'Numéro matricule',
    'filiere_id' => 'Filière de l\'étudiant',
    'niveau_id' => 'Niveau de l\'étudiant',
    
    // Table filieres
    'id_filiere' => 'Code de la filière',
    'libelle_filiere' => 'Libellé de la filière',
    'description_filiere' => 'Description de la filière',
    
    // Table matieres
    'id_matiere' => 'Code de la matière',
    'libelle_matiere' => 'Libellé de la matière',
    'description_matiere' => 'Description de la matière',
    'coefficient' => 'Coefficient de la matière',
    
    // Table niveaux
    'id_niveau' => 'Code du niveau',
    'libelle_niveau' => 'Libellé du niveau',
    'description_niveau' => 'Description du niveau',
    
    // Table notes
    'id_note' => 'Code de la note',
    'etudiant_id' => 'Étudiant',
    'matiere_id' => 'Matière',
    'note' => 'Valeur de la note',
    'date_evaluation' => 'Date d\'évaluation',
    'type_evaluation' => 'Type d\'évaluation',
    
    // Table utilisateurs
    'id_user' => 'Code de l\'utilisateur',
    'nom_user' => 'Nom de l\'utilisateur',
    'prenoms_user' => 'Prénoms de l\'utilisateur',
    'email_user' => 'Email de l\'utilisateur',
    'mdp_user' => 'Mot de passe',
    'role_user' => 'Rôle de l\'utilisateur',
    'photo_user' => 'Photo de l\'utilisateur',
    
    // Table annee_academique
    'id_annee' => 'Code de l\'année académique',
    'libelle_annee' => 'Libellé de l\'année académique',
    'date_debut' => 'Date de début',
    'date_fin' => 'Date de fin',
    'statut' => 'Statut de l\'année',
    
    // Table ecue
    'id_ecue' => 'Code de l\'ECUE',
    'libelle_ecue' => 'Libellé de l\'ECUE',
    'description_ecue' => 'Description de l\'ECUE',
    'coefficient_ecue' => 'Coefficient de l\'ECUE',
    'matiere_id' => 'Matière associée',
    
    // Table reclamations
    'id_reclamation' => 'Code de la réclamation',
    'titre_reclamation' => 'Titre de la réclamation',
    'description_reclamation' => 'Description de la réclamation',
    'date_reclamation' => 'Date de la réclamation',
    'statut_reclamation' => 'Statut de la réclamation',
    'etudiant_id' => 'Étudiant concerné',
    'reponse' => 'Réponse à la réclamation',
    'date_reponse' => 'Date de réponse',
    
    // Table reunions
    'id_reunion' => 'Code de la réunion',
    'titre_reunion' => 'Titre de la réunion',
    'description_reunion' => 'Description de la réunion',
    'date_reunion' => 'Date de la réunion',
    'heure_debut' => 'Heure de début',
    'heure_fin' => 'Heure de fin',
    'lieu' => 'Lieu de la réunion',
    'participants' => 'Liste des participants',
    'ordre_du_jour' => 'Ordre du jour',
    'compte_rendu' => 'Compte rendu',
    
    // Table stages
    'id_stage' => 'Code du stage',
    'titre_stage' => 'Titre du stage',
    'description_stage' => 'Description du stage',
    'date_debut_stage' => 'Date de début du stage',
    'date_fin_stage' => 'Date de fin du stage',
    'entreprise' => 'Nom de l\'entreprise',
    'adresse_entreprise' => 'Adresse de l\'entreprise',
    'tuteur_entreprise' => 'Tuteur en entreprise',
    'encadrant_academique' => 'Encadrant académique',
    'etudiant_id' => 'Étudiant stagiaire',
    'rapport_stage' => 'Rapport de stage',
    'note_stage' => 'Note du stage',
    
    // Table consultations
    'id_consultation' => 'Code de la consultation',
    'date_consultation' => 'Date de la consultation',
    'heure_consultation' => 'Heure de la consultation',
    'motif' => 'Motif de la consultation',
    'diagnostic' => 'Diagnostic',
    'prescription' => 'Prescription',
    'etudiant_id' => 'Étudiant consulté',
    'medecin_id' => 'Médecin consultant',
    'compte_rendu' => 'Compte rendu médical',
    
    // Table archives
    'id_archive' => 'Code de l\'archive',
    'titre_archive' => 'Titre de l\'archive',
    'description_archive' => 'Description de l\'archive',
    'date_archivage' => 'Date d\'archivage',
    'type_archive' => 'Type d\'archive',
    'chemin_fichier' => 'Chemin du fichier',
    'taille_fichier' => 'Taille du fichier',
    'utilisateur_id' => 'Utilisateur qui a archivé',
    
    // Table documents
    'id_document' => 'Code du document',
    'titre_document' => 'Titre du document',
    'description_document' => 'Description du document',
    'type_document' => 'Type de document',
    'chemin_document' => 'Chemin du document',
    'date_creation' => 'Date de création',
    'date_modification' => 'Date de modification',
    'taille_document' => 'Taille du document',
    'utilisateur_id' => 'Utilisateur créateur',
    
    // Table evaluations
    'id_evaluation' => 'Code de l\'évaluation',
    'titre_evaluation' => 'Titre de l\'évaluation',
    'description_evaluation' => 'Description de l\'évaluation',
    'date_evaluation' => 'Date de l\'évaluation',
    'type_evaluation' => 'Type d\'évaluation',
    'coefficient_evaluation' => 'Coefficient de l\'évaluation',
    'matiere_id' => 'Matière évaluée',
    'enseignant_id' => 'Enseignant évaluateur',
    
    // Table presences
    'id_presence' => 'Code de la présence',
    'date_presence' => 'Date de présence',
    'heure_arrivee' => 'Heure d\'arrivée',
    'heure_depart' => 'Heure de départ',
    'statut_presence' => 'Statut de présence',
    'justification' => 'Justification d\'absence',
    'etudiant_id' => 'Étudiant concerné',
    'cours_id' => 'Cours concerné',
    
    // Table emplois_temps
    'id_emploi' => 'Code de l\'emploi du temps',
    'jour_semaine' => 'Jour de la semaine',
    'heure_debut_cours' => 'Heure de début du cours',
    'heure_fin_cours' => 'Heure de fin du cours',
    'matiere_id' => 'Matière enseignée',
    'enseignant_id' => 'Enseignant',
    'salle' => 'Salle de cours',
    'niveau_id' => 'Niveau concerné',
    'filiere_id' => 'Filière concernée',
    
    // Champs génériques pour les clés étrangères
    'created_at' => 'Date de création',
    'updated_at' => 'Date de modification',
    'deleted_at' => 'Date de suppression',
    'status' => 'Statut',
    'active' => 'Actif/Inactif',
    'visible' => 'Visible/Caché',
    'ordre' => 'Ordre d\'affichage',
    'commentaire' => 'Commentaire',
    'observation' => 'Observation',
    'remarque' => 'Remarque',
    'note' => 'Note',
    'score' => 'Score',
    'pourcentage' => 'Pourcentage',
    'montant' => 'Montant',
    'prix' => 'Prix',
    'quantite' => 'Quantité',
    'nombre' => 'Nombre',
    'total' => 'Total',
    'moyenne' => 'Moyenne',
    'somme' => 'Somme',
    'maximum' => 'Maximum',
    'minimum' => 'Minimum',
    'moyen' => 'Moyen',
    'superieur' => 'Supérieur',
    'inferieur' => 'Inférieur',
    'egal' => 'Égal',
    'different' => 'Différent',
    'compris' => 'Compris entre',
    'hors' => 'Hors de',
    'dans' => 'Dans',
    'sur' => 'Sur',
    'sous' => 'Sous',
    'avec' => 'Avec',
    'sans' => 'Sans',
    'avant' => 'Avant',
    'apres' => 'Après',
    'pendant' => 'Pendant',
    'depuis' => 'Depuis',
    'jusqu' => 'Jusqu\'à',
    'entre' => 'Entre',
    'parmi' => 'Parmi',
    'selon' => 'Selon',
    'dapres' => 'D\'après',
    'pour' => 'Pour',
    'contre' => 'Contre'
];

$tableNumber = 1;

foreach ($tables as $table) {
    // Détails des colonnes
    $stmt = $pdo->query("SHOW FULL COLUMNS FROM $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Identifier les clés primaires
    $primaryKeys = [];
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI') {
            $primaryKeys[] = $col['Field'];
        }
    }
    
    // Générer le code au format CM_TB001, CM_TB002, etc.
    $code = 'CM_TB' . str_pad($tableNumber, 3, '0', STR_PAD_LEFT);
    
    // Récupérer la désignation de la table
    $tableDesignation = isset($tableDesignations[strtolower($table)]) ? $tableDesignations[strtolower($table)] : strtoupper(str_replace('_', ' ', $table));
    
    // Afficher l'en-tête de la table
    echo "<h3>" . $tableNumber . " Code : " . $code . "</h3>";
    echo "<p><strong>Désignation :</strong> " . $tableDesignation . "</p>";
    echo "<p><strong>Longueur</strong></p>";
    echo "<p>:</p>";
    echo "<p><strong>Clé :</strong> " . implode(', ', $primaryKeys) . "</p>";
    
    // Table des colonnes
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 40%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: center; width: 40px;'></th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left; width: 150px;'>Code</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Designation</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: center; width: 80px;'>Taille</th>";
    echo "</tr>";
    
    // Afficher les colonnes
    foreach ($columns as $col) {
        // Extraire la taille (entre parenthèses dans le type, ex : varchar(50))
        preg_match('/\((\d+)\)/', $col['Type'], $matches);
        $taille = isset($matches[1]) ? $matches[1] . ' caractères' : '';
        
        // Pour les types enum, afficher les valeurs possibles
        if (strpos($col['Type'], 'enum') !== false) {
            preg_match('/enum\((.*)\)/', $col['Type'], $enumMatches);
            if (isset($enumMatches[1])) {
                $taille = $enumMatches[1];
            }
        }
        
        // Pour les types date, afficher le format et la taille
        if (strpos($col['Type'], 'date') !== false) {
            if (strpos($col['Type'], 'datetime') !== false) {
                $taille = '19 caractères (YYYY-MM-DD HH:MM:SS)';
            } elseif (strpos($col['Type'], 'timestamp') !== false) {
                $taille = '19 caractères (YYYY-MM-DD HH:MM:SS)';
            } elseif (strpos($col['Type'], 'time') !== false) {
                $taille = '8 caractères (HH:MM:SS)';
            } elseif (strpos($col['Type'], 'year') !== false) {
                $taille = '4 caractères (YYYY)';
            } else {
                $taille = '10 caractères (YYYY-MM-DD)';
            }
        }
        
        // Pour les types int, bigint, etc. sans taille spécifiée
        if (empty($taille) && (strpos($col['Type'], 'int') !== false)) {
            $taille = '11 chiffres'; // Taille par défaut pour int
        }
        
        // Pour les types text, longtext, etc.
        if (empty($taille) && (strpos($col['Type'], 'text') !== false)) {
            if (strpos($col['Type'], 'longtext') !== false) {
                $taille = '4 GB';
            } elseif (strpos($col['Type'], 'mediumtext') !== false) {
                $taille = '16 MB';
            } elseif (strpos($col['Type'], 'text') !== false) {
                $taille = '64 KB';
            }
        }
        
        // Pour les types blob
        if (empty($taille) && (strpos($col['Type'], 'blob') !== false)) {
            if (strpos($col['Type'], 'longblob') !== false) {
                $taille = '4 GB';
            } elseif (strpos($col['Type'], 'mediumblob') !== false) {
                $taille = '16 MB';
            } elseif (strpos($col['Type'], 'blob') !== false) {
                $taille = '64 KB';
            }
        }
        
        // Pour les types decimal, float, double
        if (empty($taille) && (strpos($col['Type'], 'decimal') !== false || strpos($col['Type'], 'float') !== false || strpos($col['Type'], 'double') !== false)) {
            preg_match('/\((\d+,\d+)\)/', $col['Type'], $decimalMatches);
            $taille = isset($decimalMatches[1]) ? $decimalMatches[1] : '10,2';
        }
        
        $is_key = ($col['Key'] === 'PRI') ? 'X' : '';
        
        // Récupérer la désignation de la colonne
        $designation = isset($columnDesignations[strtolower($col['Field'])]) ? $columnDesignations[strtolower($col['Field'])] : $col['Comment'] ?? ucfirst(str_replace('_', ' ', $col['Field']));
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . $is_key . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . strtoupper($col['Field']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $designation . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>" . $taille . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    $tableNumber++;
}
?>