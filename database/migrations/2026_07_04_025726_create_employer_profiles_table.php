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
        Schema::create('employer_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('industry');
            $table->string('country', 2);
            $table->string('city');
            $table->string('website')->nullable();
            // Verification state is binary: null is unverified, set is
            // verified. There is no rejected and no pending company state —
            // "not looked at yet" and "looked at and refused" are the same
            // thing to every read path.
            $table->timestamp('verified_at')->nullable();
            $table->foreignUlid('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            // That the employer asked, not a review state machine. Sorts the
            // Admingo queue and supplies the "waiting N days" number.
            $table->timestamp('verification_requested_at')->nullable();
            // The publish batch verification kicked off, so Admingo can poll
            // its progress. Operational state on a domain table, accepted over
            // a table written once per verification.
            $table->string('publish_batch_id')->nullable();
            $table->timestamps();

            // The Admingo queue: unverified profiles, requesters first.
            $table->index(['verified_at', 'verification_requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employer_profiles');
    }
};
