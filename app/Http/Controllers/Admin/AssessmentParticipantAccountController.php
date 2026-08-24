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
    public function index(): View
    {
        return view('pages.admin.participants.index', [
            'title' => 'Peserta Assessment',
            'participants' => User::where('role', User::ROLE_PESERTA_ASSESSMENT)->withCount('assessmentParticipations')->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.participants.create', ['title' => 'Tambah Peserta Assessment']);
    }

    public function store(StoreParticipantAccountRequest $request): RedirectResponse
    {
        $participant = User::create([...$request->validated(), 'role' => User::ROLE_PESERTA_ASSESSMENT]);
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
