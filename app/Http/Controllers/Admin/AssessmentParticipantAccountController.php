<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParticipantAccountRequest;
use App\Http\Requests\Admin\UpdateParticipantAccountRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentParticipantAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $searchPattern = '%'.mb_strtolower($search).'%';
        $source = $request->query('source');
        $participantsQuery = User::query()
            ->where('role', User::ROLE_PESERTA_ASSESSMENT)
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested
                ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern])
                ->orWhereRaw('LOWER(employee_number) LIKE ?', [$searchPattern])))
            ->when(in_array($source, ['manual', 'hris'], true), fn ($query) => $query->where('identity_source', $source));

        return view('pages.admin.participants.index', [
            'title' => 'Peserta Assessment',
            'participants' => (clone $participantsQuery)->withCount('assessmentParticipations')->orderBy('name')->orderBy('id')->paginate(15)->withQueryString(),
            'participantMetrics' => [
                'total' => User::where('role', User::ROLE_PESERTA_ASSESSMENT)->count(),
                'hris' => User::where('role', User::ROLE_PESERTA_ASSESSMENT)->where('identity_source', 'hris')->count(),
                'manual' => User::where('role', User::ROLE_PESERTA_ASSESSMENT)->where('identity_source', 'manual')->count(),
            ],
            'hrisConfigured' => filled(config('services.hris.base_url')) && filled(config('services.hris.token')),
            'search' => $search,
            'source' => $source,
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.participants.create', ['title' => 'Tambah Peserta Assessment']);
    }

    public function store(StoreParticipantAccountRequest $request): RedirectResponse
    {
        $participant = User::create([...$request->validated(), 'role' => User::ROLE_PESERTA_ASSESSMENT, 'identity_source' => 'manual']);
        AuditLogger::record($request, 'participant.created', $participant, ['email' => $participant->email]);

        return to_route('admin.participants.index')->with('success', 'Akun peserta berhasil dibuat.');
    }

    public function edit(User $participant): View
    {
        abort_unless($participant->hasRole(User::ROLE_PESERTA_ASSESSMENT), 404);

        return view('pages.admin.participants.edit', ['title' => 'Edit Peserta Assessment', 'managedUser' => $participant]);
    }

    public function update(UpdateParticipantAccountRequest $request, User $participant): RedirectResponse
    {
        abort_unless($participant->hasRole(User::ROLE_PESERTA_ASSESSMENT), 404);
        $data = $request->validated();
        if ($participant->identity_source === 'hris') {
            unset($data['name'], $data['email'], $data['employee_number']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $changedFields = array_keys(array_diff_assoc($data, $participant->only(array_keys($data))));
        $participant->update([...$data, 'role' => User::ROLE_PESERTA_ASSESSMENT]);
        AuditLogger::record($request, 'participant.updated', $participant, ['changed_fields' => array_values(array_diff($changedFields, ['password']))]);

        return to_route('admin.participants.index')->with('success', 'Akun peserta berhasil diperbarui.');
    }

    public function destroy(Request $request, User $participant): RedirectResponse
    {
        abort_unless($participant->hasRole(User::ROLE_PESERTA_ASSESSMENT), 404);

        if ($participant->assessmentParticipations()->exists()) {
            return back()->with('error', 'Peserta tidak dapat dihapus karena sudah terdaftar dalam Program Assessment.');
        }

        AuditLogger::record($request, 'participant.deleted', $participant, [
            'name' => $participant->name,
            'email' => $participant->email,
        ]);
        $participant->delete();

        return to_route('admin.participants.index')->with('success', 'Akun peserta berhasil dihapus.');
    }
}
