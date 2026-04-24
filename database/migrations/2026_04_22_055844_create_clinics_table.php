<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('legal_name');
            $table->string('commercial_name');
            $table->string('rfc', 13)->nullable();
            $table->string('fiscal_regime')->nullable();
            $table->string('tax_address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('responsible_vet_name');
            $table->string('responsible_vet_license');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->jsonb('settings')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
