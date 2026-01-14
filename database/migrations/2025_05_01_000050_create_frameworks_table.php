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
        Schema::create('frameworks', function (Blueprint $table) {
            $table->string('code')->primary(); // e.g., 'CHAIN_OF_THOUGHT', 'CO_STAR'
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->enum('complexity', ['low', 'medium', 'high']);
            $table->json('components');
            $table->text('best_for')->nullable();
            $table->text('not_for')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('complexity');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frameworks');
    }
};
