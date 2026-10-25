<?php

// Exercise the actual configured database (MySQL in Compose), in addition to SQLite feature tests.
// Read-only: safe to run again against a developer database containing existing records.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$audit = $app->make(App\Services\AuditTrail::class);
foreach ([[], ['from' => '2026-01-01', 'to' => '2026-12-31', 'actor' => 'audit%_!'], ['module' => 'claims'], ['actor' => '#1'], ['unattributed' => true]] as $filters) {
    $q = $audit->query($filters); $audit->statistics($q);
    foreach ($audit->ordered(clone $q)->limit(2)->get() as $row) { $audit->summary($row); $audit->detail($row->source, (int) $row->event_id); }
}
$audit->actions();
fwrite(STDOUT, 'Piste d’audit : requêtes et filtres validés sur '.Illuminate\Support\Facades\DB::connection()->getDriverName().".\n");
