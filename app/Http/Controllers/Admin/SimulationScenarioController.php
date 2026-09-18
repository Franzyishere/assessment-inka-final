<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSimulationScenarioRequest;
use App\Http\Requests\Admin\UpdateSimulationScenarioRequest;
use App\Models\AssessmentParticipant;
use App\Models\SimulationScenario;
use App\Models\SimulationType;
use App\Support\SimulationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SimulationScenarioController extends Controller
{
    public function index(Request $request): View
    {
        $scenarios = SimulationCatalog::ensure($request->user()->id);
        $scenarios->each->load('materialPages');
        $simulationTypes = SimulationType::query()->where('is_active', true)->orderBy('sequence')->get();

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->query('search')));
            $matchingTypeIds = $simulationTypes->filter(fn ($type) => str_contains(mb_strtolower($type->name.' '.$type->description), $search))->pluck('id');
            $scenarios = $scenarios->filter(fn ($scenario) => $matchingTypeIds->contains($scenario->simulation_type_id)
                || str_contains(mb_strtolower($scenario->simulationThreePackageLabel() ?? ''), $search)
                || str_contains(mb_strtolower($scenario->title ?? ''), $search))->values();
            $simulationTypes = $simulationTypes->whereIn('id', $scenarios->pluck('simulation_type_id')->unique())->values();
        }

        return view('pages.admin.simulations.index', [
            'title' => 'Bank Simulasi',
            'simulationGroups' => $scenarios->groupBy('simulation_type_id'),
            'simulationTypes' => $simulationTypes,
            'assessmentCategories' => AssessmentParticipant::CATEGORIES,
        ]);
    }

    public function edit(SimulationScenario $simulationScenario): View
    {
        $simulationScenario->load('materialPages');

        return view('pages.admin.simulations.edit', [
            'title' => 'Edit Simulasi',
            'scenario' => $simulationScenario,
            'simulationTypes' => SimulationType::query()->where('is_active', true)->orderBy('sequence')->get(),
            'assessmentCategories' => AssessmentParticipant::CATEGORIES,
        ]);
    }

    public function update(UpdateSimulationScenarioRequest $request, SimulationScenario $simulationScenario): RedirectResponse
    {
        DB::transaction(function () use ($request, $simulationScenario) {
            $data = $request->safe()->except('material_pages');
            $type = $simulationScenario->type;
            $this->validatePdfMaterials($request, $type, $simulationScenario);
            $pages = $request->validated('material_pages', []);
            // A material already assigned to a program is immutable. Publish a new
            // catalog version while existing program links keep their original PDF.
            if ($simulationScenario->usesSharedSimulationThreeMaterial() && $simulationScenario->programSimulations()->exists()) {
                $original = $simulationScenario;
                $simulationScenario = $original->replicate();
                $simulationScenario->code = 'SYSTEM-'.Str::ulid();
                $simulationScenario->created_by = $request->user()->id;
                $simulationScenario->save();
                foreach ($pages as $index => &$page) {
                    $previous = $original->materialPages()->find($page['id'] ?? null);
                    $page['id'] = null;
                    if ($previous && ! $request->hasFile("material_pages.{$index}.attachment")) {
                        $copy = $previous->replicate();
                        $copy->simulation_scenario_id = $simulationScenario->id;
                        $copy->attachment_path = 'simulation-materials/'.$simulationScenario->id.'/'.Str::uuid().'.pdf';
                        abort_unless(Storage::disk('local')->copy($previous->attachment_path, $copy->attachment_path), 500, 'Materi gagal disalin. Silakan coba kembali.');
                        $copy->save();
                        $page['id'] = $copy->id;
                    }
                }
                unset($page);
            }
            $simulationScenario->update([
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'title' => $type->name,
                'status' => 'active',
            ]);
            $this->syncMaterialPages($request, $simulationScenario, $pages);
        });

        return to_route('admin.simulations.index')->with('success', 'Materi katalog berhasil disimpan. Program yang sudah memakai versi sebelumnya tetap menggunakan materinya; simpan pengaturan program yang belum dimulai untuk memakai versi terbaru.');
    }

    private function syncMaterialPages(StoreSimulationScenarioRequest $request, SimulationScenario $scenario, array $pages): void
    {
        $retainedIds = [];

        foreach (array_values($pages) as $index => $page) {
            $attributes = [
                'title' => $page['title'] ?: 'Materi '.($index + 1),
                'content' => $page['content'] ?? null,
                'page_order' => $index + 1,
                'is_required' => $scenario->usesSharedSimulationThreeMaterial() || (bool) ($page['is_required'] ?? true),
            ];
            $file = $request->file("material_pages.{$index}.attachment");
            $existing = isset($page['id']) ? $scenario->materialPages()->find($page['id']) : null;
            if ($file) {
                if ($existing?->attachment_path) {
                    Storage::disk('local')->delete($existing->attachment_path);
                }
                $path = $file->storeAs('simulation-materials/'.$scenario->id, Str::uuid().'.pdf', 'local');
                $attributes += [
                    'attachment_path' => $path,
                    'attachment_name' => $file->getClientOriginalName(),
                    'attachment_mime_type' => 'application/pdf',
                    'attachment_size' => $file->getSize(),
                ];
            }

            $materialPage = $scenario->materialPages()->updateOrCreate(
                ['id' => $page['id'] ?? null],
                $attributes,
            );
            $retainedIds[] = $materialPage->id;
        }

        $removed = $scenario->materialPages()->whereNotIn('id', $retainedIds)->get();
        foreach ($removed as $material) {
            if ($material->attachment_path) {
                Storage::disk('local')->delete($material->attachment_path);
            }
            $material->delete();
        }
    }

    private function validatePdfMaterials(StoreSimulationScenarioRequest $request, SimulationType $type, ?SimulationScenario $scenario = null): void
    {
        if (! in_array($type->delivery_mode, ['multi_page_response', 'case_response', 'assessor_observation'], true)) {
            return;
        }

        $pages = $request->validated('material_pages', []);
        if ($scenario?->usesSharedSimulationThreeMaterial() && count($pages) !== 1) {
            throw ValidationException::withMessages(['material_pages' => 'Critical Incident dan In-Tray masing-masing harus memiliki tepat satu materi PDF.']);
        }
        if ($type->delivery_mode === 'multi_page_response' && count($pages) !== 1) {
            throw ValidationException::withMessages(['material_pages' => 'Simulasi 1 harus memiliki tepat satu materi PDF.']);
        }
        if ($type->delivery_mode === 'assessor_observation' && count($pages) !== 1) {
            throw ValidationException::withMessages(['material_pages' => 'LGD harus memiliki tepat satu file PDF instruksi.']);
        }
        if ($type->delivery_mode === 'case_response' && count($pages) < 1) {
            throw ValidationException::withMessages(['material_pages' => 'Simulasi 3 harus memiliki minimal satu materi PDF.']);
        }

        foreach (array_values($pages) as $index => $page) {
            $hasExisting = ! empty($page['id']) && $scenario?->materialPages()->whereKey($page['id'])->whereNotNull('attachment_path')->exists();
            if (! $request->hasFile("material_pages.{$index}.attachment") && ! $hasExisting) {
                throw ValidationException::withMessages(["material_pages.{$index}.attachment" => 'File PDF materi wajib diunggah.']);
            }
        }
    }
}
