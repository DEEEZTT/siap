<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:190'], 'action' => ['nullable', 'string', 'max:60']]);

        $logs = ActivityLog::query()->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('q'), fn ($query) => $query->where('description', 'like', '%'.$request->string('q')->toString().'%'))
            ->latest('created_at')->latest('id')
            ->paginate(25)->withQueryString();

        $actions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('audit.index', compact('logs', 'actions'));
    }
}
