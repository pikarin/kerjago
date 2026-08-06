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
        Schema::create('jobseeker_languages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('jobseeker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language');
            $table->string('proficiency');
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['jobseeker_profile_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobseeker_languages');
    }
};
