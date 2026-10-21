<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
        });
        Schema::create('student_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
        });
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('revision')->default(1);
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 50)->unique();
            $table->string('last_name', 100);
            $table->string('first_name', 150);
            $table->string('email', 254)->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->foreignId('study_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('promotion_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_status_id')->constrained()->restrictOnDelete();
            // An explicit identity link, never an email join or an implicit account creation.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->boolean('is_archived')->default(false)->index();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });
        Schema::create('student_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 100);
            $table->json('metadata');
            $table->timestamp('created_at');
        });

        foreach (['Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2'] as $index => $name) {
            DB::table('study_levels')->insert(['id' => $index + 1, 'name' => $name]);
        }
        foreach (['Normal', 'À cheval', 'Redoublant'] as $index => $name) {
            DB::table('student_statuses')->insert(['id' => $index + 1, 'name' => $name]);
        }
        // Only the non-personal reference catalogue from the historical application.
        for ($year = 2003; $year <= 2024; $year++) {
            DB::table('promotions')->insert(['id' => $year - 2002, 'name' => $year.'-'.($year + 1)]);
        }
        $permissions = [
            'students.view' => 'Consulter tous les dossiers étudiants',
            'students.manage' => 'Créer, modifier et archiver les dossiers étudiants',
            'students.self' => 'Consulter son propre dossier étudiant',
            'academic.view' => 'Consulter les promotions, niveaux et statuts',
            'academic.manage' => 'Créer et modifier les promotions',
        ];
        foreach ($permissions as $code => $name) {
            DB::table('permissions')->insert(['code' => $code, 'name' => $name]);
            DB::table('group_permission')->insert([
                'user_group_id' => $code === 'students.self' ? 1 : 8, 'permission_code' => $code,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('code', ['students.view', 'students.manage', 'students.self', 'academic.view', 'academic.manage'])->delete();
        Schema::dropIfExists('student_audits');
        Schema::dropIfExists('students');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('student_statuses');
        Schema::dropIfExists('study_levels');
    }
};
