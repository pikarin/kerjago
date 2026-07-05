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
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('employment_type')->nullable()->after('currency');
            $table->string('work_arrangement')->nullable()->after('employment_type');
            $table->string('experience_level')->nullable()->after('work_arrangement');
            $table->string('education_level')->nullable()->after('experience_level');

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
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['status', 'employment_type']);
            $table->dropIndex(['status', 'work_arrangement']);
            $table->dropIndex(['status', 'experience_level']);
            $table->dropIndex(['status', 'education_level']);

            $table->dropColumn(['employment_type', 'work_arrangement', 'experience_level', 'education_level']);
        });
    }
};
