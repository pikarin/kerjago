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
        Schema::create('educations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('jobseeker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('institution');
            $table->string('field_of_study')->nullable();
            $table->string('level')->nullable();

            // Nullable because candidates routinely omit them; the UI renders
            // an unstated date as "Unspecified" rather than rejecting the row.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['jobseeker_profile_id', 'sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educations');
    }
};
