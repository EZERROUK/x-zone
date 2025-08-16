<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CurrencySeeder::class,
            TaxRateSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,  // Après brands pour les relations
            ProductSeeder::class,
            ProviderSeeder::class,
            StockMovementReasonSeeder::class,
            AppSettingSeeder::class,
            ClientSeeder::class,
        ]);

        \App\Models\User::factory()->create([
            'name' => 'SuperAdmin',
            'email' => 'SuperAdmin@example.com',
        ])->assignRole('SuperAdmin');
    }
}
