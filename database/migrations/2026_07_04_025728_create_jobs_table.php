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
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['status', 'location_country']);
            $table->index(['currency', 'salary_min', 'salary_max']);
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
