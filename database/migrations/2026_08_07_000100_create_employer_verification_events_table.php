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
        Schema::create('employer_verification_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('decision');
            // Who conferred it, not who typed it: `staff` today, `system` once
            // a purchase or package can. Mirrors candidate_unlocks.source.
            $table->string('source');
            // Null for anything the platform decided on its own.
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            // Internal. Staff write ticket numbers and fraud suspicions here,
            // so it must never reach the employer — which is why the message
            // they do see is a separate column.
            $table->text('reason')->nullable();
            $table->text('employer_message')->nullable();
            // Append-only: rows are never updated, so there is nothing to
            // stamp an updated_at with.
            $table->timestamp('created_at')->nullable();

            // The history table on the employer's Admingo page, newest first.
            $table->index(['employer_profile_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employer_verification_events');
    }
};
