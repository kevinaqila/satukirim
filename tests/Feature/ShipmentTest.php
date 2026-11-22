<?php

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\NotificationLog;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Services\BiteshipService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
});

test('authenticated user can view shipment creation wizard', function () {
    $user = User::factory()->create();

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Asal',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_1',
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)->get(route('shipments.create'));

    $response->assertOk();
});

test('user can create a draft shipment with pending payment status', function () {
    $user = User::factory()->create();

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Asal',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_ORIGIN',
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)->post(route('shipments.store'), [
        'store_id' => $store->id,
        'recipient_name' => 'Agus Pratama',
        'recipient_phone' => '081298765432',
        'destination_address' => 'Jl. Sudirman Kav 21',
        'destination_postal_code' => '10220',
        'destination_area_id' => 'AREA_DEST',
        'destination_area_name' => 'Karet Tengsin, Tanah Abang, Jakarta Pusat',
        'weight' => 1000,
        'item_description' => 'Jaket Kulit',
        'item_category' => 'fashion',
        'item_value' => 250000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 20000,
        'insurance_fee' => 0,
        'estimated_delivery' => '1-2 hari',
    ]);

    $shipment = Shipment::where('user_id', $user->id)->first();

    expect($shipment)->not->toBeNull();
    expect($shipment->status)->toBe('pending_payment');
    expect($shipment->total_amount)->toBe(21000);

    $response->assertRedirect(route('shipments.show', $shipment));
});

test('xendit webhook marks shipment as paid on paid status', function () {
    $user = User::factory()->create();

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Asal',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_ORIGIN',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-TEST-WEBHOOK',
        'status' => 'pending_payment',
        'recipient_name' => 'Budi',
        'recipient_phone' => '0812345678',
        'destination_address' => 'Alamat Penerima',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    $response = $this->postJson(route('xendit.webhook'), [
        'id' => 'inv-12345',
        'external_id' => $shipment->order_number,
        'status' => 'PAID',
        'amount' => 15000,
        'payment_method' => 'QRIS',
    ]);

    $response->assertOk();
    expect($shipment->fresh()->status)->toBe('paid');
    expect($shipment->fresh()->waybill_number)->not->toBeNull();
});

test('user can delete their own shipment', function () {
    $user = User::factory()->create();

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Asal',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_ORIGIN',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-TEST-DELETE',
        'status' => 'pending_payment',
        'recipient_name' => 'Budi',
        'recipient_phone' => '0812345678',
        'destination_address' => 'Alamat Penerima',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    $response = $this->actingAs($user)->delete(route('shipments.destroy', $shipment));

    $response->assertSessionHas('toast', [
        'type' => 'success',
        'message' => 'Data pengiriman berhasil dihapus!',
    ]);

    expect(Shipment::find($shipment->id))->toBeNull();
});

test('user cannot delete someone elses shipment', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $store = Store::create([
        'user_id' => $owner->id,
        'name' => 'Toko Asal',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_ORIGIN',
    ]);

    $shipment = Shipment::create([
        'user_id' => $owner->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-TEST-OTHER',
        'status' => 'pending_payment',
        'recipient_name' => 'Budi',
        'recipient_phone' => '0812345678',
        'destination_address' => 'Alamat Penerima',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 15000,
        'total_amount' => 15000,
    ]);

    $response = $this->actingAs($otherUser)->delete(route('shipments.destroy', $shipment));

    $response->assertForbidden();
    expect(Shipment::find($shipment->id))->not->toBeNull();
});

test('user can create cod shipment directly with paid status and waybill', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko COD',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $response = $this->actingAs($user)->post(route('shipments.store'), [
        'store_id' => $store->id,
        'recipient_name' => 'Rina Pembeli COD',
        'recipient_phone' => '08987654321',
        'destination_address' => 'Jl. Sudirman No. 45',
        'destination_area_id' => 'IDNP6IDNC148IDND838',
        'destination_area_name' => 'Setiabudi, Jakarta Selatan',
        'weight' => 1000,
        'item_description' => 'Sepatu Sneakers',
        'item_value' => 150000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 18000,
        'payment_type' => 'cod',
    ]);

    $response->assertRedirect();

    $shipment = Shipment::where('recipient_name', 'Rina Pembeli COD')->first();
    expect($shipment)->not->toBeNull();
    expect($shipment->status)->toBe('paid');
    expect($shipment->payment_method)->toBe('cod');
    expect($shipment->waybill_number)->not->toBeNull();
    expect($shipment->paid_at)->not->toBeNull();
});

