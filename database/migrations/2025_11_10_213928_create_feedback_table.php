<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('experience_level')->comment('1-7 scale: novice to experienced');
            $table->unsignedTinyInteger('usefulness')->comment('1-7 scale: not useful to extremely useful');
            $table->unsignedTinyInteger('recommendation_likelihood')->comment('1-7 scale: very unlikely to very likely (NPS)');
            $table->text('suggestions')->nullable();
            $table->json('desired_features')->comment('Array of selected feature preferences');
            $table->text('desired_features_other')->nullable()->comment('Custom feature suggestion when "other" is selected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
