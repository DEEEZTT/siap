<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const SORTABLE_COLUMNS = ['created_at', 'user_id', 'action', 'description', 'ip_address'];

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'action' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $logs = ActivityLog::query()->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('q'), fn ($query) => $query->where('description', 'like', '%'.$request->string('q')->toString().'%'))
            ->when($request->filled('sort'), function ($query) use ($request): void {
                $sort = $request->string('sort')->toString();
                $direction = $request->input('direction', 'asc');
                if ($sort === 'user_id') {
                    $query->orderBy(\App\Models\User::query()->select('name')->whereColumn('users.id', 'activity_logs.user_id')->limit(1), $direction);
                } else {
                    $query->orderBy($sort, $direction);
                }
                $query->orderByDesc('id');
            }, fn ($query) => $query->latest('created_at')->latest('id'))
            ->paginate(25)->withQueryString();

        $actions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('audit.index', compact('logs', 'actions'));
    }
}
