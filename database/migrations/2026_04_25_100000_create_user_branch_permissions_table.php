<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branch_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('clinic_branches')->cascadeOnDelete();
            $table->string('permission', 150);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['clinic_id', 'user_id', 'branch_id', 'permission'], 'ubp_unique');
            $table->index(['clinic_id', 'user_id', 'branch_id'], 'ubp_user_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_permissions');
    }
};
