<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit\AuditEventCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SystemAuditController extends Controller
{
    public function index(Request $request): View
    {
        $lookbackDays = (int) config('audit.filter_lookback_days', 90);
        $lookbackFrom = Carbon::now()->subDays($lookbackDays);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('module'), fn ($query) => $query->where('module', $request->string('module')))
            ->when($request->filled('area'), fn ($query) => $query->where('area', $request->string('area')))
            ->when($request->filled('event_type'), fn ($query) => $query->where('event_type', $request->string('event_type')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when(
                $request->filled('date_from'),
                fn ($query) => $query->where('created_at', '>=', Carbon::parse($request->string('date_from'))->startOfDay())
            )
            ->when(
                $request->filled('date_to'),
                fn ($query) => $query->where('created_at', '<=', Carbon::parse($request->string('date_to'))->endOfDay())
            )
            ->when(! $request->boolean('show_info'), function ($query): void {
                foreach (AuditEventCatalog::globalUiExcludedEventTypes() as $excluded) {
                    $query->whereNot(function ($inner) use ($excluded): void {
                        $inner->where('event_type', $excluded['event_type'])
                            ->where('action', $excluded['action']);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $filterBase = AuditLog::query()->where('created_at', '>=', $lookbackFrom);

        return view('admin.audit.index', [
            'logs' => $logs,
            'modules' => (clone $filterBase)->select('module')->distinct()->orderBy('module')->pluck('module'),
            'areas' => (clone $filterBase)->whereNotNull('area')->select('area')->distinct()->orderBy('area')->pluck('area'),
            'eventTypes' => (clone $filterBase)->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type'),
            'actions' => (clone $filterBase)->select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()
                ->whereIn('id', (clone $filterBase)->whereNotNull('user_id')->select('user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name']),
            'moduleLabels' => collect(config('audit.modules', []))->mapWithKeys(
                fn (array $definition, string $key) => [$key => $definition['label'] ?? $key]
            ),
            'areaLabels' => config('access.areas', []),
            'lookbackDays' => $lookbackDays,
        ]);
    }
}
