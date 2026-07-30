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
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Chat-internal foreign key, so it is a real constraint.
            $table->foreignUlid('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();

            // Opaque host identifier, no constraint. See the note in the
            // conversations migration.
            $table->string('participant_id');

            // last_read_at is what makes read-state analytics possible later:
            // a read receipt carrying a timestamp. Response-speed metrics are
            // deferred, but they cannot be computed retroactively from data
            // that was never recorded.
            $table->ulid('last_read_message_id')->nullable();
            $table->timestamp('last_read_at')->nullable();

            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'participant_id']);
            $table->index(['participant_id', 'left_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
