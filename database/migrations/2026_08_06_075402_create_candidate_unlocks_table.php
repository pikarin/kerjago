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
        Schema::create('candidate_unlocks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('jobseeker_profile_id')->constrained()->cascadeOnDelete();
            // Which job's quota paid for this. Null once unlocks can be bought
            // outright or granted by staff, which is why it is not required.
            $table->foreignUlid('job_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->timestamp('expires_at');
            // Stamped by unlocks:expire once it has taken the employer out of
            // the pair's threads, so the sweep never reprocesses the same row.
            // Not a second source of truth for access — masking reads
            // expires_at alone.
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // One unlock per employer/candidate pair, enforced by the database
            // rather than by the apply-time transaction alone.
            $table->unique(['employer_profile_id', 'jobseeker_profile_id']);
            // The read path: "which of these profile ids are unlocked for me?"
            $table->index(['employer_profile_id', 'expires_at']);
            // The quota count for one job, and the unlocks:expire sweep.
            $table->index('job_id');
            // The sweep's working set: expired but not yet revoked.
            $table->index(['revoked_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_unlocks');
    }
};
