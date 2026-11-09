<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchant = User::where('email', 'merchant@satukirim.id')->first();

        if (! $merchant) {
            return;
        }

        Store::updateOrCreate(
            ['user_id' => $merchant->id, 'name' => 'Gudang Utama Surabaya'],
            [
                'phone' => '081234567890',
                'address' => 'Jl. Raya Manyar No. 42, RT 03 / RW 05',
                'postal_code' => '60118',
                'biteship_area_id' => 'IDNP11IDNC434IDND5442IDZ60118',
                'area_name' => 'Menur Pumpungan, Sukolilo, Surabaya',
                'is_default' => true,
            ]
        );

        Store::updateOrCreate(
            ['user_id' => $merchant->id, 'name' => 'Cabang Jakarta Selatan'],
            [
                'phone' => '081298765432',
                'address' => 'Jl. Kemang Raya No. 17B',
                'postal_code' => '12710',
                'biteship_area_id' => 'IDNP6IDNC148IDND840IDZ12710',
                'area_name' => 'Bangka, Mampang Prapatan, Jakarta Selatan',
                'is_default' => false,
            ]
        );
    }
}

