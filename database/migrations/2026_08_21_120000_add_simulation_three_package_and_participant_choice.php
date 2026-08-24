<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulation_scenarios', function (Blueprint $table) {
            $table->string('simulation_package')->nullable()->after('assessment_category')->index();
        });

        Schema::table('assessment_participants', function (Blueprint $table) {
            $table->string('simulation_three_choice')->nullable()->after('assessment_category')->index();
            $table->timestamp('simulation_three_chosen_at')->nullable()->after('simulation_three_choice');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_participants', function (Blueprint $table) {
            $table->dropColumn(['simulation_three_choice', 'simulation_three_chosen_at']);
        });

        Schema::table('simulation_scenarios', function (Blueprint $table) {
            $table->dropColumn('simulation_package');
        });
    }
};
