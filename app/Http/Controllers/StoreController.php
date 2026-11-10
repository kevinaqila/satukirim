<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $stores = $request->user()
            ->stores()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return Inertia::render('Stores/Index', [
            'stores' => $stores,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Stores/Create');
    }

    public function edit(Request $request, Store $store): Response
    {
        abort_unless($store->user_id === $request->user()->id, 403);

        return Inertia::render('Stores/Edit', [
            'store' => $store,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'biteship_area_id' => ['required', 'string', 'max:100'],
            'area_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ]);

        $hasExistingStores = $request->user()->stores()->exists();
        $isDefault = (! $hasExistingStores) || ($validated['is_default'] ?? false);

        if ($isDefault) {
            $request->user()->stores()->update(['is_default' => false]);
        }

        $validated['is_default'] = $isDefault;
        $request->user()->stores()->create($validated);

        if ($request->boolean('from_shipment')) {
            return redirect()->route('shipments.create')->with('toast', [
                'type' => 'success',
                'message' => 'Toko berhasil ditambahkan dan siap digunakan!',
            ]);
        }

        return redirect()->route('stores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Toko berhasil ditambahkan!',
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        abort_unless($store->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'biteship_area_id' => ['required', 'string', 'max:100'],
            'area_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            $request->user()->stores()->where('id', '!=', $store->id)->update(['is_default' => false]);
        }

        $store->update($validated);

        return redirect()->route('stores.index')->with('toast', [
            'type' => 'success',
            'message' => 'Data toko berhasil diperbarui!',
        ]);
    }

    public function destroy(Request $request, Store $store): RedirectResponse
    {
        abort_unless($store->user_id === $request->user()->id, 403);

        $wasDefault = $store->is_default;
        $store->delete();

        if ($wasDefault) {
            $remainingStore = $request->user()->stores()->first();
            $remainingStore?->update(['is_default' => true]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Toko berhasil dihapus!',
        ]);
    }

    public function setDefault(Request $request, Store $store): RedirectResponse
    {
        abort_unless($store->user_id === $request->user()->id, 403);

        $store->markAsDefault();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Toko utama berhasil diubah!',
        ]);
    }
}
