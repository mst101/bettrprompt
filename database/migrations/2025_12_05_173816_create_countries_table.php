<?php

declare(strict_types=1);

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
        Schema::create('countries', function (Blueprint $table) {
            $table->string('id')->primary(); // ISO code, e.g., 'GB', 'US', 'FR'
            $table->string('continent_id')->nullable(); // e.g., 'E' for Europe, 'A' for Asia
            $table->string('currency_id'); // Foreign key to currencies table
            $table->string('language_id'); // Foreign key to languages table
            $table->enum('first_day_of_week', ['mon', 'sun', 'sat'])->default('mon');
            $table->boolean('uses_miles')->default(false);
            $table->string('name'); // English country name
            $table->timestamps();

            // Indexes
            $table->index('continent_id');
            $table->index('currency_id');
            $table->index('language_id');
            $table->foreign('currency_id')->references('id')->on('currencies')->restrictOnDelete();
            $table->foreign('language_id')->references('id')->on('languages')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
