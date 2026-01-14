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
        Schema::create('question_variants', function (Blueprint $table) {
            $table->id();
            $table->string('question_id', 10);
            $table->string('personality_pattern', 50);
            $table->text('phrasing');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->onDelete('cascade');

            $table->unique(['question_id', 'personality_pattern']);
            $table->index('question_id');
            $table->index('personality_pattern');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_variants');
    }
};
