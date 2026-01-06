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
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index(); // delivered, opened, clicked, bounced, complained, unsubscribed
            $table->string('message_id')->index(); // Mailgun message ID
            $table->string('recipient')->index(); // Recipient email address
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->json('payload'); // Full Mailgun event payload
            $table->timestamp('event_timestamp')->nullable()->index(); // When event occurred
            $table->timestamp('processed_at')->nullable(); // When we processed it
            $table->timestamps();

            // Prevent duplicate events
            $table->unique(['message_id', 'event_type', 'event_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
