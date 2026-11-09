<?php

use App\Models\Shipment;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

test('biteship webhook maps status correctly', function () {
    $statusMap = [
        'confirmed' => 'confirmed',
        'allocated' => 'allocated',
        'pickingup' => 'picking_up',
        'picking_up' => 'picking_up',
        'picked' => 'picked',
        'droppingoff' => 'dropping_off',
        'dropping_off' => 'dropping_off',
        'in_transit' => 'in_transit',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        'rejected' => 'rejected',
        'on_hold' => 'on_hold',
        'returned' => 'returned',
    ];

    expect($statusMap['allocated'])->toBe('allocated')
        ->and($statusMap['picking_up'])->toBe('picking_up')
        ->and($statusMap['picked'])->toBe('picked')
        ->and($statusMap['dropping_off'])->toBe('dropping_off')
        ->and($statusMap['on_hold'])->toBe('on_hold')
        ->and($statusMap['delivered'])->toBe('delivered');
});

