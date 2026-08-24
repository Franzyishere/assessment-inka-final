<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assessment_participants')
            ->where('assessment_category', 'promotion_specialist_principal')
            ->update([
                'assessment_category' => 'promotion_specialist_pratama',
            ]);

        DB::table('simulation_scenarios')
            ->where('assessment_category', 'promotion_specialist_principal')
            ->update([
                'assessment_category' => 'promotion_specialist_pratama',
            ]);
    }

    public function down(): void
    {
        DB::table('assessment_participants')
            ->where('assessment_category', 'promotion_specialist_pratama')
            ->update([
                'assessment_category' => 'promotion_specialist_principal',
            ]);

        DB::table('simulation_scenarios')
            ->where('assessment_category', 'promotion_specialist_pratama')
            ->update([
                'assessment_category' => 'promotion_specialist_principal',
            ]);
    }
};