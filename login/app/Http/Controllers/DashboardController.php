<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SecurityLog;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Mostrar el panel de control principal (Dashboard).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $data = [];

        if ($user->role === 'admin') {
            $data = $this->getAdminData($request);
        } else {
            $data = $this->getUserData($user);
        }

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Obtener datos para el administrador.
     */
    private function getAdminData(Request $request): array
    {
        $search = $request->input('search');
        
        // --- Usuarios paginados ---
        $usersQuery = User::select('id', 'name', 'email', 'role', 'two_factor_confirmed_at', 'email_verified_at');
        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $paginatedUsers = $usersQuery->paginate(8)->withQueryString();

        // --- Bitácora paginada con filtros ---
        $logsPerPage = $request->input('logs_per_page', 20);
        $logsQuery = SecurityLog::latest();
        
        if ($request->filled('log_ip')) {
            $logsQuery->where('ip_address', 'like', '%' . $request->input('log_ip') . '%');
        }
        if ($request->filled('log_email')) {
            $logsQuery->where('email', 'like', '%' . $request->input('log_email') . '%');
        }
        if ($request->filled('log_event')) {
            $logsQuery->where('event', $request->input('log_event'));
        }
        if ($request->filled('log_status')) {
            $logsQuery->where('status', $request->input('log_status'));
        }

        $paginatedLogs = $logsQuery->paginate($logsPerPage, ['*'], 'logs_page')->withQueryString();

        // Estadísticas de Bloqueo
        $totalLogs = SecurityLog::count();
        $blockedLogsCount = SecurityLog::where('status', 'like', '%Bloqueado%')
            ->orWhere('status', 'Rechazado')
            ->count();
        $blockRate = $totalLogs > 0 ? round(($blockedLogsCount / $totalLogs) * 100, 2) : 0;
        
        $lastBlock = SecurityLog::where('status', 'like', '%Bloqueado%')
            ->orWhere('status', 'Rechazado')
            ->latest()
            ->first();

        return [
            'stats' => [
                'blockRate' => $blockRate,
                'lastBlockIp' => $lastBlock ? ($lastBlock->ip_address ?? 'Desconocida') : 'Ninguno',
            ],
            'users' => [
                'data' => $paginatedUsers->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->role,
                        'mfa' => $u->two_factor_confirmed_at ? 'Activo' : 'Inactivo',
                        'email_otp' => in_array($u->role, ['admin', 'user']) ? 'Activo' : 'Inactivo',
                    ];
                })->all(),
                'links' => $paginatedUsers->linkCollection()->toArray(),
                'current_page' => $paginatedUsers->currentPage(),
                'last_page' => $paginatedUsers->lastPage(),
                'total' => $paginatedUsers->total(),
                'per_page' => $paginatedUsers->perPage(),
            ],
            'filters' => [
                'search' => $search,
            ],
            'securityLogs' => [
                'data' => $paginatedLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'ip' => $log->ip_address ?? '127.0.0.1',
                        'user_agent' => $log->user_agent ?? 'Desconocido',
                        'email' => $log->email ?? 'N/A',
                        'event' => $log->event,
                        'status' => $log->status,
                        'time' => $log->created_at->diffForHumans(),
                    ];
                })->all(),
                'links' => $paginatedLogs->linkCollection()->toArray(),
                'current_page' => $paginatedLogs->currentPage(),
                'total' => $paginatedLogs->total(),
                'per_page' => $paginatedLogs->perPage(),
            ],
            'logOptions' => [
                'events' => SecurityLog::select('event')->distinct()->pluck('event'),
                'statuses' => SecurityLog::select('status')->distinct()->pluck('status'),
            ],
            'logFilters' => [
                'ip' => $request->input('log_ip', ''),
                'email' => $request->input('log_email', ''),
                'event' => $request->input('log_event', ''),
                'status' => $request->input('log_status', ''),
                'per_page' => $logsPerPage,
            ],
        ];
    }

    /**
     * Obtener datos para un usuario normal.
     */
    private function getUserData(User $user): array
    {
        $personalLogs = SecurityLog::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'device' => $log->event,
                    'location' => $log->ip_address ?? '127.0.0.1',
                    'user_agent' => $log->user_agent ?? 'Desconocido',
                    'status' => $log->status,
                    'time' => $log->created_at->diffForHumans(),
                ];
            });

        return [
            'personalLogs' => $personalLogs,
        ];
    }
}
