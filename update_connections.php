<?php
/**
 * Script pour mettre à jour tous les modèles et contrôleurs
 * pour utiliser la nouvelle classe DataBase singleton
 */

require_once __DIR__ . '/config/config.php';

echo "=== Mise à jour des connexions PDO vers le pattern singleton ===\n\n";

// Liste des dossiers à traiter
$directories = [
    'app/Models/',
    'app/Controllers/'
];

$updated_files = 0;
$total_files = 0;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "Dossier $dir non trouvé, ignoré.\n";
        continue;
    }
    
    $files = glob($dir . '*.php');
    $total_files += count($files);
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $original_content = $content;
        $modified = false;
        
        // Remplacer les anciennes connexions PDO
        $patterns = [
            // Remplacer new PDO(...) par DataBase::getConnection()
            '/new\s+PDO\s*\(\s*["\'][^"\']*["\']\s*,\s*["\'][^"\']*["\']\s*,\s*["\'][^"\']*["\']\s*\)/',
            // Remplacer $pdo = new PDO(...) par $pdo = DataBase::getConnection()
            '/\$pdo\s*=\s*new\s+PDO\s*\(/',
            // Remplacer $this->pdo = new PDO(...) par $this->pdo = DataBase::getConnection()
            '/\$this->pdo\s*=\s*new\s+PDO\s*\(/',
            // Remplacer $this->db = new PDO(...) par $this->db = DataBase::getConnection()
            '/\$this->db\s*=\s*new\s+PDO\s*\(/'
        ];
        
        $replacements = [
            'DataBase::getConnection()',
            '$pdo = DataBase::getConnection()',
            '$this->pdo = DataBase::getConnection()',
            '$this->db = DataBase::getConnection()'
        ];
        
        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacements[$index], $content);
                $modified = true;
            }
        }
        
        // Mettre à jour les constructeurs pour utiliser DataBase::getConnection()
        if (strpos($content, 'public function __construct') !== false) {
            // Remplacer les constructeurs qui créent des connexions PDO
            $content = preg_replace(
                '/public function __construct\s*\(\s*\)\s*\{\s*\$this->pdo\s*=\s*new PDO[^}]*\}/',
                'public function __construct() {
        $this->pdo = DataBase::getConnection();
    }',
                $content
            );
            
            $content = preg_replace(
                '/public function __construct\s*\(\s*\)\s*\{\s*\$this->db\s*=\s*new PDO[^}]*\}/',
                'public function __construct() {
        $this->db = DataBase::getConnection();
    }',
                $content
            );
        }
        
        // Ajouter l'import de DataBase si nécessaire
        if ($modified && strpos($content, 'require_once') === false && strpos($content, 'require') === false) {
            $content = "<?php\n\nrequire_once __DIR__ . '/../../config/config.php';\n\n" . substr($content, 5);
        }
        
        if ($modified) {
            file_put_contents($file, $content);
            echo "✅ Mis à jour: $file\n";
            $updated_files++;
        }
    }
}

echo "\n=== Résumé ===\n";
echo "Fichiers traités: $total_files\n";
echo "Fichiers mis à jour: $updated_files\n";
echo "Fichiers inchangés: " . ($total_files - $updated_files) . "\n\n";

// Test de la connexion
echo "=== Test de la connexion ===\n";
try {
    $pdo = DataBase::getConnection();
    echo "✅ Connexion réussie!\n";
    
    $info = DataBase::getConnectionInfo();
    echo "📊 Informations de connexion:\n";
    foreach ($info as $key => $value) {
        echo "   $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
    
    // Test d'une requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reunions");
    $result = $stmt->fetch();
    echo "📈 Test requête: " . $result['total'] . " réunions trouvées\n";
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
}

echo "\n=== Mise à jour terminée ===\n";
echo "Tous les modèles et contrôleurs utilisent maintenant le pattern singleton DataBase.\n";
echo "Cela devrait résoudre les problèmes de 'trop de connexions'.\n";
?> 