<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('pending');
            // Only the current year has slot 1; all other rows have NULL.
            $table->unsignedTinyInteger('current_slot')->nullable()->unique();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('tuition_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_level_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['academic_year_id', 'study_level_id']);
        });
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('tuition_fee_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_status_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_due');
            $table->date('enrolled_on');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 1000)->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id', 'study_level_id'], 'enrollment_student_year_level_unique');
        });
        Schema::create('tuition_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('receipt_number', 40)->unique();
            $table->uuid('operation_key')->unique();
            $table->unsignedInteger('amount');
            $table->string('method', 20);
            $table->string('cheque_number', 50)->nullable();
            $table->string('purpose', 255);
            $table->date('paid_on');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason', 1000)->nullable();
            $table->timestamps();
        });
        Schema::create('enrollment_internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('company', 200);
            $table->string('title', 255);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('evidence_reference', 255);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('eligibility_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('result', 20);
            $table->string('fingerprint', 64);
            $table->json('criteria');
            $table->string('reason', 1000);
            $table->timestamp('created_at');
        });
        Schema::create('enrollment_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 100);
            $table->json('metadata');
            $table->timestamp('created_at');
        });
        foreach ([
            'schooling.view' => 'Consulter les années académiques et les tarifs',
            'schooling.manage' => 'Configurer les années académiques et les tarifs',
            'enrollments.view' => 'Consulter les inscriptions et leurs règlements',
            'enrollments.manage' => 'Gérer les inscriptions et les stages déclarés',
            'payments.manage' => 'Enregistrer et annuler des écritures de paiement',
            'eligibility.review' => 'Examiner les critères d’éligibilité',
        ] as $code => $name) {
            DB::table('permissions')->insert(['code' => $code, 'name' => $name]);
            DB::table('group_permission')->insert(['user_group_id' => 8, 'permission_code' => $code]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('code', ['schooling.view', 'schooling.manage', 'enrollments.view', 'enrollments.manage', 'payments.manage', 'eligibility.review'])->delete();
        foreach (['enrollment_audits', 'eligibility_reviews', 'enrollment_internships', 'tuition_payments', 'enrollments', 'tuition_fees', 'academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
