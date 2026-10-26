<?php
// Synthetic visual fixtures only. Never reads a user, report or document from the database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$base = ['reference' => 'DOC-EXEMPLE-FICTIF', 'number' => 2, 'title' => 'Mémoire — conception d’un système de gestion', 'kind' => 'personal',
    'published' => false, 'publicationReference' => null, 'actor' => 'Auteur fictif', 'date' => '26/10/2026', 'extract' => false, 'entries' => [],
    'sections' => [
        ['heading' => 'Introduction', 'style' => 'paragraph', 'body' => 'Ce document fictif permet de vérifier les accents, les marges et la lisibilité du PDF. Aucune donnée d’étudiant réel n’est utilisée.'],
        ['heading' => 'Objectifs', 'style' => 'list', 'body' => "Conserver les versions enregistrées\nProtéger les informations personnelles\nPermettre le dépôt dès l’éligibilité"],
        ['heading' => 'Observation', 'style' => 'quote', 'body' => 'L’approbation du rapport exige l’accord de chaque membre de la commission.'],
        ['heading' => 'Développement sur plusieurs pages', 'style' => 'paragraph', 'body' => str_repeat("L’étude décrit la rédaction, le suivi des versions et la consultation des documents. Le texte doit rester lisible quand il passe à la page suivante.\n\n", 24)],
    ]];
$source = ['student' => 'ÉTUDIANT Fictif', 'card_number' => 'DEMO-001', 'year' => '2026–2027', 'report_reference' => 'MEM-DEMO', 'report_number' => 1,
    'report_title' => 'Étude fictive des systèmes de gestion', 'round_number' => 1, 'status' => 'approved', 'approved' => 3, 'members' => 3];
$samples = ['personal' => $base,
    'minutes' => [...$base, 'reference' => 'CR-EXEMPLE-FICTIF', 'title' => 'Compte rendu de la commission', 'kind' => 'minutes', 'sections' => [],
        'entries' => [['snapshot' => $source, 'body' => 'Le rapport respecte les attentes de la commission. Les trois membres ont approuvé cette version.']]],
    'extract' => [...$base, 'reference' => 'CR-EXEMPLE-FICTIF', 'title' => 'Extrait individuel du compte rendu', 'kind' => 'minutes', 'published' => true,
        'publicationReference' => 'PV-DEMO-2026', 'extract' => true, 'sections' => [], 'entries' => [['snapshot' => $source, 'body' => 'Le rapport est accepté à l’unanimité. Cet extrait concerne uniquement ce dossier fictif.']]],
];
$folder = storage_path('app/private/document-samples'); Illuminate\Support\Facades\File::ensureDirectoryExists($folder);
foreach ($samples as $name => $data) {
    $bytes = $app->make(App\Services\DocumentFiles::class)->render($data); file_put_contents($folder.'/'.$name.'.pdf', $bytes);
    echo 'Exemple PDF fictif : '.$name.' ('.strlen($bytes)." octets).\n";
    if (in_array('--encoded', $argv, true)) { echo 'DOCUMENT_SAMPLE_BEGIN:'.$name."\n".chunk_split(base64_encode($bytes), 120)."DOCUMENT_SAMPLE_END\n"; }
}