test('cod shipment requires item value of at least 1000', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko COD Min Value',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $response = $this->actingAs($user)->post(route('shipments.store'), [
        'store_id' => $store->id,
        'recipient_name' => 'Rina Pembeli COD',
        'recipient_phone' => '08987654321',
        'destination_address' => 'Jl. Sudirman No. 45',
        'destination_area_id' => 'IDNP6IDNC148IDND838',
        'destination_area_name' => 'Setiabudi, Jakarta Selatan',
        'weight' => 1000,
        'item_description' => 'Sepatu Sneakers',
        'item_value' => 0,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 18000,
        'payment_type' => 'cod',
    ]);

    $response->assertSessionHasErrors('item_value');
});

test('user can bulk delete their own shipments', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Bulk',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $shipment1 = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-BULK-1',
        'status' => 'pending_payment',
        'recipient_name' => 'Penerima 1',
        'recipient_phone' => '08123456781',
        'destination_address' => 'Alamat 1',
        'destination_area_id' => 'AREA_1',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reg',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    $shipment2 = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-BULK-2',
        'status' => 'pending_payment',
        'recipient_name' => 'Penerima 2',
        'recipient_phone' => '08123456782',
        'destination_address' => 'Alamat 2',
        'destination_area_id' => 'AREA_2',
        'weight' => 1000,
        'courier_code' => 'jnt',
        'courier_service_code' => 'ez',
        'courier_service_name' => 'J&T EZ',
        'shipping_fee' => 12000,
        'total_amount' => 12000,
    ]);

    $response = $this->actingAs($user)->post(route('shipments.bulk-delete'), [
        'ids' => [$shipment1->id, $shipment2->id],
    ]);

    $response->assertSessionHas('toast', [
        'type' => 'success',
        'message' => 'Sebanyak 2 data pengiriman berhasil dihapus!',
    ]);

    expect(Shipment::find($shipment1->id))->toBeNull();
    expect(Shipment::find($shipment2->id))->toBeNull();
});

test('bulk delete only deletes shipments owned by the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $otherUser->id,
        'name' => 'Toko Other',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $otherShipment = Shipment::create([
        'user_id' => $otherUser->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-OTHER-BULK',
        'status' => 'pending_payment',
        'recipient_name' => 'Penerima Other',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Alamat Other',
        'destination_area_id' => 'AREA_1',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reg',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    $response = $this->actingAs($user)->post(route('shipments.bulk-delete'), [
        'ids' => [$otherShipment->id],
    ]);

    $response->assertSessionHas('toast', [
        'type' => 'error',
        'message' => 'Tidak ada pengiriman yang dapat dihapus.',
    ]);

    expect(Shipment::find($otherShipment->id))->not->toBeNull();
});

test('super admin can bulk delete any shipment', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko User',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-ADMIN-BULK',
        'status' => 'pending_payment',
        'recipient_name' => 'Penerima User',
        'recipient_phone' => '08123456789',
        'destination_address' => 'Alamat User',
        'destination_area_id' => 'AREA_1',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reg',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    $response = $this->actingAs($admin)->post(route('shipments.bulk-delete'), [
        'ids' => [$shipment->id],
    ]);

    $response->assertSessionHas('toast', [
        'type' => 'success',
        'message' => 'Sebanyak 1 data pengiriman berhasil dihapus!',
    ]);

    expect(Shipment::find($shipment->id))->toBeNull();
});

