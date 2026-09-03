<?php

namespace Database\Seeders;

use App\Models\PapiDimension;
use App\Models\PsychologicalTest;
use Illuminate\Database\Seeder;

class PapiDimensionSeeder extends Seeder
{
    public function run(): void
    {
        PsychologicalTest::updateOrCreate(
            ['code' => 'PAPI_KOSTICK'],
            ['name' => 'PAPI Kostick', 'description' => 'Tes kepribadian kerja ipsatif dengan 90 pasangan pernyataan.', 'is_active' => true],
        );

        $dimensions = [
            ['G', 'role', 'G'], ['L', 'role', 'L'], ['I', 'role', 'I'], ['T', 'role', 'T'], ['V', 'role', 'V'],
            ['S', 'role', 'S'], ['R', 'role', 'R'], ['D', 'role', 'D'], ['C', 'role', 'C'], ['E', 'role', 'E'],
            ['N', 'need', 'N'], ['A', 'need', 'A'], ['P', 'need', 'P'], ['X', 'need', 'X'], ['B', 'need', 'B'],
            ['O', 'need', 'O'], ['Z', 'need', 'Z'], ['K', 'need', 'K'], ['F', 'need', 'F'], ['W', 'need', 'W'],
        ];

        foreach ($dimensions as $index => [$code, $category, $name]) {
            PapiDimension::updateOrCreate(
                ['code' => $code],
                ['category' => $category, 'name' => $name, 'display_order' => $index + 1, 'is_active' => true],
            );
        }
    }
}
