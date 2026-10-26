<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_decisions', function (Blueprint $t) {
            $t->id(); $t->foreignId('enrollment_id')->constrained()->restrictOnDelete(); $t->unsignedInteger('number');
            $t->string('outcome', 20); $t->text('reason'); $t->string('reference', 150); $t->boolean('partial_confirmed')->default(false);
            $t->json('snapshot'); $t->json('selected_subjects'); $t->string('source_fingerprint', 64);
            $t->foreignId('decided_by')->constrained('users')->restrictOnDelete(); $t->string('actor_name');
            $t->uuid('operation_key')->unique(); $t->string('request_hash', 64); $t->timestamp('created_at'); $t->unique(['enrollment_id', 'number']);
        });
        Schema::table('enrollments', function (Blueprint $t) {
            $t->foreignId('source_annual_decision_id')->nullable()->unique()->constrained('annual_decisions')->restrictOnDelete();
            $t->json('progression_data')->nullable();
        });
        Schema::create('retake_fees', function (Blueprint $t) {
            $t->id(); $t->foreignId('academic_year_id')->constrained()->restrictOnDelete(); $t->foreignId('study_level_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('amount'); $t->unsignedInteger('revision')->default(1); $t->timestamps(); $t->unique(['academic_year_id', 'study_level_id']);
        });
        Schema::create('retake_subjects', function (Blueprint $t) {
            $t->id(); $t->foreignId('student_id')->constrained()->restrictOnDelete(); $t->foreignId('origin_enrollment_id')->constrained('enrollments')->restrictOnDelete();
            $t->foreignId('grade_publication_id')->constrained()->restrictOnDelete(); $t->foreignId('academic_semester_id')->constrained()->restrictOnDelete();
            $t->foreignId('source_level_id')->constrained('study_levels')->restrictOnDelete(); $t->string('subject_key', 100); $t->string('name'); $t->unsignedInteger('credits');
            $t->string('status', 20)->default('pending'); $t->unsignedSmallInteger('score')->nullable(); $t->unsignedInteger('revision')->default(1); $t->timestamps();
            $t->unique(['origin_enrollment_id', 'academic_semester_id', 'subject_key'], 'retake_origin_subject'); $t->index(['student_id', 'status']);
        });
        Schema::create('enrollment_retake', function (Blueprint $t) {
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete(); $t->foreignId('retake_subject_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('amount'); $t->primary(['enrollment_id', 'retake_subject_id']);
        });
        Schema::create('retake_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('retake_subject_id')->constrained()->restrictOnDelete(); $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_user_id')->constrained('users')->restrictOnDelete(); $t->string('actor_name');
            $t->string('from_status', 20); $t->string('to_status', 20); $t->unsignedSmallInteger('score')->nullable(); $t->string('reference', 150); $t->text('reason');
            $t->uuid('operation_key')->unique(); $t->string('request_hash', 64); $t->timestamp('created_at'); $t->index(['retake_subject_id', 'id']);
        });
        foreach ([
            'progression.view' => ['Consulter les bilans annuels et rattrapages', [3, 8]],
            'progression.manage' => ['Consigner les décisions annuelles et rattrapages', [3]],
            'progression.self' => ['Consulter mon parcours annuel', [1]],
            'progression.fees' => ['Configurer le tarif des matières à reprendre', [8]],
        ] as $code => [$name, $groups]) {
            DB::table('permissions')->insert(compact('code', 'name'));
            foreach ($groups as $id) { DB::table('group_permission')->insert(['user_group_id' => $id, 'permission_code' => $code]); }
        }
    }
    public function down(): void { throw new LogicException('Restaurez une sauvegarde cohérente pour retirer les décisions annuelles et leurs réinscriptions.'); }
};
