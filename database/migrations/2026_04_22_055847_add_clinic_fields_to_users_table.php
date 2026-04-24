<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('clinic_id')->constrained('clinic_branches')->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('branch_id');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('professional_license')->nullable()->after('avatar_path');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['clinic_id', 'branch_id', 'is_super_admin', 'phone', 'avatar_path', 'professional_license', 'deleted_at']);
        });
    }
};
