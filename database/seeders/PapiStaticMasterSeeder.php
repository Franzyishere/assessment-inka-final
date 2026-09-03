<?php

namespace Database\Seeders;

use App\Models\PapiDimension;
use App\Models\PsychologicalTest;
use App\Models\PsychologicalTestVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PapiStaticMasterSeeder extends Seeder
{
    public function run(): void
    {
        $snapshot = $this->decodeData('papi_questions.base64');
        $scoringKey = $this->decodeData('papi_scoring_key.base64');
        if (count($snapshot[0] ?? []) !== 90 || count($snapshot[1] ?? []) !== 180 || count($scoringKey) !== 90) {
            throw new RuntimeException('Snapshot master PAPI statis tidak lengkap.');
        }

        DB::transaction(function () use ($snapshot, $scoringKey) {
            $test = PsychologicalTest::updateOrCreate(['code' => 'PAPI_KOSTICK'], ['name' => 'PAPI Kostick', 'description' => 'Master psikotes PAPI tetap INKA.', 'is_active' => true]);
            $version = $test->versions()->where('version', 'INKA-STATIC')->first()
                ?? $test->versions()->withCount('questions')->get()->firstWhere('questions_count', 90)
                ?? new PsychologicalTestVersion(['psychological_test_id' => $test->id]);
            $version->fill(['version' => 'INKA-STATIC', 'item_count' => 90, 'expected_role_total' => 45, 'expected_need_total' => 45, 'status' => 'published', 'validated_at' => $version->validated_at ?? now(), 'published_at' => $version->published_at ?? now()])->save();

            if ($version->questions()->count() !== 90) {
                $version->questions()->delete();
                $questions = [];
                foreach ($snapshot[0] as $data) {
                    $questions[$data['id']] = $version->questions()->create(['number' => $data['number'], 'is_required' => true]);
                }
                foreach ($snapshot[1] as $data) {
                    $questions[$data['psychological_question_id']]->options()->create(['code' => $data['code'], 'statement' => $data['statement'], 'display_order' => $data['display_order']]);
                }
            }

            $dimensions = PapiDimension::query()->pluck('id', 'code');
            foreach ($version->questions()->with('options')->get() as $question) {
                foreach ($question->options as $option) {
                    $code = $scoringKey[(string) $question->number][$option->code] ?? null;
                    if (! $code || ! isset($dimensions[$code])) {
                        throw new RuntimeException("Mapping PAPI nomor {$question->number} pilihan {$option->code} tidak valid.");
                    }
                    $option->scoringRule()->updateOrCreate([], ['papi_dimension_id' => $dimensions[$code], 'weight' => 1]);
                }
            }
        });
    }

    private function decodeData(string $filename): array
    {
        $encoded = trim((string) file_get_contents(database_path('data/'.$filename)));
        $decoded = json_decode((string) base64_decode($encoded, true), true);
        if (! is_array($decoded)) {
            throw new RuntimeException("Data statis {$filename} tidak dapat dibaca.");
        }

        return $decoded;
    }
}
