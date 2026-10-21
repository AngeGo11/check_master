<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->string('code', 80)->primary();
            $table->string('name');
        });
        Schema::create('user_group_user_type', function (Blueprint $table) {
            $table->foreignId('user_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_group_id', 'user_type_id']);
        });
        Schema::create('type_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_type_id')->constrained()->restrictOnDelete();
            $table->primary(['user_id', 'user_type_id']);
        });
        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_group_id')->constrained()->restrictOnDelete();
            $table->primary(['user_id', 'user_group_id']);
        });
        Schema::create('group_permission', function (Blueprint $table) {
            $table->foreignId('user_group_id')->constrained()->cascadeOnDelete();
            $table->string('permission_code', 80);
            $table->foreign('permission_code')->references('code')->on('permissions')->cascadeOnDelete();
            $table->primary(['user_group_id', 'permission_code']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('auth_version')->default(1);
        });
        Schema::create('access_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_group_id')->nullable()->constrained('user_groups')->nullOnDelete();
            $table->string('action', 80);
            $table->json('metadata');
            $table->timestamp('created_at');
        });

        // Only reference data from the legacy schema; no accounts or passwords are imported.
        DB::table('user_types')->insert([
            ['id' => 1, 'name' => 'Étudiant'],
            ['id' => 2, 'name' => 'Enseignant simple'],
            ['id' => 3, 'name' => 'Enseignant administratif'],
            ['id' => 4, 'name' => 'Personnel administratif'],
        ]);
        $groups = [
            1 => ['student', 'Étudiant', [1]],
            2 => ['communications', 'Chargé de communication', [4]],
            3 => ['schooling', 'Responsable scolarité', [4]],
            4 => ['secretary', 'Secrétaire', [4]],
            5 => ['teacher', 'Enseignant sans responsabilité administrative', [2]],
            6 => ['level-manager', 'Responsable niveau', [3]],
            7 => ['program-manager', 'Responsable filière', [3]],
            8 => ['platform-administrator', 'Administrateur plateforme', [3]],
            9 => ['validation-committee', 'Commission de validation', [2, 3]],
        ];
        foreach ($groups as $id => [$code, $name, $types]) {
            DB::table('user_groups')->insert(compact('id', 'code', 'name'));
            foreach ($types as $type) {
                DB::table('user_group_user_type')->insert(['user_group_id' => $id, 'user_type_id' => $type]);
            }
        }
        // This migration is deliberately self-contained: future catalog changes need a new migration.
        foreach ([
            'users.view' => 'Consulter les utilisateurs',
            'users.manage' => 'Administrer les comptes et leurs accès',
            'groups.view' => 'Consulter les groupes et leurs permissions',
            'groups.manage' => 'Configurer les permissions des groupes',
        ] as $code => $name) {
            DB::table('permissions')->insert(compact('code', 'name'));
            DB::table('group_permission')->insert(['user_group_id' => 8, 'permission_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('access_audits');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_active', 'must_change_password', 'auth_version']));
        Schema::dropIfExists('group_permission');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('type_user');
        Schema::dropIfExists('user_group_user_type');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('user_types');
    }
};
