<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branch_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('clinic_branches')->cascadeOnDelete();
            $table->string('role', 80);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['clinic_id', 'user_id', 'branch_id', 'role'], 'user_branch_roles_unique');
            $table->index(['clinic_id', 'branch_id']);
            $table->index(['clinic_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_roles');
    }
};
