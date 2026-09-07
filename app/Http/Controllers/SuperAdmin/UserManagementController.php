<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreUserRequest;
use App\Http\Requests\SuperAdmin\UpdateUserRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_strtolower(trim((string) $request->query('search')));
        $users = User::query()
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested
                ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(role) LIKE ?', ["%{$search}%"])))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return view('pages.super-admin.users.index', ['title' => 'Manajemen Pengguna', 'users' => $users]);
    }

    public function create(): View
    {
        return view('pages.super-admin.users.create', ['title' => 'Tambah Pengguna']);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        AuditLogger::record($request, 'user.created', $user, ['email' => $user->email, 'role' => $user->role]);

        return to_route('super-admin.users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('pages.super-admin.users.edit', ['title' => 'Edit Pengguna', 'managedUser' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if ($request->user()->is($user)) {
            $data['role'] = User::ROLE_SUPER_ADMIN;
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $changedFields = array_keys(array_diff_assoc($data, $user->only(array_keys($data))));
        $user->update($data);
        AuditLogger::record($request, 'user.updated', $user, ['changed_fields' => array_values(array_diff($changedFields, ['password']))]);

        return to_route('super-admin.users.index')->with('success', 'Akun pengguna berhasil diperbarui.');
    }
}