test('user can export all filtered shipments as json', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Export',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-EXP-1',
        'status' => 'paid',
        'recipient_name' => 'Kiki',
        'recipient_phone' => '08123456781',
        'destination_address' => 'Alamat 1',
        'destination_area_id' => 'AREA_1',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reg',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-EXP-2',
        'status' => 'pending_payment',
        'recipient_name' => 'Lala',
        'recipient_phone' => '08123456782',
        'destination_address' => 'Alamat 2',
        'destination_area_id' => 'AREA_2',
        'weight' => 1000,
        'courier_code' => 'sicepat',
        'courier_service_code' => 'siuntung',
        'courier_service_name' => 'SiCepat SIUNTUNG',
        'shipping_fee' => 12000,
        'total_amount' => 12000,
    ]);

    $response = $this->actingAs($user)->getJson(route('shipments.export-all', [
        'status' => 'paid',
    ]));

    $response->assertOk();
    $data = $response->json();
    expect($data)->toHaveKey('shipments');
    expect(count($data['shipments']))->toBe(1);
    expect($data['shipments'][0]['order_number'])->toBe('SATU-EXP-1');
});

test('user can access bulk print with comma-separated and array ids', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Print',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $shipment1 = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-PRN-1',
        'status' => 'paid',
        'waybill_number' => 'AWB111',
        'recipient_name' => 'Penerima Print 1',
        'recipient_phone' => '08123456781',
        'destination_address' => 'Alamat 1',
        'destination_area_id' => 'AREA_1',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reg',
        'shipping_fee' => 10000,
        'total_amount' => 10000,
    ]);

    $responseStr = $this->actingAs($user)->get(route('shipments.print-bulk', [
        'ids' => "{$shipment1->id}",
    ]));
    $responseStr->assertOk();
    $responseStr->assertInertia(fn ($page) => $page
        ->component('Shipments/PrintBulk')
        ->has('shipments', 1)
    );

    $responseArr = $this->actingAs($user)->get(route('shipments.print-bulk', [
        'ids' => [$shipment1->id],
    ]));
    $responseArr->assertOk();
    $responseArr->assertInertia(fn ($page) => $page
        ->component('Shipments/PrintBulk')
        ->has('shipments', 1)
    );
});

test('xendit webhook and shipment detail check do not duplicate biteship order or whatsapp notification', function () {
    Queue::fake();
    config()->set('services.whatsapp.enabled', true);
    config()->set('services.whatsapp.base_url', 'http://localhost:3000');
    config()->set('services.whatsapp.api_key', 'test-wa-key');
    config()->set('services.whatsapp.session', 'default');
    config()->set('services.xendit.key', 'test-xendit-key');
    config()->set('services.biteship.key', 'test-biteship-key');
    config()->set('services.biteship.base_url', 'https://api.biteship.com');

    Http::fake([
        'https://api.biteship.com/v1/orders' => Http::response([
            'id' => 'biteship-order-1',
            'courier' => ['waybill_id' => 'AWB-XND-001'],
        ], 200),
        'https://api.biteship.com/v1/orders/biteship-order-1' => Http::response([
            'id' => 'biteship-order-1',
            'status' => 'confirmed',
            'courier' => ['waybill_id' => 'AWB-XND-001', 'history' => []],
        ], 200),
        'https://api.xendit.co/*' => Http::response([
            'id' => 'inv-dup-1',
            'status' => 'PAID',
            'payment_method' => 'QRIS',
        ], 200),
    ]);

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Anti Double',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-NODUP-PAY',
        'status' => 'pending_payment',
        'xendit_invoice_id' => 'inv-dup-1',
        'recipient_name' => 'Siti',
        'recipient_phone' => '081299998888',
        'destination_address' => 'Jl. Melati 1',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE REG',
        'shipping_fee' => 15000,
        'total_amount' => 16000,
    ]);

    $this->postJson(route('xendit.webhook'), [
        'id' => 'inv-dup-1',
        'external_id' => $shipment->order_number,
        'status' => 'PAID',
        'payment_method' => 'QRIS',
    ])->assertOk();

    $this->postJson(route('xendit.webhook'), [
        'id' => 'inv-dup-1',
        'external_id' => $shipment->order_number,
        'status' => 'PAID',
        'payment_method' => 'QRIS',
    ])->assertOk();

    $this->actingAs($user)->get(route('shipments.show', $shipment))->assertOk();

    Http::assertSentCount(2);
    Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
});

