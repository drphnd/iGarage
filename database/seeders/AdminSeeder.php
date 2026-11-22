<?php

namespace Database\Seeders;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Administrator', 'email' => 'admin@igarage.test', 'role' => User::ROLE_ADMIN,
                'nik' => null, 'phone' => null, 'status' => User::STATUS_APPROVED],
            ['name' => 'Budi Santoso', 'email' => 'renter@igarage.test', 'role' => User::ROLE_RENTER,
                'nik' => '3578010101990001', 'phone' => '081234567890', 'status' => User::STATUS_APPROVED],
            ['name' => 'Adi Pratama', 'email' => 'pending@igarage.test', 'role' => User::ROLE_RENTER,
                'nik' => '3578010101990002', 'phone' => '081234567891', 'status' => User::STATUS_PENDING],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrNew(['email' => $account['email']]);
            $user->name = $account['name'];
            $user->role = $account['role'];
            $user->nik = $account['nik'];
            $user->phone = $account['phone'];
            $user->verification_status = $account['status'];
            $user->password = Hash::make('password');
            $user->email_verified_at = now();
            $user->save();
        }

        if (Garage::count() === 0) {
            $batches = [
                [Garage::TYPE_BICYCLE, 3, 10000, null],
                [Garage::TYPE_CAR, 3, 40000, null],
                [Garage::TYPE_ELECTRIC, 2, 50000, 2500],
            ];

            foreach ($batches as [$type, $count, $dailyPrice, $pricePerKwh]) {
                for ($i = 1; $i <= $count; $i++) {
                    Garage::create([
                        'code' => Garage::makeCode($type, $i),
                        'type' => $type,
                        'daily_price' => $dailyPrice,
                        'price_per_kwh' => $pricePerKwh,
                    ]);
                }
            }
        }
    }
}
