<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ServiceProviderProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ApplicationOnboardingFormFieldsSeeder::class,
        ]);
    }
}
