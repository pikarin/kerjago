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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Opaque to the module. Kerjago writes App\Enums\ConversationKind
            // values; nothing in app/Chat/ interprets them.
            $table->string('kind');

            // Deliberately NOT a foreign key. Chat must be extractable into a
            // service with its own database, which cannot constrain against
            // jobs or applications. A dangling context is a normal state that
            // the host's ContextResolver renders as a placeholder.
            $table->string('context_type')->nullable();
            $table->string('context_id')->nullable();

            // Generic at-most-one slot. The host composes a value such as
            // "application:{ulid}" when a context may hold only one
            // conversation, and leaves it null when it may hold many (a job
            // has one conversation per candidate). Keeping the rule here
            // rather than in a partial index on context_type is what stops
            // domain values leaking into the module's schema.
            $table->string('unique_key')->nullable()->unique();

            $table->json('meta')->nullable();
            $table->string('created_by_participant_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['context_type', 'context_id']);
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
