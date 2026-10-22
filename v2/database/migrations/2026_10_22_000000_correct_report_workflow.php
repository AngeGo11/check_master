<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('eligibility_review_id')->nullable()->change();
            $table->json('eligibility_snapshot')->nullable();
        });
        Schema::create('report_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('forwarded_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('status', 30)->default('open')->index();
            $table->string('reason', 1000)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('closed_at')->nullable();
            $table->unique(['report_version_id', 'number']);
        });
        Schema::create('report_round_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name_snapshot');
            $table->unique(['report_round_id', 'user_id']);
            $table->index(['user_id', 'report_round_id']);
        });
        Schema::create('report_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_round_member_id')->unique()->constrained()->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('comment')->nullable();
            $table->timestamp('created_at');
        });

        // Previous administrative approvals are retained in report_decisions as historical opinions,
        // but cannot constitute unanimous commission acceptance.
        DB::table('student_reports')->where('status', 'approved')->update(['status' => 'submitted', 'revision' => DB::raw('revision + 1')]);
        DB::table('permissions')->where('code', 'reports.review')->delete();
        foreach ([
            'internships.self' => 'Déclarer et corriger son propre stage',
            'reports.forward' => 'Transmettre les rapports à la commission',
            'reports.assigned' => 'Consulter les versions reçues en commission',
            'reports.vote' => 'Donner son avis personnel en commission',
        ] as $code => $name) { DB::table('permissions')->insert(compact('code', 'name')); }
        DB::table('permissions')->where('code', 'enrollments.manage')->update(['name' => 'Gérer les inscriptions']);
        DB::table('permissions')->where('code', 'eligibility.review')->update(['name' => 'Consigner une observation sur les critères (sans bloquer le dépôt)']);
        // Reference groups/type compatibility from the attached check_master_db (8).sql.
        foreach ([
            1 => ['internships.self'],
            2 => ['students.view', 'reports.view', 'reports.forward'],
            3 => ['students.view', 'students.manage', 'schooling.view', 'schooling.manage', 'enrollments.view', 'enrollments.manage', 'payments.manage'],
            4 => ['students.view'],
            9 => ['reports.assigned', 'reports.vote'],
        ] as $group => $permissions) {
            foreach ($permissions as $permission) { DB::table('group_permission')->insertOrIgnore(['user_group_id' => $group, 'permission_code' => $permission]); }
        }
    }

    public function down(): void
    {
        // Dropping votes or restoring a single-person approval would change recorded decisions.
        throw new LogicException('Cette évolution conserve des décisions de commission. Pour revenir au lot précédent, restaurez une sauvegarde cohérente de la base et des documents.');
    }
};
