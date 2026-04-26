<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('species_id')->nullable()->constrained('species')->nullOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
            $table->foreignId('temperament_id')->nullable()->constrained('temperaments')->nullOnDelete();
            $table->foreignId('coat_color_id')->nullable()->constrained('pelage_colors')->nullOnDelete();
            $table->string('name', 100);
            $table->string('sex', 20)->default('unknown');
            $table->date('birth_date')->nullable();
            $table->string('microchip', 50)->nullable();
            $table->string('size', 20)->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sterilized')->default(false);
            $table->boolean('is_deceased')->default(false);
            $table->date('deceased_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['clinic_id', 'microchip'], 'patients_clinic_microchip_unique');
            $table->index(['clinic_id', 'client_id']);
            $table->index(['clinic_id', 'is_active']);
            $table->index(['clinic_id', 'name']);
            $table->index(['clinic_id', 'species_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
