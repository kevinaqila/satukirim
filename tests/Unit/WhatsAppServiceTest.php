<?php

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class);

test('whatsapp service reports enabled status based on config and flags', function () {
    $enabled = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: 'secret-key',
        session: 'default',
        enabled: true,
    );
    expect($enabled->isEnabled())->toBeTrue();

    $disabledByFlag = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: 'secret-key',
        session: 'default',
        enabled: false,
    );
    expect($disabledByFlag->isEnabled())->toBeFalse();

    $missingKey = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: '',
        session: 'default',
    );
    expect($missingKey->isEnabled())->toBeFalse();
});

test('whatsapp service normalizes Indonesian phone numbers to chatId', function () {
    $service = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: 'secret-key',
        session: 'default',
    );

    expect($service->toChatId('0812-3456-7890'))->toBe('6281234567890@c.us')
        ->and($service->toChatId('+62 812 3456 7890'))->toBe('6281234567890@c.us')
        ->and($service->toChatId('6281234567890'))->toBe('6281234567890@c.us')
        ->and($service->toChatId('1234'))->toBeNull();
});

test('whatsapp service sends text payload with X-Api-Key header', function () {
    Http::fake([
        'http://localhost:3000/api/sendText' => function ($request) {
            $body = json_decode($request->body(), true);

            expect($request->header('X-Api-Key')[0])->toBe('test-wa-key')
                ->and($body['session'])->toBe('session_test')
                ->and($body['chatId'])->toBe('6281234567890@c.us')
                ->and($body['text'])->toBe('Halo pesanan Anda siap!');

            return Http::response(['id' => 'msg_123', 'sent' => true], 201);
        },
    ]);

    $service = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: 'test-wa-key',
        session: 'session_test',
        enabled: true,
    );

    $result = $service->sendText('081234567890', 'Halo pesanan Anda siap!');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['id'])->toBe('msg_123');
});

test('whatsapp service handles HTTP error gracefully without throwing', function () {
    Http::fake([
        'http://localhost:3000/api/sendText' => Http::response([
            'message' => 'Session is not WORKING',
        ], 422),
    ]);

    $service = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: 'test-wa-key',
        session: 'default',
        enabled: true,
    );

    $result = $service->sendText('081234567890', 'Tes');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('HTTP 422');
});

test('whatsapp service queues shipment ready and status notifications to recipient', function () {
    Queue::fake();

    $service = new WhatsAppService(
        baseUrl: 'http://localhost:3000',
        apiKey: 'test-wa-key',
        session: 'default',
        enabled: true,
    );

    $store = new Store(['name' => 'Toko Maju']);
    $shipment = new Shipment([
        'order_number' => 'SK-TEST-001',
        'waybill_number' => 'AWB998877',
        'recipient_name' => 'Budi',
        'recipient_phone' => '+6281299990000',
        'courier_service_name' => 'SiCepat REG',
        'payment_method' => 'cod',
        'total_amount' => 15000,
        'item_value' => 85000,
    ]);
    $shipment->setRelation('store', $store);

    $service->notifyShipmentReady($shipment);
    $service->notifyShipmentReady($shipment);
    $service->notifyShipmentStatusUpdated($shipment, 'dropping_off');
    $service->notifyShipmentStatusUpdated($shipment, 'on_hold');
    $service->notifyShipmentStatusUpdated($shipment, 'cancelled');
    $service->notifyShipmentStatusUpdated($shipment, 'rejected');
    $service->notifyShipmentStatusUpdated($shipment, 'courier_not_found');
    $service->notifyShipmentStatusUpdated($shipment, 'disposed');
    $service->notifyShipmentStatusUpdated($shipment, 'delivered');
    $service->notifyShipmentStatusUpdated($shipment, 'delivered');
    $service->notifyShipmentStatusUpdated($shipment, 'allocated');

    Queue::assertPushed(SendWhatsAppMessageJob::class, 8);
    Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job) {
        return $job->phone === '+6281299990000'
            && str_contains($job->message, 'AWB998877')
            && str_contains($job->message, 'tab=resi')
            && str_contains($job->message, 'waybill=AWB998877')
            && str_contains($job->message, 'Rp 100.000');
    });
    Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job) {
        return str_contains($job->message, 'sedang mengantar paket menuju alamat Anda')
            && str_contains($job->message, 'pembayaran COD sebesar *Rp 100.000*');
    });
    Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job) {
        return str_contains($job->message, 'Kurir belum ditemukan di area penjemputan');
    });
    Queue::assertPushed(SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job) {
        return str_contains($job->message, 'dinyatakan dimusnahkan');
    });
});
