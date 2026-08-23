<?php

namespace App\Http\Controllers\Admin\Pengaturan;

use App\Exports\LogExport;
use App\Http\Controllers\Controller;
use App\Models\Auth\Activity;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with([
            'causer' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    User::class => ['roles'],
                ]);
            },
        ])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhereHasMorph('causer', [User::class, Pendaftar::class], function ($causerQuery, $type) use ($search) {
                        if ($type === User::class) {
                            $causerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('roles', function ($q3) use ($search) {
                                    $q3->where('name', 'like', "%{$search}%");
                                });
                        } elseif ($type === Pendaftar::class) {
                            $causerQuery->where('nama', 'like', "%{$search}%")
                                ->orWhere('nik', 'like', "%{$search}%")
                                ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                                ->orWhere('nomor_hp', 'like', "%{$search}%");
                        }
                    });
            });
        }

        if ($request->filled('module')) {
            $query->where('log_name', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('event', $request->action);
        }

        if ($request->filled('role')) {
            $role = $request->role;
            if (strcasecmp($role, 'pendaftar') === 0 || strcasecmp($role, 'calon santri') === 0) {
                $query->where('causer_type', Pendaftar::class);
            } else {
                $query->whereHasMorph('causer', [User::class], function ($q) use ($role) {
                    $q->whereHas('roles', function ($rq) use ($role) {
                        $rq->where('name', $role);
                    });
                });
            }
        }

        if ($request->filled('date_start') && $request->filled('date_end')) {
            $query->whereBetween('created_at', [$request->date_start.' 00:00:00', $request->date_end.' 23:59:59']);
        }

        $limit = $request->input('limit', 5);
        $logs = $query->paginate($limit)->withQueryString();

        // Get unique log names and events for dropdowns
        $modules = Activity::select('log_name')->distinct()->whereNotNull('log_name')->pluck('log_name');
        $events = Activity::select('event')->distinct()->whereNotNull('event')->pluck('event');
        $roles = Role::pluck('name')->toArray();
        if (! in_array('Pendaftar', $roles)) {
            $roles[] = 'Pendaftar';
        }

        return Inertia::render('Admin/Pengaturan/LogSystem/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'module', 'action', 'date_start', 'date_end', 'role']),
            'modules' => $modules,
            'events' => $events,
            'roles' => $roles,
        ]);
    }

    public function show(string $id)
    {
        $log = Activity::with([
            'causer' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    User::class => ['roles'],
                ]);
            },
        ])->findOrFail($id);

        return Inertia::render('Admin/Pengaturan/LogSystem/Show', [
            'log' => $log,
        ]);
    }

    public function destroy(string $id, Request $request)
    {
        $log = Activity::findOrFail($id);
        $logData = $log->toArray();
        $log->delete();

        activity()
            ->useLog('Log_Aktivitas')
            ->event('deleted')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old' => ['id' => $id, 'log_name' => $logData['log_name'], 'event' => $logData['event']],
            ])
            ->log('Menghapus log aktivitas secara spesifik');

        return back()->with('success', 'Log aktivitas berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:activity_log,id',
        ]);

        $count = count($request->ids);
        Activity::whereIn('id', $request->ids)->delete();

        activity()
            ->useLog('Log_Aktivitas')
            ->event('deleted')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old' => ['deleted_count' => $count, 'ids' => $request->ids],
            ])
            ->log("Menghapus {$count} log aktivitas terpilih");

        return back()->with('success', $count.' log aktivitas berhasil dihapus.');
    }

    public function clear(Request $request)
    {
        $count = Activity::count();
        Activity::truncate();

        // Note: Truncate removes everything including the new log we might want to write.
        // But since we write it after truncate, it will be the only log remaining.
        activity()
            ->useLog('Log_Aktivitas')
            ->event('deleted')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old' => ['deleted_count' => $count],
            ])
            ->log('Mengosongkan semua log aktivitas');

        return back()->with('success', 'Semua log aktivitas berhasil dikosongkan.');
    }

    public function export(Request $request)
    {
        $query = Activity::with([
            'causer' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    User::class => ['roles'],
                ]);
            },
        ])->latest();

        // Apply same filters as index if requested
        if ($request->filled('ids')) {
            // Export only selected
            // Parse comma separated ids if it comes as string (it might be sent via GET request parameter array or string)
            $ids = is_string($request->ids) ? explode(',', $request->ids) : $request->ids;
            $query->whereIn('id', $ids);
        } else {
            // Export all matching filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('log_name', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class, Pendaftar::class], function ($causerQuery, $type) use ($search) {
                            if ($type === User::class) {
                                $causerQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhereHas('roles', function ($q3) use ($search) {
                                        $q3->where('name', 'like', "%{$search}%");
                                    });
                            } elseif ($type === Pendaftar::class) {
                                $causerQuery->where('nama', 'like', "%{$search}%")
                                    ->orWhere('nik', 'like', "%{$search}%")
                                    ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                                    ->orWhere('nomor_hp', 'like', "%{$search}%");
                            }
                        });
                });
            }
            if ($request->filled('module')) {
                $query->where('log_name', $request->module);
            }
            if ($request->filled('action')) {
                $query->where('event', $request->action);
            }
            if ($request->filled('role')) {
                $role = $request->role;
                if (strcasecmp($role, 'pendaftar') === 0 || strcasecmp($role, 'calon santri') === 0) {
                    $query->where('causer_type', Pendaftar::class);
                } else {
                    $query->whereHasMorph('causer', [User::class], function ($q) use ($role) {
                        $q->whereHas('roles', function ($rq) use ($role) {
                            $rq->where('name', $role);
                        });
                    });
                }
            }
            if ($request->filled('date_start') && $request->filled('date_end')) {
                $query->whereBetween('created_at', [$request->date_start.' 00:00:00', $request->date_end.' 23:59:59']);
            }
        }

        $filename = 'activity-log-'.date('YmdHis').'.xlsx';

        activity()
            ->useLog('Log_Aktivitas')
            ->event('exported')
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attributes' => [
                    'filters' => $request->all(),
                    'filename' => $filename,
                ],
            ])
            ->log('Mengekspor data log aktivitas ke Excel');

        return Excel::download(new LogExport($query), $filename);
    }
}
