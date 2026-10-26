<?php
// Read-only: exercise every document query branch on the configured SQL driver.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$access = $app->make(App\Services\DocumentAccess::class);
$probe = new class extends App\Models\User {
    public bool $globalRead = false;
    public function can($abilities, $arguments = []): bool { return $abilities !== 'reports.view' || $this->globalRead; }
    public function belongsToCompatibleGroup(string $code): bool { return true; }
};
$probe->id = PHP_INT_MAX; // No persisted identity and no writes or content output.
foreach ([false, true] as $globalRead) {
    $probe->globalRead = $globalRead;
    $latest = $access->fullVersions($probe)->selectRaw('max(work_document_versions.id)')->groupBy('work_document_id');
    $access->fullVersions($probe)->whereIn('id', $latest)->with('document')->limit(1)->get();
    $access->fullVersions($probe)->distinct()->count('work_document_id');
    $access->rounds($probe)->with('version.report.student')->limit(1)->get();
    $access->ownEntries($probe)->with('version.document')->limit(1)->get();
    $access->archives($probe)->with(['reportVersion.report', 'version.document', 'entry'])->limit(1)->get();
}
echo 'Documents : versions, sources, extraits et archives validés sur '.Illuminate\Support\Facades\DB::connection()->getDriverName().".\n";
