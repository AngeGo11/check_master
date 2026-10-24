<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_claims', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('grade_publication_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('subject', 150);
            $t->text('body');
            $t->json('subjects');
            $t->string('status', 20)->default('submitted')->index();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->uuid('operation_key')->unique();
            $t->string('submission_hash', 64);
            $t->string('storage_path')->nullable();
            $t->unsignedInteger('size')->nullable();
            $t->string('sha256', 64)->nullable();
            $t->timestamps();
        });
        Schema::create('claim_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_claim_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $t->string('actor_name');
            $t->string('actor_role', 30);
            $t->string('action', 20);
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->text('body');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('assignee_name')->nullable();
            $t->uuid('operation_key')->unique();
            $t->string('request_hash', 64);
            $t->timestamp('created_at');
        });
        foreach ([
            'claims.self' => ['Déposer et suivre ses réclamations', [1]],
            'claims.view' => ['Consulter les réclamations étudiantes', [3, 8]],
            'claims.manage' => ['Traiter et transmettre les réclamations', [3]],
            'claims.assigned' => ['Traiter les réclamations qui me sont transmises', [7]],
        ] as $code => [$name, $groups]) {
            DB::table('permissions')->insert(compact('code', 'name'));
            foreach ($groups as $group) { DB::table('group_permission')->insert(['user_group_id' => $group, 'permission_code' => $code]); }
        }
    }

    public function down(): void
    {
        throw new LogicException('Restaurez une sauvegarde cohérente pour retirer les réclamations et leur historique.');
    }
};
