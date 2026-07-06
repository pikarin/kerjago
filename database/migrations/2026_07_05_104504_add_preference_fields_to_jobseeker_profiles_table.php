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
        Schema::table('jobseeker_profiles', function (Blueprint $table) {
            $table->string('preferred_job_title')->nullable()->after('current_title');
            $table->string('preferred_country', 2)->nullable()->after('city');
            $table->string('preferred_city')->nullable()->after('preferred_country');
            $table->string('availability')->nullable()->after('preferred_city');
            $table->json('languages')->nullable()->after('availability');
            $table->string('gender')->nullable()->after('languages');
            $table->string('education_level')->nullable()->after('gender');

            $table->index(['preferred_country', 'preferred_city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobseeker_profiles', function (Blueprint $table) {
            $table->dropIndex(['preferred_country', 'preferred_city']);

            $table->dropColumn([
                'preferred_job_title',
                'preferred_country',
                'preferred_city',
                'availability',
                'languages',
                'gender',
                'education_level',
            ]);
        });
    }
};
