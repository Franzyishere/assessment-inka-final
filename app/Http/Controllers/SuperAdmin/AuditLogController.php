<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->orderByDesc('occurred_at')->orderByDesc('id');
        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        return view('pages.super-admin.audit-logs.index', [
            'title' => 'Audit Log',
            'logs' => $query->paginate(20)->withQueryString(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
