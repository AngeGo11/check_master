<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_documents', function (Blueprint $t) {
            $t->id(); $t->string('reference', 40)->unique(); $t->string('kind', 20); $t->string('format', 10);
            $t->foreignId('owner_user_id')->constrained('users')->restrictOnDelete(); $t->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1); $t->timestamps(); $t->index(['owner_user_id', 'kind']);
        });
        Schema::create('work_document_versions', function (Blueprint $t) {
            $t->id(); $t->foreignId('work_document_id')->constrained()->restrictOnDelete(); $t->unsignedInteger('number');
            $t->string('title', 200); $t->json('sections'); $t->text('reason'); $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->string('actor_name');
            $t->string('storage_path'); $t->unsignedInteger('size'); $t->string('sha256', 64);
            $t->timestamp('published_at')->nullable(); $t->string('publication_reference', 150)->nullable(); $t->timestamp('created_at');
            $t->unique(['work_document_id', 'number'], 'work_document_version_number'); $t->index(['published_at', 'id']);
        });
        Schema::create('work_document_entries', function (Blueprint $t) {
            $t->id(); $t->foreignId('work_document_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('report_round_id')->constrained()->restrictOnDelete(); $t->foreignId('report_version_id')->constrained()->restrictOnDelete();
            $t->text('body'); $t->json('snapshot'); $t->string('source_fingerprint', 64);
            $t->unique(['work_document_version_id', 'report_version_id'], 'document_entry_report');
        });
        Schema::create('personal_archives', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->restrictOnDelete(); $t->string('source_key', 80);
            $t->foreignId('report_version_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('work_document_version_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('work_document_entry_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('active')->default(true); $t->unsignedInteger('revision')->default(1); $t->timestamps();
            $t->unique(['user_id', 'source_key']); $t->index(['user_id', 'active']);
        });
        Schema::create('document_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('work_document_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('work_document_version_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('personal_archive_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action', 100); $t->json('metadata')->nullable(); $t->uuid('operation_key')->nullable()->unique(); $t->string('request_hash', 64)->nullable(); $t->timestamp('created_at');
            $t->index(['created_at', 'id']); $t->index(['actor_user_id', 'created_at']); $t->index(['action', 'created_at']);
        });
        foreach ([
            'documents.self' => ['Rédiger mes documents et consulter mes extraits', [1]],
            'documents.minutes' => ['Rédiger et publier mes comptes rendus de commission', [9]],
            'documents.view' => ['Consulter les comptes rendus publiés', [2, 3, 8]],
            'documents.archive' => ['Gérer mes archives personnelles autorisées', [1, 2, 3, 4, 5, 6, 7, 8, 9]],
        ] as $code => [$name, $groups]) {
            DB::table('permissions')->insert(compact('code', 'name'));
            foreach ($groups as $id) { DB::table('group_permission')->insert(['user_group_id' => $id, 'permission_code' => $code]); }
        }
    }
    public function down(): void { throw new LogicException('Restaurez une sauvegarde cohérente pour retirer les documents, versions et archives.'); }
};
