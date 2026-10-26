<?php
// Read-only SQL compatibility check, safe for the existing developer database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = $app->make(App\Services\AnnualProgression::class);
$rows = App\Models\Enrollment::with(['latestAnnualDecision', 'retakes'])->whereHas('latestAnnualDecision')->orderByDesc('id')->limit(2)->get();
foreach ($rows as $e) { $service->build($e); $service->quote($e->latestAnnualDecision, App\Models\AcademicYear::where('status', 'current')->first()); }
App\Models\RetakeSubject::where('status', '!=', 'validated')->whereHas('enrollments')->limit(1)->get();
App\Models\RetakeFee::with(['academicYear', 'studyLevel'])->limit(1)->get();
echo 'Parcours annuel : relations et requêtes validées sur '.Illuminate\Support\Facades\DB::connection()->getDriverName().".\n";
