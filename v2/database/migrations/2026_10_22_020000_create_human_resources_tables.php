<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_references', function (Blueprint $t) {
            $t->id(); $t->string('kind', 20); $t->unsignedInteger('legacy_id')->nullable(); $t->string('name', 150); $t->string('name_key', 150);
            $t->boolean('is_active')->default(true); $t->unsignedInteger('revision')->default(1); $t->unique(['kind', 'name_key']); $t->unique(['kind', 'legacy_id']);
        });
        foreach ([
            'grade' => ["Professeur d'université", 'Maître de conférences', "Chargé d'enseignement", 'Doctorant enseignant'],
            'function' => ['Directeur de filière', 'Responsable de licence', 'Responsable de master', 'Enseignant vacataire'],
            'specialty' => ['Informatique', 'Mathématiques Appliquées', 'Réseaux et Télécommunications', 'Intelligence Artificielle', 'Génie Logiciel', 'Cybersécurité', 'Statistique et Décisionnel', 'Big Data'],
        ] as $kind => $names) {
            foreach ($names as $i => $name) { DB::table('staff_references')->insert(['kind' => $kind, 'legacy_id' => $i + 1, 'name' => $name, 'name_key' => Str::lower($name)]); }
        }
        Schema::create('staff_profiles', function (Blueprint $t) {
            $t->id(); $t->string('reference', 40)->unique(); $t->string('kind', 20)->index(); $t->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->uuid('operation_key')->unique(); $t->string('request_hash', 64); $t->string('last_name', 100); $t->string('first_name', 150); $t->string('email', 254)->unique();
            $t->string('phone', 30)->nullable(); $t->string('gender', 20)->nullable(); $t->date('date_of_birth')->nullable(); $t->date('hired_on')->nullable(); $t->string('position', 150)->nullable();
            $t->boolean('is_archived')->default(false); $t->unsignedInteger('revision')->default(1); $t->timestamps(); $t->index(['kind', 'is_archived', 'last_name', 'id']);
        });
        Schema::create('staff_specialties', function (Blueprint $t) {
            $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete(); $t->foreignId('staff_reference_id')->constrained()->restrictOnDelete(); $t->primary(['staff_profile_id', 'staff_reference_id']);
        });
        Schema::create('staff_assignments', function (Blueprint $t) {
            $t->id(); $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete(); $t->foreignId('staff_reference_id')->constrained()->restrictOnDelete();
            $t->string('kind', 20); $t->string('name_snapshot', 150); $t->date('assigned_on'); $t->date('ended_on')->nullable(); $t->unsignedTinyInteger('current_slot')->nullable();
            $t->foreignId('assigned_by')->constrained('users')->restrictOnDelete(); $t->timestamp('created_at'); $t->unique(['staff_profile_id', 'kind', 'current_slot'], 'staff_current_assignment');
        });
        Schema::create('staff_photos', function (Blueprint $t) {
            $t->id(); $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete(); $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_key')->unique(); $t->string('source_sha256', 64); $t->string('sha256', 64); $t->string('storage_path')->unique(); $t->unsignedBigInteger('size'); $t->string('mime', 30);
            $t->unsignedTinyInteger('current_slot')->nullable(); $t->timestamp('created_at'); $t->timestamp('withdrawn_at')->nullable(); $t->unique(['staff_profile_id', 'current_slot']);
        });
        Schema::create('staff_audits', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_user_id')->constrained('users')->restrictOnDelete(); $t->foreignId('staff_profile_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('staff_reference_id')->nullable()->constrained()->restrictOnDelete(); $t->string('action', 60); $t->json('metadata'); $t->timestamp('created_at'); $t->index(['staff_profile_id', 'id']);
        });
        foreach (['hr.view' => 'Consulter les ressources humaines', 'hr.manage' => 'Gérer les fiches et attributions RH', 'hr.catalogs.manage' => 'Gérer les grades, fonctions et spécialités', 'hr.self' => 'Consulter sa fiche professionnelle'] as $code => $name) { DB::table('permissions')->insert(compact('code', 'name')); }
        foreach (['hr.view', 'hr.manage', 'hr.catalogs.manage'] as $code) { DB::table('group_permission')->insert(['user_group_id' => 8, 'permission_code' => $code]); }
        foreach (range(2, 9) as $id) { DB::table('group_permission')->insert(['user_group_id' => $id, 'permission_code' => 'hr.self']); }
    }
    public function down(): void { throw new LogicException('Restaurez une sauvegarde cohérente pour retirer les fiches RH et leurs historiques.'); }
};
