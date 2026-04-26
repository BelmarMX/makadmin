<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_role_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('role', 80);
            $table->string('module_key', 100);
            $table->boolean('is_enabled')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['clinic_id', 'role', 'module_key'], 'crm_unique');
            $table->index(['clinic_id', 'role'], 'crm_clinic_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_role_modules');
    }
};
