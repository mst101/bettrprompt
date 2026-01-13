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
        // Alert Rules
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('alert_type'); // e.g., workflow_failure
            $table->json('conditions')->nullable(); // Alert-specific conditions
            $table->boolean('email_enabled')->default(true);
            $table->string('email_recipients')->nullable(); // Comma-separated
            $table->boolean('in_app_enabled')->default(true);
            $table->integer('debounce_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('alert_type');
            $table->index('is_active');
        });

        // Alert History (when alerts are triggered)
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained('alert_rules')->cascadeOnDelete();
            $table->integer('triggered_count')->default(1); // How many times before debounce
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['alert_rule_id', 'error_code']);
            $table->index('last_triggered_at');
        });

        // Alert Notifications (sending email/in-app)
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_history_id')->constrained('alert_history')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // email or in_app
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['alert_history_id', 'type']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_notifications');
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_rules');
    }
};
