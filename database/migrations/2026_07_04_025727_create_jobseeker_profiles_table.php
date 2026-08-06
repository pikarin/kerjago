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
        Schema::create('jobseeker_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('current_title');
            $table->string('current_company')->nullable();
            $table->string('preferred_job_title')->nullable();
            $table->text('summary')->nullable();
            $table->json('skills');
            $table->unsignedTinyInteger('experience_years');
            $table->string('country', 2);
            $table->string('state')->nullable();
            $table->string('city');

            // Where they want to work, as opposed to country/state/city
            // above, which is where they are.
            $table->string('preferred_country', 2)->nullable();
            $table->string('preferred_state')->nullable();
            $table->string('preferred_city')->nullable();

            // Whole major currency units, per ADR 0005.
            $table->unsignedBigInteger('expected_salary_min')->nullable();
            $table->unsignedBigInteger('expected_salary_max')->nullable();
            $table->string('expected_salary_currency', 3)->nullable();
            $table->string('expected_salary_period')->nullable();

            $table->string('availability')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();

            // Distinct from phone: the WhatsApp-reachable number is often
            // not the number on the CV.
            $table->string('whatsapp')->nullable();

            $table->string('avatar_path')->nullable();
            $table->string('resume_path')->nullable();

            // Denormalised engagement totals. No event log yet — the profile
            // shows counts only, never who did the viewing (ADR 0012).
            $table->timestamp('last_active_at')->nullable();
            $table->unsignedInteger('profile_views_count')->default(0);
            $table->unsignedInteger('resume_downloads_count')->default(0);
            $table->unsignedInteger('employer_actions_count')->default(0);

            $table->timestamps();

            $table->index(['country', 'city']);
            $table->index('experience_years');
            $table->index(['preferred_country', 'preferred_city']);
            $table->index('last_active_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobseeker_profiles');
    }
};
