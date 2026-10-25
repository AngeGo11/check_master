<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        foreach (['audit.view' => 'Consulter la piste d’audit', 'audit.export' => 'Exporter la piste d’audit'] as $code => $name) {
            DB::table('permissions')->insert(compact('code', 'name'));
            DB::table('group_permission')->insert(['user_group_id' => 8, 'permission_code' => $code]);
        }
        foreach (['access_audits', 'student_audits', 'enrollment_audits', 'report_audits', 'collaboration_audits', 'staff_audits', 'academic_audits', 'claim_events'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->index(['created_at', 'id']);
                $t->index(['actor_user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (['access_audits', 'student_audits', 'enrollment_audits', 'report_audits', 'collaboration_audits', 'staff_audits', 'academic_audits', 'claim_events'] as $table) {
            // MySQL may replace an implicit FK index when the compound index is added.
            // Keep a single-column replacement before removing the compound index.
            if (! Schema::hasIndex($table, ['actor_user_id'])) {
                Schema::table($table, fn (Blueprint $t) => $t->index('actor_user_id', $table.'_audit_actor_fallback'));
            }
            Schema::table($table, function (Blueprint $t) { $t->dropIndex(['created_at', 'id']); $t->dropIndex(['actor_user_id', 'created_at']); });
        }
        DB::table('group_permission')->whereIn('permission_code', ['audit.view', 'audit.export'])->delete();
        DB::table('permissions')->whereIn('code', ['audit.view', 'audit.export'])->delete();
    }
};
