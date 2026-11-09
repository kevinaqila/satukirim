<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (AuditLog::count() > 0) {
            return;
        }

        foreach (User::all() as $user) {
            AuditLog::create([
                'user_id' => $user->id === 1 ? null : 1,
                'event' => 'created',
                'auditable_type' => 'User',
                'auditable_id' => $user->id,
                'old_values' => null,
                'new_values' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => $user->created_at ?? now(),
            ]);
        }

        foreach (Store::all() as $store) {
            AuditLog::create([
                'user_id' => $store->user_id,
                'event' => 'created',
                'auditable_type' => 'Store',
                'auditable_id' => $store->id,
                'old_values' => null,
                'new_values' => [
                    'name' => $store->name,
                    'phone' => $store->phone,
                    'address' => $store->address,
                    'postal_code' => $store->postal_code,
                    'area_name' => $store->area_name,
                    'is_default' => $store->is_default,
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => $store->created_at ?? now(),
            ]);
        }

        $shipments = Shipment::orderBy('id', 'desc')->take(20)->get();

        foreach ($shipments as $shipment) {
            AuditLog::create([
                'user_id' => $shipment->user_id,
                'event' => 'created',
                'auditable_type' => 'Shipment',
                'auditable_id' => $shipment->id,
                'old_values' => null,
                'new_values' => [
                    'order_number' => $shipment->order_number,
                    'courier_code' => $shipment->courier_code,
                    'courier_service_name' => $shipment->courier_service_name,
                    'recipient_name' => $shipment->recipient_name,
                    'destination_area_name' => $shipment->destination_area_name,
                    'total_amount' => $shipment->total_amount,
                    'status' => 'pending_payment',
                ],
                'ip_address' => '127.0.0.1',
                'created_at' => $shipment->created_at ?? now(),
            ]);

            if ($shipment->status !== 'pending_payment') {
                AuditLog::create([
                    'user_id' => $shipment->user_id,
                    'event' => 'updated',
                    'auditable_type' => 'Shipment',
                    'auditable_id' => $shipment->id,
                    'old_values' => [
                        'status' => 'pending_payment',
                        'waybill_number' => null,
                    ],
                    'new_values' => [
                        'status' => $shipment->status,
                        'waybill_number' => $shipment->waybill_number,
                        'paid_at' => $shipment->paid_at ? (string) $shipment->paid_at : null,
                    ],
                    'ip_address' => '127.0.0.1',
                    'created_at' => $shipment->updated_at ?? now(),
                ]);
            }
        }
    }
}
