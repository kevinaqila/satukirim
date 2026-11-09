<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Wallet;
use App\Handlers\AuditHandler;
use App\Services\BiteshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    public function __construct(
        protected BiteshipService $biteship
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $courier = $request->query('courier');

        $dates = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_preset' => ['nullable', 'string', 'max:20'],
        ]);
        $dateFrom = $dates['date_from'] ?? null;
        $dateTo = $dates['date_to'] ?? null;

        $query = $request->user()->shipments()->with('store:id,name,phone')->latest();

        if ($status && $status !== 'all') {
            if ($status === 'paid') {
                $query->whereIn('status', ['paid', 'confirmed', 'waiting_pickup', 'allocated', 'picking_up']);
            } elseif ($status === 'in_transit') {
                $query->whereIn('status', ['picked', 'in_transit', 'dropping_off', 'on_hold']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($courier && $courier !== 'all') {
            $query->where('courier_code', $courier);
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
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('destination_address', 'like', "%{$search}%")
                    ->orWhere('destination_area_name', 'like', "%{$search}%")
                    ->orWhere('courier_code', 'like', "%{$search}%")
                    ->orWhere('courier_service_name', 'like', "%{$search}%")
                    ->orWhere('item_description', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        $shipments = $query->paginate(15)->withQueryString();

        return Inertia::render('Shipments/Index', [
            'shipments' => $shipments,
            'couriers' => $request->user()->shipments()
                ->whereNotNull('courier_code')
                ->distinct()
                ->orderBy('courier_code')
                ->pluck('courier_code'),
            'filters' => [
                'status' => $status ?? 'all',
                'search' => $search ?? '',
                'courier' => $courier ?? 'all',
                'date_preset' => $dates['date_preset'] ?? 'all',
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $stores = $request->user()->stores()->orderByDesc('is_default')->get();

        if ($stores->isEmpty()) {
            return redirect()->route('stores.index')->with('toast', [
                'type' => 'warning',
                'message' => 'Silakan buat toko atau alamat pengirim terlebih dahulu!',
            ]);
        }

        return Inertia::render('Shipments/Create', [
            'stores' => $stores,
            'defaultStore' => $request->user()->defaultStore(),
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'destination_area_id' => ['required', 'string'],
            'weight' => ['required', 'integer', 'min:100', 'max:10000'],
        ]);

        $store = $request->user()->stores()->findOrFail($validated['store_id']);

        $result = $this->biteship->checkRates(
            originAreaId: $store->biteship_area_id,
            destinationAreaId: $validated['destination_area_id'],
            weightInGrams: (int) $validated['weight'],
        );

        return response()->json($result);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:25'],
            'destination_address' => ['required', 'string', 'max:1000'],
            'destination_postal_code' => ['nullable', 'string', 'max:10'],
            'destination_area_id' => ['required', 'string', 'max:100'],
            'destination_area_name' => ['nullable', 'string', 'max:255'],
            'weight' => ['required', 'integer', 'min:100', 'max:10000'],
            'item_description' => ['nullable', 'string', 'max:500'],
            'item_category' => ['nullable', 'string', 'max:100'],
            'item_value' => ['nullable', 'integer', 'min:0'],
            'courier_code' => ['required', 'string', 'max:50'],
            'courier_service_code' => ['required', 'string', 'max:50'],
            'courier_service_name' => ['required', 'string', 'max:100'],
            'shipping_fee' => ['required', 'integer', 'min:1'],
            'estimated_delivery' => ['nullable', 'string', 'max:50'],
            'is_cod' => ['nullable', 'boolean'],
            'payment_type' => ['nullable', 'string', 'in:non_cod,cod'],
        ]);

        $store = $request->user()->stores()->findOrFail($validated['store_id']);

        $cleanPhone = preg_replace('/\D/', '', (string) $validated['recipient_phone']);
        if (str_starts_with($cleanPhone, '0')) {
            $validated['recipient_phone'] = '+62'.substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '62')) {
            $validated['recipient_phone'] = '+'.$cleanPhone;
        }

        $isCod = (bool) ($request->boolean('is_cod') || $request->input('payment_type') === 'cod');
        $itemValue = (int) ($validated['item_value'] ?? 0);

        if ($isCod && $itemValue < 1000) {
            return back()->withErrors([
                'item_value' => 'Nilai barang untuk pengiriman COD (Bayar di Tempat) minimal Rp 1.000.',
            ])->withInput();
        }

        $shippingFee = (int) $validated['shipping_fee'];
        $totalAmount = $isCod
            ? $shippingFee
            : ($shippingFee + Shipment::PLATFORM_FEE);

        $waybill = $isCod ? 'STK'.strtoupper($validated['courier_code']).date('ymd').rand(1000, 9999) : null;
        $history = $isCod ? [
            [
                'status' => 'paid',
                'note' => 'Pengiriman COD (Bayar di Tempat) telah dikonfirmasi penjual.',
                'timestamp' => now()->toIso8601String(),
            ],
            [
                'status' => 'waiting_pickup',
                'note' => 'Permintaan penjemputan paket COD dijadwalkan ke kurir '.$validated['courier_service_name'],
                'timestamp' => now()->addMinutes(2)->toIso8601String(),
            ],
        ] : null;

        $shipment = $request->user()->shipments()->create([
            'store_id' => $store->id,
            'order_number' => Shipment::generateOrderNumber(),
            'status' => $isCod ? 'paid' : 'pending_payment',
            'payment_method' => $isCod ? 'cod' : null,
            'paid_at' => $isCod ? now() : null,
            'waybill_number' => $waybill,
            'tracking_history' => $history,
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'destination_address' => $validated['destination_address'],
            'destination_postal_code' => $validated['destination_postal_code'] ?? null,
            'destination_area_id' => $validated['destination_area_id'],
            'destination_area_name' => $validated['destination_area_name'] ?? null,
            'weight' => (int) $validated['weight'],
            'item_description' => $validated['item_description'] ?? 'Barang Dagangan UMKM',
            'item_category' => $validated['item_category'] ?? 'Fashion / Retail',
            'item_value' => $itemValue,
            'courier_code' => strtolower($validated['courier_code']),
            'courier_service_code' => strtolower($validated['courier_service_code']),
            'courier_service_name' => $validated['courier_service_name'],
            'shipping_fee' => $shippingFee,
            'total_amount' => $totalAmount,
            'estimated_delivery' => $validated['estimated_delivery'] ?? '1-3 hari',
        ]);

        if ($isCod) {
            $biteshipResult = $this->biteship->createOrder($shipment);

            if (! empty($biteshipResult['success'])) {
                $shipment->update([
                    'biteship_order_id' => $biteshipResult['biteship_order_id'] ?? null,
                    'waybill_number' => $biteshipResult['waybill_number'] ?: $shipment->waybill_number,
                ]);
            }

            $request->user()->getOrCreateWallet()->syncBalances();
            Wallet::syncPlatformBalances();
        }

        return redirect()->route('shipments.show', $shipment)->with('toast', [
            'type' => 'success',
            'message' => $isCod
                ? 'Pesanan COD berhasil dibuat! Menunggu penjemputan kurir.'
                : 'Pesanan pengiriman berhasil dibuat! Silakan lanjutkan pembayaran.',
        ]);
    }

    public function show(Request $request, Shipment $shipment): Response
    {
        abort_unless($shipment->user_id === $request->user()->id || $request->user()->isSuperAdmin(), 403);

        if ($shipment->status === 'pending_payment' && ! empty(config('services.midtrans.server_key'))) {
            app(\App\Services\MidtransService::class)->checkStatus($shipment);
            $shipment->refresh();
        }

        if (($shipment->status === 'paid' || $shipment->payment_method === 'cod') && empty($shipment->biteship_order_id)) {
            $biteshipResult = $this->biteship->createOrder($shipment);
            if (! empty($biteshipResult['success'])) {
                $shipment->update([
                    'biteship_order_id' => $biteshipResult['biteship_order_id'] ?? null,
                    'waybill_number' => $biteshipResult['waybill_number'] ?: $shipment->waybill_number,
                ]);
                $shipment->refresh();
            }
        }

        if (! empty($shipment->biteship_order_id) && ! in_array($shipment->status, ['delivered', 'cancelled'], true)) {
            $orderResult = $this->biteship->getOrder($shipment->biteship_order_id);
            if (! empty($orderResult['success']) && ! empty($orderResult['data'])) {
                $orderData = $orderResult['data'];
                $biteshipStatus = strtolower(trim((string) ($orderData['status'] ?? '')));

                $statusMap = [
                    'confirmed' => 'confirmed',
                    'scheduled' => 'confirmed',
                    'allocated' => 'allocated',
                    'pickingup' => 'picking_up',
                    'picking_up' => 'picking_up',
                    'dalam penjemputan' => 'picking_up',
                    'picked' => 'picked',
                    'droppingoff' => 'dropping_off',
                    'dropping_off' => 'dropping_off',
                    'in_transit' => 'in_transit',
                    'intransit' => 'in_transit',
                    'on_process' => 'in_transit',
                    'delivered' => 'delivered',
                    'cancelled' => 'cancelled',
                    'rejected' => 'rejected',
                    'on_hold' => 'on_hold',
                    'onhold' => 'on_hold',
                    'courier_not_found' => 'courier_not_found',
                    'couriernotfound' => 'courier_not_found',
                    'returned' => 'returned',
                    'return_in_transit' => 'returned',
                ];

                $mapped = $statusMap[$biteshipStatus] ?? null;
                $newWaybill = $orderData['courier']['waybill_id'] ?? null;
                $showUpdates = [];

                if ($mapped && $mapped !== $shipment->status) {
                    $showUpdates['status'] = $mapped;
                }
                if ($newWaybill && $newWaybill !== $shipment->waybill_number) {
                    $showUpdates['waybill_number'] = $newWaybill;
                }

                $historyList = $orderData['courier']['history'] ?? ($orderData['history'] ?? []);
                if (! empty($historyList) && is_array($historyList)) {
                    $formattedHistory = [];
                    foreach ($historyList as $item) {
                        $itemStatus = strtolower(trim((string) ($item['status'] ?? '')));
                        $formattedHistory[] = [
                            'status' => $statusMap[$itemStatus] ?? ($item['status'] ?? 'in_transit'),
                            'note' => $item['note'] ?? 'Status kurir: '.$itemStatus,
                            'timestamp' => $item['updated_at'] ?? now()->toIso8601String(),
                        ];
                    }
                    $showUpdates['tracking_history'] = $formattedHistory;
                }

                if (! empty($showUpdates)) {
                    $shipment->update($showUpdates);
                    $shipment->refresh();

                    if (in_array($mapped, ['delivered', 'cancelled'], true)) {
                        $shipment->user?->getOrCreateWallet()->syncBalances();
                        \App\Models\Wallet::syncPlatformBalances();
                    }
                }
            }
        }

        $shipment->load(['store', 'user:id,name,email,phone']);

        return Inertia::render('Shipments/Show', [
            'shipment' => $shipment,
            'isSuperAdmin' => $request->user()->isSuperAdmin(),
            'midtransClientKey' => config('services.midtrans.client_key'),
            'midtransIsProduction' => config('services.midtrans.is_production', false),
        ]);
    }

    public function destroy(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_unless($shipment->user_id === $request->user()->id || $request->user()->isSuperAdmin(), 403);

        $shipment->delete();

        Wallet::syncPlatformBalances();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Data pengiriman berhasil dihapus!',
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:shipments,id'],
        ]);

        $query = Shipment::query()->whereIn('id', $validated['ids']);

        if (! $request->user()->isSuperAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $shipments = $query->get();
        $count = $shipments->count();

        if ($count === 0) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Tidak ada pengiriman yang dapat dihapus.',
            ]);
        }

        $targetIds = $shipments->pluck('id')->all();

        foreach ($shipments as $shipment) {
            $shipment->delete();
        }

        if (class_exists(AuditHandler::class) && method_exists(AuditHandler::class, 'log')) {
            AuditHandler::log(
                'bulk_deleted',
                'Bulk Hapus Pengiriman',
                'Shipment',
                null,
                null,
                ['deleted_ids' => $targetIds, 'count' => $count]
            );
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Sebanyak {$count} data pengiriman berhasil dihapus!",
        ]);
    }

    public function exportAll(Request $request): JsonResponse
    {
        if ($request->boolean('no_filter')) {
            $shipments = $request->user()->shipments()
                ->with(['store:id,name', 'user:id,name'])
                ->latest()
                ->get();

            return response()->json(['shipments' => $shipments]);
        }

        $status = $request->query('status');
        $search = $request->query('search');
        $courier = $request->query('courier');

        $dates = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_preset' => ['nullable', 'string', 'max:20'],
        ]);
        $dateFrom = $dates['date_from'] ?? null;
        $dateTo = $dates['date_to'] ?? null;

        $query = $request->user()->shipments()->with(['store:id,name', 'user:id,name'])->latest();

        if ($status && $status !== 'all') {
            if ($status === 'paid') {
                $query->whereIn('status', ['paid', 'confirmed', 'waiting_pickup', 'allocated', 'picking_up']);
            } elseif ($status === 'in_transit') {
                $query->whereIn('status', ['picked', 'in_transit', 'dropping_off', 'on_hold']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($courier && $courier !== 'all') {
            $query->where('courier_code', $courier);
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
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('destination_address', 'like', "%{$search}%")
                    ->orWhere('destination_area_name', 'like', "%{$search}%")
                    ->orWhere('courier_code', 'like', "%{$search}%")
                    ->orWhere('courier_service_name', 'like', "%{$search}%")
                    ->orWhere('item_description', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        $shipments = $query->get();

        return response()->json(['shipments' => $shipments]);
    }

    public function printBulk(Request $request): Response
    {
        $rawIds = $request->input('ids');
        $idList = [];

        if (is_array($rawIds)) {
            $idList = array_values(array_filter(array_map('intval', $rawIds)));
        } elseif (is_string($rawIds) && trim($rawIds) !== '') {
            $idList = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        }

        $shipments = collect();

        if (! empty($idList)) {
            $query = Shipment::with(['store', 'user'])->whereIn('id', $idList);

            if (! $request->user()->isSuperAdmin()) {
                $query->where('user_id', $request->user()->id);
            }

            $shipments = $query->latest()->get()->map(function (Shipment $shipment) {
                $hasWaybill = ! empty($shipment->waybill_number);
                $isPaid = $shipment->status !== 'pending_payment';

                $shipment->setAttribute('is_printable', $hasWaybill || $isPaid);
                $shipment->setAttribute('can_print', $hasWaybill);

                return $shipment;
            });
        }

        return Inertia::render('Shipments/PrintBulk', [
            'shipments' => $shipments,
        ]);
    }
}
