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
        Schema::create('inbound_emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->index(); // Mailgun message ID
            $table->string('from')->index(); // Sender email address
            $table->string('to'); // Recipient email address
            $table->string('subject')->nullable();
            $table->text('body_plain')->nullable(); // Plain text body
            $table->text('body_html')->nullable(); // HTML body
            $table->text('stripped_text')->nullable(); // Mailgun's stripped text (body without quoted replies)
            $table->text('stripped_signature')->nullable(); // Mailgun's stripped signature
            $table->json('headers')->nullable(); // Email headers
            $table->json('attachments')->nullable(); // Attachment metadata
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Matched user
            $table->timestamp('received_at')->nullable()->index(); // When email was received
            $table->timestamp('processed_at')->nullable(); // When we processed it
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_emails');
    }
};
