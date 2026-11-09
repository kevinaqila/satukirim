<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@satukirim.id'],
            [
                'name' => 'Super Admin SatuKirim',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'merchant@satukirim.id'],
            [
                'name' => 'Budi Santoso (Kopi Kenangan UMKM)',
                'password' => Hash::make('password'),
                'role' => 'umkm',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'inactive@satukirim.id'],
            [
                'name' => 'Toko Nonaktif (Demo Banned)',
                'password' => Hash::make('password'),
                'role' => 'umkm',
                'is_active' => false,
                'email_verified_at' => now(),
            ]
        );
    }
}

