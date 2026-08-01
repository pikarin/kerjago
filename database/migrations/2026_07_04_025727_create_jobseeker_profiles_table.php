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
            $table->string('preferred_job_title')->nullable();
            $table->json('skills');
            $table->unsignedTinyInteger('experience_years');
            $table->string('country', 2);
            $table->string('city');

            // Where they want to work, as opposed to country/city above,
            // which is where they are.
            $table->string('preferred_country', 2)->nullable();
            $table->string('preferred_city')->nullable();

            $table->string('availability')->nullable();
            $table->json('languages')->nullable();
            $table->string('gender')->nullable();
            $table->string('education_level')->nullable();
            $table->string('phone')->nullable();
            $table->string('resume_path')->nullable();
            $table->timestamps();

            $table->index(['country', 'city']);
            $table->index('experience_years');
            $table->index(['preferred_country', 'preferred_city']);
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
