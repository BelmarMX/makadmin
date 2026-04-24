<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5);
            $table->foreignId('state_id')->constrained();
            $table->foreignId('municipality_id')->constrained();
            $table->string('settlement');
            $table->string('settlement_type')->nullable();
            $table->timestamps();
            $table->index('code');
            $table->index(['state_id', 'municipality_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postal_codes');
    }
};
