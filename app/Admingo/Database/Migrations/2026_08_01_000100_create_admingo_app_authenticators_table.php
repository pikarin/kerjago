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
        Schema::create('admingo_app_authenticators', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Filament's default implementation puts these on `users`. They
            // live here instead so employers and jobseekers do not carry two
            // permanently-null columns, and so the domain migration stays
            // owned by the domain. See docs/adr/0011.
            //
            // Unlike App\Chat, Admingo is not extractable to its own service —
            // it is a UI over this database — so a real constraint is correct.
            $table->foreignUlid('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Both encrypted at the model. A row exists only while the staff
            // member has enrolled; disenrolling deletes it.
            $table->text('secret')->nullable();
            $table->text('recovery_codes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admingo_app_authenticators');
    }
};