test('biteship webhook and shipment detail page do not send duplicate whatsapp status notifications', function () {
    Queue::fake();
    config()->set('services.whatsapp.enabled', true);
    config()->set('services.whatsapp.base_url', 'http://localhost:3000');
    config()->set('services.whatsapp.api_key', 'test-wa-key');
    config()->set('services.whatsapp.session', 'default');
    config()->set('services.biteship.key', 'test-biteship-key');
    config()->set('services.biteship.base_url', 'https://api.biteship.com');

    Http::fake([
        'https://api.biteship.com/v1/orders/biteship-track-1' => Http::response([
            'id' => 'biteship-track-1',
            'status' => 'in_transit',
            'courier' => [
                'waybill_id' => 'AWB-TRK-001',
                'history' => [
                    ['status' => 'in_transit', 'note' => 'Sedang dikirim', 'updated_at' => '2026-11-16T10:00:00Z'],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Tracking',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran No 10',
        'biteship_area_id' => 'IDNP6IDNC148IDND837',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-NODUP-TRK',
        'status' => 'paid',
        'biteship_order_id' => 'biteship-track-1',
        'waybill_number' => 'AWB-TRK-001',
        'recipient_name' => 'Budi',
        'recipient_phone' => '081277776666',
        'destination_address' => 'Jl. Mawar 2',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE REG',
        'shipping_fee' => 15000,
        'total_amount' => 16000,
    ]);

    $this->postJson(route('biteship.webhook'), [
        'event' => 'order.status',
        'order_id' => 'biteship-track-1',
        'reference_id' => $shipment->order_number,
        'status' => 'in_transit',
        'courier' => ['waybill_id' => 'AWB-TRK-001'],
    ])->assertOk();

    $this->postJson(route('biteship.webhook'), [
        'event' => 'order.status',
        'order_id' => 'biteship-track-1',
        'reference_id' => $shipment->order_number,
        'status' => 'in_transit',
        'courier' => ['waybill_id' => 'AWB-TRK-001'],
    ])->assertOk();

    $this->actingAs($user)->get(route('shipments.show', $shipment))->assertOk();

    expect($shipment->fresh()->status)->toBe('in_transit');
    Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
});

test('whatsapp notifications persist to notification_logs and update status on job execution', function () {
    config()->set('services.whatsapp.enabled', true);
    config()->set('services.whatsapp.base_url', 'http://localhost:3000');
    config()->set('services.whatsapp.api_key', 'test-wa-key');
    config()->set('services.whatsapp.session', 'default');

    Http::fake([
        'http://localhost:3000/api/sendText' => Http::sequence()
            ->push(['id' => 'msg_ok_1'], 201)
            ->push(['message' => 'Session is not WORKING'], 422),
    ]);

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Notif Log',
        'phone' => '08123456789',
        'address' => 'Jl. Melati 5',
        'biteship_area_id' => 'AREA_ORIGIN',
    ]);

    $shipment = Shipment::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'order_number' => 'SATU-NLOG-01',
        'status' => 'paid',
        'waybill_number' => 'AWB-NLOG-01',
        'recipient_name' => 'Ani',
        'recipient_phone' => '081234567890',
        'destination_address' => 'Jl. Anggrek 9',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 1000,
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE REG',
        'shipping_fee' => 15000,
        'total_amount' => 16000,
    ]);

    $service = app(WhatsAppService::class);
    $service->notifyShipmentReady($shipment);

    $readyLog = $shipment->notificationLogs()->where('type', 'ready')->first();
    expect($readyLog)->not->toBeNull()
        ->and($readyLog->status)->toBe('sent')
        ->and($readyLog->recipient_phone)->toBe('6281234567890')
        ->and($readyLog->channel)->toBe('whatsapp')
        ->and($readyLog->sent_at)->not->toBeNull();

    Cache::flush();
    $service->notifyShipmentReady($shipment);
    expect($shipment->notificationLogs()->where('type', 'ready')->count())->toBe(1);

    $service->notifyShipmentStatusUpdated($shipment, 'delivered');

    $failedLog = NotificationLog::where('shipment_id', $shipment->id)
        ->where('type', 'status:delivered')
        ->first();

    expect($failedLog)->not->toBeNull()
        ->and($failedLog->status)->toBe('failed')
        ->and($failedLog->error_message)->toContain('HTTP 422')
        ->and($failedLog->sent_at)->toBeNull();
});

