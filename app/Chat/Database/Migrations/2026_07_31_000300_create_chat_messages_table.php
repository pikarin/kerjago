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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();

            // Null for system messages, which the host emits with no author.
            $table->string('participant_id')->nullable();

            $table->string('type')->default('text');
            $table->text('body')->nullable();

            // Threads. The constraint is added below, not here.
            $table->ulid('parent_message_id')->nullable();

            $table->timestamp('edited_at')->nullable();

            // Soft delete on purpose: a removed message must stop rendering
            // without vanishing from the history any future response-speed
            // metric is computed over.
            $table->softDeletes();
            $table->timestamps();

            // ULIDs sort lexicographically by time, so this index serves both
            // chronological ordering and cursor pagination.
            $table->index(['conversation_id', 'id']);
            $table->index(['participant_id', 'created_at']);
        });

        // The self-referential foreign key has to wait until the table exists.
        // Laravel emits `->primary()` as its own ALTER statement, and inside
        // create() the foreign key ALTER can be ordered ahead of it — leaving
        // Postgres with no unique constraint on chat_messages.id to reference.
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreign('parent_message_id')
                ->references('id')
                ->on('chat_messages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
