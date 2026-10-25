<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditFiltersRequest;
use App\Services\AuditTrail;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(AuditFiltersRequest $r, AuditTrail $audit)
    {
        $filters = $r->filters(); $q = $audit->query($filters);
        return Inertia::render('audit/index', [
            'events' => $audit->ordered(clone $q)->paginate(25)->appends($filters)->through(fn ($row) => $audit->summary($row)),
            'statistics' => $audit->statistics($q), 'filters' => $filters,
            'modules' => collect(AuditTrail::SOURCES)->map(fn ($d, $key) => ['value' => $key, 'label' => $d['label']])->values(),
            'actions' => $audit->actions(), 'canExport' => $r->user()->can('audit.export'), 'exportLimit' => AuditTrail::EXPORT_LIMIT,
        ])->toResponse($r)->header('Cache-Control', 'private, no-store');
    }

    public function show(AuditFiltersRequest $r, AuditTrail $audit, string $source, int $id)
    {
        return Inertia::render('audit/show', ['event' => $audit->detail($source, $id), 'filters' => [...$r->filters(), 'page' => (int) ($r->validated('page') ?? 1)]])
            ->toResponse($r)->header('Cache-Control', 'private, no-store');
    }

    public function export(AuditFiltersRequest $r, AuditTrail $audit)
    {
        Gate::authorize('audit.export');
        // One bounded SELECT is the export snapshot: concurrent inserts cannot change a later count or page.
        $rows = $audit->ordered($audit->query($r->filters()))->limit(AuditTrail::EXPORT_LIMIT + 1)->get();
        if ($rows->count() > AuditTrail::EXPORT_LIMIT) {
            return redirect()->route('audit.index', $r->filters())->withErrors(['export' => 'La sélection dépasse 10 000 actions. Précisez les filtres pour exporter toutes les lignes sans troncature.']);
        }
        Gate::forUser($r->user()->fresh())->authorize('audit.view');
        Gate::forUser($r->user()->fresh())->authorize('audit.export');
        return response()->streamDownload(function () use ($rows, $audit) {
            $out = fopen('php://output', 'wb'); fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Événement', 'Date (UTC)', 'Module', 'Action', 'Code action', 'Utilisateur', 'Identifiant utilisateur', 'Dossier'], ';', '"', '', "\r\n");
            foreach ($rows as $row) {
                $e = $audit->summary($row);
                fputcsv($out, array_map([AuditTrail::class, 'csvCell'], [$e['key'], $e['created_at'], $e['module'], $e['label'], $e['action'], $e['actor'], (string) ($e['actor_id'] ?? ''), implode(' · ', $e['references'])]), ';', '"', '', "\r\n");
            }
            fclose($out);
        }, 'check-master-audit-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