test('shipment rates forwards optional item dimensions to biteship', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [
                [
                    'courier_code' => 'jne',
                    'courier_name' => 'JNE',
                    'courier_service_code' => 'reg',
                    'courier_service_name' => 'Reguler',
                    'price' => 38000,
                    'duration' => '1 - 2 hari',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Dimensi',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'IDnp60281',
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('shipments.rates'), [
        'store_id' => $store->id,
        'destination_area_id' => 'IDnp10110',
        'weight' => 500,
        'item_value' => 75000,
        'items' => [
            [
                'name' => 'Keripik Kaca',
                'qty' => 1,
                'weight' => 500,
                'value' => 75000,
                'length' => 30,
                'width' => 25,
                'height' => 20,
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    Http::assertSent(function ($request) {
        $item = $request['items'][0] ?? [];

        return ($item['length'] ?? null) === 30
            && ($item['width'] ?? null) === 25
            && ($item['height'] ?? null) === 20;
    });
});

test('user can create shipment with optional item dimensions and rejects partial dimensions', function () {
    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Dimensi',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_ORIGIN',
        'is_default' => true,
    ]);

    $invalidResponse = $this->actingAs($user)->post(route('shipments.store'), [
        'store_id' => $store->id,
        'recipient_name' => 'Agus Pratama',
        'recipient_phone' => '081298765432',
        'destination_address' => 'Jl. Sudirman Kav 21',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 500,
        'items' => [
            [
                'name' => 'Keripik Kaca',
                'qty' => 1,
                'weight' => 500,
                'value' => 75000,
                'length' => 30,
            ],
        ],
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 20000,
    ]);

    $invalidResponse->assertSessionHasErrors(['items.0.width', 'items.0.height']);

    $validResponse = $this->actingAs($user)->post(route('shipments.store'), [
        'store_id' => $store->id,
        'recipient_name' => 'Agus Pratama',
        'recipient_phone' => '081298765432',
        'destination_address' => 'Jl. Sudirman Kav 21',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 500,
        'items' => [
            [
                'name' => 'Keripik Kaca',
                'qty' => 1,
                'weight' => 500,
                'value' => 75000,
                'length' => 30,
                'width' => 25,
                'height' => 20,
            ],
        ],
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 20000,
    ]);

    $shipment = Shipment::where('user_id', $user->id)->latest('id')->first();
    $validResponse->assertRedirect(route('shipments.show', $shipment));

    expect($shipment->items)->toHaveCount(1)
        ->and($shipment->items[0]['length'])->toBe(30)
        ->and($shipment->items[0]['width'])->toBe(25)
        ->and($shipment->items[0]['height'])->toBe(20);
});

test('user can create shipment with optional item description and passes it to biteship', function () {
    Http::fake([
        'api.biteship.com/v1/orders' => Http::response([
            'success' => true,
            'id' => 'BS-ORD-DESC',
            'courier' => [
                'waybill_id' => 'WB-DESC-001',
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Toko Deskripsi',
        'phone' => '08123456789',
        'address' => 'Jl. Kenjeran',
        'biteship_area_id' => 'AREA_ORIGIN',
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)->post(route('shipments.store'), [
        'store_id' => $store->id,
        'recipient_name' => 'Siti Pembeli',
        'recipient_phone' => '081298765432',
        'destination_address' => 'Jl. Sudirman Kav 21',
        'destination_area_id' => 'AREA_DEST',
        'weight' => 500,
        'items' => [
            [
                'name' => 'Kemeja Flanel',
                'description' => 'Warna Navy ukuran XL, bungkus bubble wrap',
                'qty' => 2,
                'weight' => 250,
                'value' => 75000,
            ],
        ],
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'courier_service_name' => 'JNE Reguler',
        'shipping_fee' => 20000,
    ]);

    $shipment = Shipment::where('user_id', $user->id)->latest('id')->first();
    $response->assertRedirect(route('shipments.show', $shipment));

    expect($shipment->items)->toHaveCount(1)
        ->and($shipment->items[0]['description'])->toBe('Warna Navy ukuran XL, bungkus bubble wrap');

    $biteship = app(BiteshipService::class);
    $biteship->createOrder($shipment);

    Http::assertSent(function ($request) {
        $item = $request['items'][0] ?? [];

        return ($item['name'] ?? null) === 'Kemeja Flanel'
            && ($item['description'] ?? null) === 'Warna Navy ukuran XL, bungkus bubble wrap';
    });
});
