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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code'); // 'GBP', 'EUR', 'USD'
            $table->string('tier'); // 'pro', 'private'
            $table->string('interval'); // 'monthly', 'yearly'
            $table->string('stripe_price_id'); // Stripe Price ID (e.g., price_1ABC...)
            $table->decimal('amount', 10, 2); // Amount in minor currency units (e.g., 1200 for £12.00)
            $table->timestamps();

            // Ensure unique combination of currency, tier, interval
            $table->unique(['currency_code', 'tier', 'interval']);

            // Foreign key to currencies table
            $table->foreign('currency_code')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
