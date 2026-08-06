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
        Schema::create('jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('skills');
            $table->string('location_country', 2);
            $table->string('location_city');
            $table->unsignedBigInteger('salary_min');
            $table->unsignedBigInteger('salary_max');
            $table->string('currency', 3);
            $table->string('employment_type')->nullable();
            $table->string('work_arrangement')->nullable();
            $table->string('experience_level')->nullable();
            $table->string('education_level')->nullable();
            $table->string('status')->default('draft');
            // Publishing stamps both: expires_at is published_at + 45 days,
            // frozen at publish time so editing a live ad never moves it.
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Serves both scopeActive (status + not yet expired) and the
            // jobs:expire sweep, which reads the same two columns.
            $table->index(['status', 'expires_at']);
            $table->index(['status', 'location_country']);
            $table->index(['currency', 'salary_min', 'salary_max']);

            // One composite per facet, each led by status: search always
            // filters to published jobs first.
            $table->index(['status', 'employment_type']);
            $table->index(['status', 'work_arrangement']);
            $table->index(['status', 'experience_level']);
            $table->index(['status', 'education_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
