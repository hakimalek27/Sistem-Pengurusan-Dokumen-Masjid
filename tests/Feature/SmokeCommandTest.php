<?php

use App\Models\Mosque;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('boleh menjalankan smoke E2E untuk lebih daripada satu tenant tanpa konflik identiti', function () {
    Mail::fake();
    Storage::fake(config('diwan.storage_disk'));

    $this->artisan('diwan:smoke', ['--slug' => 'smoke-satu'])
        ->expectsOutputToContain('SMOKE E2E: 9 lulus, 0 gagal.')
        ->assertSuccessful();

    $this->artisan('diwan:smoke', ['--slug' => 'smoke-dua'])
        ->expectsOutputToContain('SMOKE E2E: 9 lulus, 0 gagal.')
        ->assertSuccessful();

    expect(Mosque::query()->whereIn('slug', ['smoke-satu', 'smoke-dua'])->pluck('code'))
        ->each->toHaveLength(6);
});
