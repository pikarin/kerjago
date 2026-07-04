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
        Schema::create('applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('jobseeker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('submitted');
            $table->string('resume_path')->nullable();
            $table->text('cover_note')->nullable();
            $table->timestamps();

            $table->unique(['job_id', 'jobseeker_profile_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
