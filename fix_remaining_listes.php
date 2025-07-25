<?php
// Script pour corriger automatiquement tous les fichiers de listes restants
$liste_files = [
    'liste_niveaux_etudes.php' => 'niveaux_etudes',
    'liste_semestres.php' => 'semestres',
    'liste_specialites.php' => 'specialites',
    'liste_statuts_jury.php' => 'statuts_jury',
    'liste_traitements.php' => 'traitements',
    'liste_types_utilisateurs.php' => 'types_utilisateurs',
    'liste_ue.php' => 'ue',
    'liste_ecue.php' => 'ecue'
];

foreach ($liste_files as $filename => $liste_name) {
    $filepath = 'app/Views/listes/' . $filename;
    if (!file_exists($filepath)) {
        echo "Fichier $filename non trouvé, ignoré.\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Ajouter la vérification du paramètre liste au début
    $check_code = "<?php
// Vérifier si on est dans le bon contexte
if (!isset(\$_GET['liste']) || \$_GET['liste'] !== '$liste_name') {
    return;
}

";
    
    // Supprimer la structure HTML complète
    $content = preg_replace('/<!DOCTYPE html>.*?<\/html>/s', '', $content);
    
    // Remplacer les liens de retour
    $content = preg_replace('/href=\"\.\.\/index_commission\.php\?page=parametres_generaux\"/', 'href=\"?page=parametres_generaux\"', $content);
    $content = preg_replace('/href=\"\.\.\/index_commission\.php\?page=parametres_generaux/', 'href=\"?page=parametres_generaux', $content);
    
    // Ajouter les paramètres cachés dans les formulaires
    $content = preg_replace('/<form([^>]*)method=\"GET\"/', '<form$1method=\"GET\"><input type=\"hidden\" name=\"page\" value=\"parametres_generaux\"><input type=\"hidden\" name=\"liste\" value=\"' . $liste_name . '\">', $content);
    $content = preg_replace('/<form([^>]*)method=\"POST\"/', '<form$1method=\"POST\"><input type=\"hidden\" name=\"page\" value=\"parametres_generaux\"><input type=\"hidden\" name=\"liste\" value=\"' . $liste_name . '\">', $content);
    
    // Corriger les liens de pagination
    $content = preg_replace('/href=\"\?search=/', 'href=\"?page=parametres_generaux&liste=' . $liste_name . '&search=', $content);
    $content = preg_replace('/href=\"\?page=/', 'href=\"?page=parametres_generaux&liste=' . $liste_name . '&page=', $content);
    
    // Corriger les redirections
    $content = preg_replace('/header\(\'Location: \' \. strtok\(\$_SERVER\[\'REQUEST_URI\'\], \'?\'\)\);/', 'header(\'Location: ?page=parametres_generaux&liste=' . $liste_name . '\');', $content);
    
    // Ajouter le code de vérification au début
    $content = $check_code . $content;
    
    // Sauvegarder le fichier
    file_put_contents($filepath, $content);
    echo "Fichier $filename corrigé.\n";
}

echo "Tous les fichiers ont été corrigés avec succès!\n";
?> 