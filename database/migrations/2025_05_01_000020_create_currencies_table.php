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
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g., 'EUR', 'USD', 'GBP'
            $table->string('symbol'); // e.g., '€', '$', '£'
            $table->string('thousands_separator');
            $table->string('decimal_separator');
            $table->boolean('symbol_on_left')->default(true);
            $table->boolean('space_between_amount_and_symbol')->default(false);
            $table->integer('rounding_coefficient')->default(0);
            $table->integer('decimal_digits')->default(2);
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
