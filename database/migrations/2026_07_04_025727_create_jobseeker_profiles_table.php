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
            $table->json('skills');
            $table->unsignedTinyInteger('experience_years');
            $table->string('country', 2);
            $table->string('city');
            $table->string('phone')->nullable();
            $table->string('resume_path')->nullable();
            $table->timestamps();

            $table->index(['country', 'city']);
            $table->index('experience_years');
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
