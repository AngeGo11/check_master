<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $t) {
            $t->id(); $t->string('reference', 40)->unique(); $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_key')->unique(); $t->string('request_hash', 64); $t->string('title'); $t->text('description')->nullable();
            $t->string('priority', 20)->default('normal'); $t->string('location'); $t->dateTime('starts_at')->index(); $t->dateTime('ends_at')->index();
            $t->unsignedInteger('revision')->default(1); $t->timestamp('cancelled_at')->nullable(); $t->string('cancellation_reason', 1000)->nullable(); $t->timestamps();
        });
        Schema::create('meeting_participants', function (Blueprint $t) {
            $t->id(); $t->foreignId('meeting_id')->constrained()->restrictOnDelete(); $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('name_snapshot'); $t->string('response', 20)->default('pending'); $t->unsignedInteger('response_revision')->default(1);
            $t->timestamp('responded_at')->nullable(); $t->timestamp('removed_at')->nullable(); $t->unique(['meeting_id', 'user_id']); $t->index(['user_id', 'removed_at']);
        });
        Schema::create('meeting_agenda_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('meeting_id')->constrained()->restrictOnDelete(); $t->foreignId('report_round_id')->constrained()->restrictOnDelete();
            $t->unique(['meeting_id', 'report_round_id']);
        });
        Schema::create('meeting_documents', function (Blueprint $t) {
            $t->id(); $t->foreignId('meeting_id')->constrained()->restrictOnDelete(); $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_key')->unique(); $t->string('title'); $t->string('storage_path')->unique(); $t->unsignedBigInteger('size'); $t->string('sha256', 64);
            $t->timestamp('created_at'); $t->timestamp('withdrawn_at')->nullable(); $t->string('withdrawal_reason', 1000)->nullable();
        });
        Schema::create('internal_messages', function (Blueprint $t) {
            $t->id(); $t->string('reference', 40)->unique(); $t->foreignId('sender_id')->constrained('users')->restrictOnDelete(); $t->string('sender_name');
            $t->uuid('operation_key')->unique(); $t->string('request_hash', 64); $t->string('status', 20)->index(); $t->string('kind', 30)->default('message');
            $t->string('subject')->default(''); $t->text('body'); $t->string('priority', 20)->default('normal'); $t->string('category', 30)->default('general');
            $t->json('draft_recipients')->nullable(); $t->foreignId('source_id')->nullable()->constrained('internal_messages')->restrictOnDelete(); $t->string('source_mode', 20)->nullable();
            $t->foreignId('meeting_id')->nullable()->constrained()->restrictOnDelete(); $t->string('sender_folder', 20)->default('sent');
            $t->unsignedInteger('revision')->default(1); $t->unsignedInteger('sender_revision')->default(1); $t->timestamp('sent_at')->nullable(); $t->timestamps();
            $t->index(['sender_id', 'status', 'sender_folder', 'id']);
        });
        Schema::create('message_recipients', function (Blueprint $t) {
            $t->id(); $t->foreignId('internal_message_id')->constrained()->restrictOnDelete(); $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('name_snapshot'); $t->string('folder', 20)->default('inbox'); $t->timestamp('read_at')->nullable(); $t->unsignedInteger('revision')->default(1);
            $t->unique(['internal_message_id', 'user_id']); $t->index(['user_id', 'folder', 'read_at']);
        });
        Schema::create('collaboration_audits', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('meeting_id')->nullable()->constrained()->restrictOnDelete(); $t->foreignId('message_id')->nullable()->constrained('internal_messages')->restrictOnDelete();
            $t->string('action', 80); $t->json('metadata'); $t->timestamp('created_at'); $t->index(['meeting_id', 'id']);
        });
        foreach (['messages.use' => 'Utiliser sa messagerie interne', 'messages.broadcast' => 'Sélectionner des groupes destinataires',
            'meetings.view' => 'Consulter les réunions accessibles', 'meetings.manage' => 'Organiser ses réunions de commission'] as $code => $name) {
            DB::table('permissions')->insert(compact('code', 'name'));
        }
        foreach (range(1, 9) as $id) { DB::table('group_permission')->insert(['user_group_id' => $id, 'permission_code' => 'messages.use']); }
        foreach ([2, 8, 9] as $id) { DB::table('group_permission')->insert(['user_group_id' => $id, 'permission_code' => 'messages.broadcast']); }
        foreach ([8, 9] as $id) { DB::table('group_permission')->insert(['user_group_id' => $id, 'permission_code' => 'meetings.view']); }
        DB::table('group_permission')->insert(['user_group_id' => 9, 'permission_code' => 'meetings.manage']);
    }
    public function down(): void
    {
        throw new LogicException('Restaurez une sauvegarde cohérente pour retirer les réunions et messages sans perdre leurs historiques.');
    }
};
