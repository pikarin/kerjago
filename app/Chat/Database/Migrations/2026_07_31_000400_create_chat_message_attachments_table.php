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
        Schema::create('chat_message_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('message_id')
                ->constrained('chat_messages')
                ->cascadeOnDelete();

            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            // foreignUlid()->constrained() does not create a supporting index on
            // Postgres. Without it, loading a message's attachments and every
            // ON DELETE CASCADE from chat_messages sequentially scan this table.
            // The other chat tables escape this only because their foreign key
            // is the leading column of a composite index.
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_attachments');
    }
};
