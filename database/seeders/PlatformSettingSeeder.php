<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

// §5.13 platform_settings
class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'pricing' => ['per_gb_year_rm' => null, 'block_gb' => 10], // ✋ superadmin tetapkan kadar
            'bank_details' => ['bank' => null, 'account_name' => null, 'account_no' => null], // ✋
            'data_protection_officer' => ['name' => null, 'phone' => null, 'email' => null],   // ✋
            'default_retention_years' => (int) config('diwan.default_retention_years', 7),
            'registration_open' => (bool) config('diwan.registration_open', true),
            'terms_version' => '2026-07-01',
            'gateway_status' => ['ok' => true, 'checked_at' => null],
        ];

        foreach ($defaults as $key => $value) {
            PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
