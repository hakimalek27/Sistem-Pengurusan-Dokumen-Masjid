<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Sentiasa: tetapan platform + peraturan retensi lalai (§16.1).
        $this->call([
            PlatformSettingSeeder::class,
            RetentionRuleSeeder::class,
        ]);

        // Data demo — local/testing SAHAJA (§17 langkah 5).
        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoSeeder::class);
        }
    }
}
