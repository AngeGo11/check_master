<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('status', 30)->default('submitted')->index();
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedInteger('version_count')->default(1);
            $table->timestamp('latest_submitted_at');
            $table->timestamps();
        });
        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('student_reports')->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('eligibility_review_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('storage_path')->unique();
            $table->unsignedInteger('size');
            $table->string('sha256', 64);
            $table->uuid('operation_key')->unique();
            $table->timestamp('created_at');
            $table->unique(['report_id', 'number']);
        });
        Schema::create('report_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('student_reports')->restrictOnDelete();
            $table->foreignId('report_version_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('eligibility_review_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('comment');
            $table->timestamp('created_at');
        });
        Schema::create('report_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('report_id')->constrained('student_reports')->restrictOnDelete();
            $table->foreignId('report_version_id')->constrained()->restrictOnDelete();
            $table->string('action', 100);
            $table->json('metadata');
            $table->timestamp('created_at');
        });
        foreach ([
            'reports.view' => 'Consulter les mémoires déposés et leurs versions',
            'reports.review' => 'Approuver un mémoire ou demander une correction',
            'reports.self' => 'Déposer et consulter son propre mémoire',
        ] as $code => $name) {
            DB::table('permissions')->insert(compact('code', 'name'));
            DB::table('group_permission')->insert(['user_group_id' => $code === 'reports.self' ? 1 : 8, 'permission_code' => $code]);
        }
    }
    public function down(): void
    {
        DB::table('permissions')->whereIn('code', ['reports.view', 'reports.review', 'reports.self'])->delete();
        foreach (['report_audits', 'report_decisions', 'report_versions', 'student_reports'] as $table) { Schema::dropIfExists($table); }
    }
};
