<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Shipment;
use App\Models\User;
use App\Handlers\AuditHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function users(Request $request): Response
    {
        $search = $request->query('search');

        $query = User::withCount(['stores', 'shipments'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        $stats = [
            'total_users' => User::count(),
            'active_umkm' => User::where('role', 'umkm')->where('is_active', true)->count(),
            'total_shipments' => Shipment::count(),
            'total_volume' => (int) Shipment::whereNotIn('status', ['pending_payment', 'cancelled'])->sum('total_amount'),
        ];

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Anda tidak dapat menonaktifkan akun sendiri!',
            ]);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Akun {$user->name} berhasil {$statusText}!",
        ]);
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'status' => ['required', 'boolean'],
        ]);

        $targetIds = array_diff($validated['user_ids'], [$request->user()->id]);

        $users = User::whereIn('id', $targetIds)->get();
        foreach ($users as $user) {
            $user->update([
                'is_active' => $validated['status'],
            ]);
        }

        $count = count($targetIds);
        $statusText = $validated['status'] ? 'diaktifkan' : 'dinonaktifkan';

        if (class_exists(AuditHandler::class) && method_exists(AuditHandler::class, 'log')) {
            AuditHandler::log(
                'bulk_updated',
                'Bulk Ubah Status User',
                'User',
                null,
                null,
                ['user_ids' => $targetIds, 'is_active' => $validated['status'], 'count' => $count]
            );
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Sebanyak {$count} akun pengguna berhasil {$statusText}!",
        ]);
    }

    public function transactions(Request $request): Response
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $dates = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_preset' => ['nullable', 'string', 'max:20'],
        ]);
        $dateFrom = $dates['date_from'] ?? null;
        $dateTo = $dates['date_to'] ?? null;

        $query = Shipment::with(['user:id,name,email', 'store:id,name'])->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Transactions', [
            'transactions' => $transactions,
            'filters' => [
                'status' => $status ?? 'all',
                'search' => $search ?? '',
                'date_preset' => $dates['date_preset'] ?? 'all',
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }

    public function exportAllShipments(Request $request): JsonResponse
    {
        if ($request->boolean('no_filter')) {
            $transactions = Shipment::with(['user:id,name,email', 'store:id,name'])->latest()->get();

            return response()->json(['transactions' => $transactions]);
        }

        $status = $request->query('status');
        $search = $request->query('search');

        $dates = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_preset' => ['nullable', 'string', 'max:20'],
        ]);
        $dateFrom = $dates['date_from'] ?? null;
        $dateTo = $dates['date_to'] ?? null;

        $query = Shipment::with(['user:id,name,email', 'store:id,name'])->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->get();

        return response()->json(['transactions' => $transactions]);
    }

    public function auditLogs(Request $request): Response
    {
        $event = $request->query('event');
        $modelType = $request->query('model');
        $search = $request->query('search');

        $dates = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_preset' => ['nullable', 'string', 'max:20'],
        ]);
        $dateFrom = $dates['date_from'] ?? null;
        $dateTo = $dates['date_to'] ?? null;

        $query = AuditLog::with([
            'user:id,name,email,role',
            'auditable',
        ])->latest('id');

        if ($event && $event !== 'all') {
            $query->where('event', $event);
        }

        if ($modelType && $modelType !== 'all') {
            $query->where('auditable_type', $modelType);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('auditable_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $logs->getCollection()->transform(function ($log) {
            $label = match ($log->auditable_type) {
                'Shipment' => $log->auditable ? $log->auditable->order_number : (string) $log->auditable_id,
                'Store' => $log->auditable ? $log->auditable->name : (string) $log->auditable_id,
                'User' => $log->auditable ? $log->auditable->name : (string) $log->auditable_id,
                default => (string) $log->auditable_id,
            };

            return [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                    'role' => $log->user->role,
                ] : null,
                'event' => $log->event,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'auditable_label' => $label,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'old_formatted' => AuditHandler::format($log->old_values),
                'new_formatted' => AuditHandler::format($log->new_values),
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ];
        });

        $stats = [
            'total' => AuditLog::count(),
            'created_count' => AuditLog::where('event', 'created')->count(),
            'updated_count' => AuditLog::where('event', 'updated')->count(),
            'deleted_count' => AuditLog::where('event', 'deleted')->count(),
        ];

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => [
                'event' => $event ?? 'all',
                'model' => $modelType ?? 'all',
                'search' => $search ?? '',
                'date_preset' => $dates['date_preset'] ?? 'all',
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }
}
