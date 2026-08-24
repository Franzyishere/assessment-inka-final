<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_program_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simulation_scenario_id')->constrained()->restrictOnDelete();
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();

            $table->unique(['assessment_program_id', 'simulation_scenario_id'], 'program_scenario_unique');
        });

        Schema::create('assessment_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('assigned')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_program_id', 'user_id'], 'program_participant_unique');
        });

        Schema::create('assessor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_program_simulation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_program_simulation_id', 'assessor_id'], 'simulation_assessor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_assignments');
        Schema::dropIfExists('assessment_participants');
        Schema::dropIfExists('assessment_program_simulations');
    }
};
