<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_semesters', function (Blueprint $t) {
            $t->id(); $t->string('name', 40); $t->foreignId('study_level_id')->constrained()->restrictOnDelete();
        });
        $source = json_decode(file_get_contents(database_path('reference/academic-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($source['semesters'] as $s) { DB::table('academic_semesters')->insert(['id' => $s['id'], 'name' => $s['name'], 'study_level_id' => $s['study_level_id']]); }
        Schema::create('teaching_programs', function (Blueprint $t) {
            $t->id(); $t->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $t->foreignId('academic_semester_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('number'); $t->unsignedInteger('revision')->default(1); $t->string('status', 20)->default('draft'); $t->unsignedTinyInteger('active_slot')->nullable();
            $t->json('units'); $t->string('source_sha256', 64); $t->text('review_note')->nullable(); $t->uuid('operation_key')->unique();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete(); $t->timestamp('published_at')->nullable(); $t->timestamps();
            $t->unique(['academic_year_id', 'academic_semester_id', 'number'], 'program_version'); $t->unique(['academic_year_id', 'academic_semester_id', 'active_slot'], 'program_active');
        });
        Schema::create('grade_sheets', function (Blueprint $t) {
            $t->id(); $t->foreignId('enrollment_id')->constrained()->restrictOnDelete(); $t->foreignId('academic_semester_id')->constrained()->restrictOnDelete(); $t->foreignId('teaching_program_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1); $t->string('status', 20)->default('draft'); $t->json('marks'); $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['enrollment_id', 'academic_semester_id']);
        });
        Schema::create('grade_publications', function (Blueprint $t) {
            $t->id(); $t->foreignId('grade_sheet_id')->constrained()->restrictOnDelete(); $t->unsignedInteger('number'); $t->json('result'); $t->string('decision', 20); $t->text('comment');
            $t->foreignId('published_by')->constrained('users')->restrictOnDelete(); $t->timestamp('created_at'); $t->unique(['grade_sheet_id', 'number']);
        });
        Schema::create('defenses', function (Blueprint $t) {
            $t->id(); $t->string('reference', 40)->unique(); $t->foreignId('student_id')->constrained()->restrictOnDelete(); $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('report_id')->unique()->constrained('student_reports')->restrictOnDelete(); $t->foreignId('report_version_id')->constrained()->restrictOnDelete(); $t->foreignId('report_round_id')->constrained()->restrictOnDelete();
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete(); $t->uuid('operation_key')->unique(); $t->timestamp('requested_at'); $t->string('status', 20)->default('requested'); $t->unsignedInteger('revision')->default(1);
            $t->dateTime('starts_at')->nullable()->index(); $t->dateTime('ends_at')->nullable(); $t->string('location', 150)->nullable(); $t->string('location_key', 150)->nullable(); $t->text('instructions')->nullable();
            $t->string('outcome', 20)->nullable(); $t->unsignedSmallInteger('score')->nullable(); $t->text('result_comment')->nullable(); $t->foreignId('result_by')->nullable()->constrained('users')->restrictOnDelete(); $t->timestamp('result_at')->nullable(); $t->timestamps();
        });
        Schema::create('defense_jurors', function (Blueprint $t) {
            $t->id(); $t->foreignId('defense_id')->constrained()->restrictOnDelete(); $t->foreignId('user_id')->constrained()->restrictOnDelete(); $t->string('role', 20); $t->string('name_snapshot');
            $t->unsignedTinyInteger('current_slot')->nullable(); $t->timestamp('created_at'); $t->timestamp('removed_at')->nullable(); $t->unique(['defense_id', 'user_id', 'current_slot']);
        });
        Schema::create('academic_audits', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_user_id')->constrained('users')->restrictOnDelete(); $t->foreignId('enrollment_id')->nullable()->constrained()->restrictOnDelete(); $t->foreignId('teaching_program_id')->nullable()->constrained()->restrictOnDelete(); $t->foreignId('defense_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action', 60); $t->json('metadata'); $t->timestamp('created_at');
        });
        $permissions = [
            'grades.view' => ['Consulter les évaluations', [3, 8]], 'grades.manage' => ['Saisir et publier les notes', [3]], 'grades.self' => ['Consulter ses notes publiées', [1]], 'grades.catalogs.manage' => ['Gérer les programmes UE et ECUE', [8]],
            'defenses.view' => ['Consulter les demandes de soutenance', [3, 8]], 'defenses.manage' => ['Planifier les soutenances', [3]], 'defenses.self' => ['Demander et suivre sa soutenance', [1]],
            'defenses.assigned' => ['Consulter les soutenances de son jury', [5, 6, 7, 9]], 'defenses.assess' => ['Enregistrer le résultat en tant que président du jury', [5, 6, 7, 9]],
        ];
        foreach ($permissions as $code => [$name, $groups]) { DB::table('permissions')->insert(compact('code', 'name')); foreach ($groups as $group) { DB::table('group_permission')->insert(['user_group_id' => $group, 'permission_code' => $code]); } }
    }
    public function down(): void { throw new LogicException('Restaurez une sauvegarde cohérente pour retirer les notes, programmes et soutenances.'); }
};
