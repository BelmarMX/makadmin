<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('professional_license');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable()->after('last_login_at');
            }

            $table->index(['clinic_id', 'is_active'], 'users_clinic_id_is_active_index');
            $table->index(['clinic_id', 'branch_id'], 'users_clinic_id_branch_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_clinic_id_is_active_index');
            $table->dropIndex('users_clinic_id_branch_id_index');
            $table->dropColumn(['is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
