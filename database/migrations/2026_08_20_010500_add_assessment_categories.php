<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulation_scenarios', function (Blueprint $table) {
            $table->string('assessment_category')->nullable()->after('simulation_type_id')->index();
        });

        Schema::table('assessment_participants', function (Blueprint $table) {
            $table->string('assessment_category')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_participants', fn (Blueprint $table) => $table->dropColumn('assessment_category'));
        Schema::table('simulation_scenarios', fn (Blueprint $table) => $table->dropColumn('assessment_category'));
    }
};
