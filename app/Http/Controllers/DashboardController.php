<?php

namespace App\Http\Controllers;

use App\Handlers\AuditHandler;
use App\Models\AuditLog;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->role === 'super_admin';

        $shipmentQuery = $isSuperAdmin ? Shipment::query() : $user->shipments();
        $metrics = $this->calculateMetrics($shipmentQuery);

        $roleData = $isSuperAdmin
            ? $this->getSuperAdminData($user, $metrics)
            : $this->getUmkmData($user);

        $trendData = $this->calculateSevenDayTrend(Carbon::now('Asia/Jakarta'), $shipmentQuery);

        $allShipments = (clone $shipmentQuery)->get([
            'id', 'store_id', 'order_number', 'status', 'recipient_name',
            'destination_area_name', 'destination_address', 'weight', 'item_category',
            'courier_code', 'courier_service_code', 'courier_service_name',
            'total_amount', 'waybill_number', 'tracking_history', 'updated_at', 'created_at',
        ]);

        $courierPerformance = $this->calculateCourierAnalytics($allShipments, $metrics['total']);
        $serviceBreakdown = $this->calculateServiceBreakdown($allShipments);
        $storePerformance = $this->calculateStorePerformance($isSuperAdmin, $user, $allShipments, $metrics['total']);
        $liveTrackingFeed = $this->calculateLiveTrackingFeed($allShipments);
        $topDestinations = $this->calculateTopDestinations($allShipments);

        $defaultStore = $roleData['defaultStore'];

        return Inertia::render('Dashboard', [
            'isSuperAdmin' => $isSuperAdmin,
            'metrics' => $metrics,
            'sevenDayTrend' => $trendData['trend'],
            'weeklyGrowth' => $trendData['weeklyGrowth'],
            'topDestinations' => $topDestinations,
            'courierPerformance' => $courierPerformance,
            'serviceBreakdown' => $serviceBreakdown,
            'storePerformance' => $storePerformance,
            'liveTrackingFeed' => $liveTrackingFeed,
            'recentShipments' => $roleData['recentShipments'],
            'walletStats' => $roleData['walletStats'],
            'defaultStore' => $defaultStore ? [
                'id' => $defaultStore->id,
                'name' => $defaultStore->name,
                'area_name' => $defaultStore->area_name,
                'postal_code' => $defaultStore->postal_code,
                'biteship_area_id' => $defaultStore->biteship_area_id,
            ] : null,
            'storesCount' => $roleData['storesCount'],
            'platformStats' => $roleData['platformStats'],
            'recentAuditLogs' => $roleData['recentAuditLogs'],
            'umkmList' => $roleData['umkmList'],
        ]);
    }

    /**
     * Hitung metrik ringkasan status pengiriman.
     *
     * @return array{total: int, unpaid: int, waiting_pickup: int, in_transit: int, delivered: int, total_volume: int}
     */
    private function calculateMetrics(Builder|HasMany $shipmentQuery): array
    {
        $totalCount = (clone $shipmentQuery)->count();
        $unpaidCount = (clone $shipmentQuery)->where('status', 'pending_payment')->count();
        $waitingPickupCount = (clone $shipmentQuery)->whereIn('status', ['paid', 'waiting_pickup', 'allocated', 'picking_up'])->count();
        $inTransitCount = (clone $shipmentQuery)->where('status', 'in_transit')->count();
        $deliveredCount = (clone $shipmentQuery)->where('status', 'delivered')->count();
        $totalVolume = (int) (clone $shipmentQuery)->whereNotIn('status', ['pending_payment', 'cancelled'])->sum('total_amount');

        return [
            'total' => $totalCount,
            'unpaid' => $unpaidCount,
            'waiting_pickup' => $waitingPickupCount,
            'in_transit' => $inTransitCount,
            'delivered' => $deliveredCount,
            'total_volume' => $totalVolume,
        ];
    }

    /**
     * Ambil data analitik platform khusus Super Admin.
     *
     * @param  array{total: int, unpaid: int, waiting_pickup: int, in_transit: int, delivered: int, total_volume: int}  $metrics
     * @return array{recentShipments: Collection, storesCount: int, defaultStore: null, walletStats: null, platformStats: array, recentAuditLogs: array, umkmList: array}
     */
    private function getSuperAdminData(User $user, array $metrics): array
    {
        $totalCount = $metrics['total'];
        $totalVolume = $metrics['total_volume'];
        $deliveredCount = $metrics['delivered'];
        $eligibleForDeliveryCount = $metrics['waiting_pickup'] + $metrics['in_transit'] + $deliveredCount;

        $recentShipments = Shipment::with(['user:id,name,email', 'store:id,name,phone'])
            ->latest()
            ->take(6)
            ->get();

        $storesCount = Store::count();
        $paidShipmentsCount = Shipment::whereNotIn('status', ['pending_payment', 'cancelled'])->count();
        $cancelledShipmentsCount = Shipment::whereIn('status', ['cancelled', 'rejected', 'courier_not_found', 'returned'])->count();
        $adminWallet = $user->getOrCreateWallet()->syncBalances();
        $platformRevenue = (int) $adminWallet->available_balance;

        $pendingWithdrawalsQuery = Withdrawal::whereIn('status', ['pending', 'processing']);
        $completedWithdrawalsQuery = Withdrawal::where('status', 'completed');

        $nonCodShipments = Shipment::whereNotIn('status', ['pending_payment', 'cancelled'])
            ->where(function ($query) {
                $query->where('payment_method', '!=', 'cod')
                    ->orWhereNull('payment_method');
            });
        $codShipments = Shipment::where('payment_method', 'cod')
            ->whereNotIn('status', ['pending_payment', 'cancelled']);

        $nonCodCount = (clone $nonCodShipments)->count();
        $nonCodVolume = (int) (clone $nonCodShipments)->sum('total_amount');

        $codCount = (clone $codShipments)->count();
        $codVolume = (int) (clone $codShipments)->sum('total_amount');

        $platformStats = [
            'total_users' => User::count(),
            'total_umkm' => User::where('role', 'umkm')->count(),
            'active_umkm' => User::where('role', 'umkm')->where('is_active', true)->count(),
            'total_stores' => $storesCount,
            'total_shipments' => $totalCount,
            'paid_shipments' => $paidShipmentsCount,
            'cancelled_shipments' => $cancelledShipmentsCount,
            'delivered_rate' => $eligibleForDeliveryCount > 0 ? (int) round(($deliveredCount / $eligibleForDeliveryCount) * 100) : 0,
            'platform_revenue' => $platformRevenue,
            'total_volume' => $totalVolume,
            'total_audit_logs' => AuditLog::count(),
            'non_cod_count' => $nonCodCount,
            'non_cod_volume' => $nonCodVolume,
            'cod_count' => $codCount,
            'cod_volume' => $codVolume,
            'pending_withdrawals_count' => (clone $pendingWithdrawalsQuery)->count(),
            'pending_withdrawals_amount' => (int) (clone $pendingWithdrawalsQuery)->sum('amount'),
            'completed_withdrawals_count' => (clone $completedWithdrawalsQuery)->count(),
            'completed_withdrawals_amount' => (int) (clone $completedWithdrawalsQuery)->sum('amount'),
        ];

        $umkmList = User::where('role', 'umkm')
            ->withCount(['stores', 'shipments'])
            ->withSum(['shipments as total_spent' => function ($q) {
                $q->whereNotIn('status', ['pending_payment', 'cancelled']);
            }], 'total_amount')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => (bool) $u->is_active,
                'stores_count' => $u->stores_count,
                'shipments_count' => $u->shipments_count,
                'total_spent' => (int) ($u->total_spent ?? 0),
                'created_at' => $u->created_at->toIso8601String(),
            ])
            ->values()
            ->all();

        $recentAuditLogs = AuditLog::with(['user:id,name,email,role', 'auditable'])
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function ($log) {
                $label = match ($log->auditable_type) {
                    'Shipment' => $log->auditable ? $log->auditable->order_number : (string) $log->auditable_id,
                    'Store' => $log->auditable ? $log->auditable->name : (string) $log->auditable_id,
                    'User' => $log->auditable ? $log->auditable->name : (string) $log->auditable_id,
                    default => (string) $log->auditable_id,
                };

                $formatted = ! empty($log->new_values)
                    ? AuditHandler::format($log->new_values)
                    : (! empty($log->old_values) ? AuditHandler::format($log->old_values) : []);

                return [
                    'id' => $log->id,
                    'user' => $log->user ? [
                        'name' => $log->user->name,
                        'role' => $log->user->role,
                    ] : null,
                    'event' => $log->event,
                    'auditable_type' => $log->auditable_type,
                    'auditable_id' => $log->auditable_id,
                    'auditable_label' => $label,
                    'formatted_changes' => array_slice($formatted, 0, 2),
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return [
            'recentShipments' => $recentShipments,
            'storesCount' => $storesCount,
            'defaultStore' => null,
            'walletStats' => null,
            'platformStats' => $platformStats,
            'recentAuditLogs' => $recentAuditLogs,
            'umkmList' => $umkmList,
        ];
    }

    /**
     * Ambil data pengiriman, dompet COD, dan toko khusus user UMKM.
     *
     * @return array{recentShipments: Collection, defaultStore: Store|null, storesCount: int, walletStats: array{available_balance: int, pending_balance: int, total_withdrawn: int}, platformStats: null, recentAuditLogs: array, umkmList: array}
     */
    private function getUmkmData(User $user): array
    {
        $recentShipments = $user->shipments()
            ->with('store:id,name,phone')
            ->latest()
            ->take(6)
            ->get();

        $wallet = $user->getOrCreateWallet()->syncBalances();

        return [
            'recentShipments' => $recentShipments,
            'defaultStore' => $user->defaultStore(),
            'storesCount' => $user->stores()->count(),
            'walletStats' => [
                'available_balance' => (int) $wallet->available_balance,
                'pending_balance' => (int) $wallet->pending_balance,
                'total_withdrawn' => (int) $wallet->total_withdrawn,
            ],
            'platformStats' => null,
            'recentAuditLogs' => [],
            'umkmList' => [],
        ];
    }

    /**
     * Hitung grafik tren volume pengiriman 7 hari terakhir beserta persentase pertumbuhan mingguan.
     *
     * @return array{trend: array<int, array{date: string, day: string, count: int}>, weeklyGrowth: int}
     */
    private function calculateSevenDayTrend(Carbon $today, Builder|HasMany $shipmentQuery): array
    {
        $tz = 'Asia/Jakarta';
        $today = (clone $today)->setTimezone($tz)->startOfDay();
        $sevenDayTrend = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        for ($i = 6; $i >= 0; $i--) {
            $day = (clone $today)->subDays($i);
            $dateStr = $day->toDateString();
            $sevenDayTrend[$dateStr] = [
                'date' => $dateStr,
                'day' => $dayNames[$day->dayOfWeek],
                'count' => 0,
            ];
        }

        $startUtc = (clone $today)->subDays(6)->startOfDay()->setTimezone('UTC');

        $recent7DaysShipments = (clone $shipmentQuery)
            ->where('created_at', '>=', $startUtc)
            ->get(['id', 'created_at']);

        foreach ($recent7DaysShipments as $s) {
            $sDate = $s->created_at->timezone($tz)->toDateString();
            if (isset($sevenDayTrend[$sDate])) {
                $sevenDayTrend[$sDate]['count']++;
            }
        }

        $prevStartUtc = (clone $today)->subDays(13)->startOfDay()->setTimezone('UTC');
        $prevEndUtc = (clone $today)->subDays(7)->endOfDay()->setTimezone('UTC');

        $previous7DaysCount = (clone $shipmentQuery)
            ->whereBetween('created_at', [$prevStartUtc, $prevEndUtc])
            ->count();

        $current7DaysCount = $recent7DaysShipments->count();

        if ($previous7DaysCount > 0) {
            $weeklyGrowth = (int) round((($current7DaysCount - $previous7DaysCount) / $previous7DaysCount) * 100);
        } elseif ($current7DaysCount > 0) {
            $weeklyGrowth = 100;
        } else {
            $weeklyGrowth = 0;
        }

        return [
            'trend' => array_values($sevenDayTrend),
            'weeklyGrowth' => $weeklyGrowth,
        ];
    }

    /**
     * Konversi kode ekspedisi ke nama resmi ekspedisi.
     */
    private function mapCourierName(string $code): string
    {
        return match (strtolower($code)) {
            'jne' => 'JNE Express',
            'jnt' => 'J&T Express',
            'sicepat' => 'SiCepat Ekspres',
            'anteraja' => 'AnterAja',
            'ninja' => 'Ninja Xpress',
            'lion' => 'Lion Parcel',
            'pos' => 'Pos Indonesia',
            'tiki' => 'TIKI',
            default => strtoupper($code),
        };
    }

    /**
     * Hitung metrik performa per ekspedisi yang aktif digunakan.
     *
     * @return array<int, array{code: string, name: string, count: int, delivered_count: int, active_count: int, avg_fee: int, total_fee: int, delivery_rate: int, share_percentage: int}>
     */
    private function calculateCourierAnalytics(Collection $allShipments, int $totalCount): array
    {
        return $allShipments
            ->filter(fn ($s) => ! empty($s->courier_code))
            ->groupBy(fn ($s) => strtolower((string) $s->courier_code))
            ->map(function (Collection $group, string $code) use ($totalCount) {
                $count = $group->count();
                $delivered = $group->where('status', 'delivered')->count();
                $inTransit = $group->whereIn('status', ['in_transit', 'waiting_pickup', 'paid', 'allocated', 'picking_up'])->count();
                $paidGroup = $group->whereNotIn('status', ['pending_payment', 'cancelled']);
                $totalFee = (int) $paidGroup->sum('total_amount');
                $avgFee = $count > 0 ? (int) round($group->avg('total_amount')) : 0;
                $completedOrActive = $delivered + $inTransit;
                $deliveryRate = $completedOrActive > 0 ? (int) round(($delivered / $completedOrActive) * 100) : 0;

                return [
                    'code' => $code,
                    'name' => $this->mapCourierName($code),
                    'count' => $count,
                    'delivered_count' => $delivered,
                    'active_count' => $inTransit,
                    'avg_fee' => $avgFee,
                    'total_fee' => $totalFee,
                    'delivery_rate' => $deliveryRate,
                    'share_percentage' => $totalCount > 0 ? (int) round(($count / $totalCount) * 100) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Hitung perbandingan penggunaan layanan reguler vs express.
     */
    private function calculateServiceBreakdown(Collection $allShipments): array
    {
        $serviceGroups = [
            'regular' => ['label' => 'Reguler / Ekonomis', 'desc' => 'JNE REG, J&T EZ, SiCepat REG, AnterAja Regular', 'count' => 0, 'total_spend' => 0, 'avg_spend' => 0],
            'express' => ['label' => 'Next Day / Express', 'desc' => 'JNE YES, J&T Super, SiCepat BEST, AnterAja Next Day', 'count' => 0, 'total_spend' => 0, 'avg_spend' => 0],
        ];

        foreach ($allShipments as $s) {
            if (in_array($s->status, ['pending_payment', 'cancelled'], true)) {
                continue;
            }
            $srvCode = strtolower((string) $s->courier_service_code);

            if (in_array($srvCode, ['yes', 'super', 'best', 'next_day', 'nextday'], true)) {
                $tier = 'express';
            } else {
                $tier = 'regular';
            }

            $serviceGroups[$tier]['count']++;
            $serviceGroups[$tier]['total_spend'] += (int) $s->total_amount;
        }

        $activeServiceCount = max(1, array_sum(array_column($serviceGroups, 'count')));

        return collect($serviceGroups)->map(function ($item, $key) use ($activeServiceCount) {
            $item['key'] = $key;
            $item['avg_spend'] = $item['count'] > 0 ? (int) round($item['total_spend'] / $item['count']) : 0;
            $item['percentage'] = (int) round(($item['count'] / $activeServiceCount) * 100);

            return $item;
        })->values()->all();
    }

    /**
     * Hitung performa setiap toko aktif.
     */
    private function calculateStorePerformance(bool $isSuperAdmin, User $user, Collection $allShipments, int $totalCount): array
    {
        $storesList = $isSuperAdmin
            ? Store::query()->take(6)->get()
            : $user->stores()->orderByDesc('is_default')->get();

        return $storesList->map(function (Store $store) use ($allShipments, $totalCount) {
            $storeShipments = $allShipments->where('store_id', $store->id);
            $count = $storeShipments->count();
            $delivered = $storeShipments->where('status', 'delivered')->count();
            $inTransit = $storeShipments->where('status', 'in_transit')->count();
            $totalSpend = (int) $storeShipments->whereNotIn('status', ['pending_payment', 'cancelled'])->sum('total_amount');
            $avgWeight = $count > 0 ? (int) round($storeShipments->avg('weight')) : 0;

            return [
                'id' => $store->id,
                'name' => $store->name,
                'phone' => $store->phone,
                'address' => $store->address,
                'area_name' => $store->area_name,
                'postal_code' => $store->postal_code,
                'is_default' => (bool) $store->is_default,
                'shipments_count' => $count,
                'delivered_count' => $delivered,
                'in_transit_count' => $inTransit,
                'total_spend' => $totalSpend,
                'avg_weight' => $avgWeight,
                'share_percentage' => $totalCount > 0 ? (int) round(($count / $totalCount) * 100) : 0,
            ];
        })->sortByDesc('shipments_count')->values()->all();
    }

    /**
     * Format 5 aktivitas pelacakan paket terbaru secara real-time.
     */
    private function calculateLiveTrackingFeed(Collection $allShipments): array
    {
        return $allShipments
            ->whereIn('status', ['in_transit', 'waiting_pickup', 'delivered'])
            ->sortByDesc('updated_at')
            ->take(5)
            ->map(function ($s) {
                $history = is_array($s->tracking_history) ? $s->tracking_history : [];
                $latestLog = ! empty($history) ? end($history) : null;

                return [
                    'id' => $s->id,
                    'order_number' => $s->order_number,
                    'waybill_number' => $s->waybill_number,
                    'status' => $s->status,
                    'recipient_name' => $s->recipient_name,
                    'destination_area_name' => $s->destination_area_name,
                    'courier_code' => $s->courier_code,
                    'courier_service_name' => $s->courier_service_name,
                    'note' => $latestLog['note'] ?? match ($s->status) {
                        'delivered' => 'Paket telah diterima oleh '.$s->recipient_name,
                        'in_transit' => 'Paket sedang dalam perjalanan menuju kota tujuan',
                        default => 'Menunggu kurir menjemput paket dari lokasi toko',
                    },
                    'time' => $latestLog['time'] ?? $s->updated_at?->toDateTimeString() ?? $s->created_at?->toDateTimeString(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Identifikasi 3 kota destinasi pengiriman teratas.
     */
    private function calculateTopDestinations(Collection $allShipments): array
    {
        $provinces = [
            'jawa barat', 'jawa timur', 'jawa tengah', 'dki jakarta', 'banten',
            'd.i. yogyakarta', 'sumatera utara', 'sumatera selatan', 'bali',
            'sulawesi selatan', 'kalimantan timur', 'kalimantan selatan', 'riau', 'lampung',
        ];

        return $allShipments
            ->filter(fn ($s) => ! empty($s->destination_area_name))
            ->map(function ($s) use ($provinces) {
                $raw = preg_replace('/\.\s*\d{5}$/', '', (string) $s->destination_area_name);
                $parts = array_values(array_filter(array_map('trim', explode(',', $raw))));

                $city = end($parts) ?: $raw;
                if (count($parts) >= 2 && in_array(strtolower($city), $provinces, true)) {
                    $city = $parts[count($parts) - 2];
                }

                return [
                    'city' => $city,
                    'raw' => $raw,
                ];
            })
            ->groupBy(fn ($item) => strtolower($item['city']))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'city' => $first['city'],
                    'full' => $first['raw'],
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->all();
    }
}
