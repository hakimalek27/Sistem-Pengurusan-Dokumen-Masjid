<?php

namespace App\Livewire;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

// §9.A — Halaman pendaftaran masjid awam (/daftar).
class RegisterMosque extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string')]
    public string $state = '';

    #[Validate('nullable|string|max:255')]
    public string $district = '';

    #[Validate('required|string|min:3|max:6|alpha')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $admin_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:20')]
    public string $phone_wa = '';

    public string $slug = '';

    #[Validate('accepted')]
    public bool $agree_terms = false;

    #[Validate('accepted')]
    public bool $agree_retention = false;

    public bool $submitted = false;

    public function updatedName(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function submit(): void
    {
        // §15.1 — throttle 3 pendaftaran / jam / IP.
        $key = 'daftar:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'name' => 'Terlalu banyak permohonan pendaftaran. Sila cuba lagi kemudian.',
            ]);
        }

        $data = $this->validate();

        RateLimiter::hit($key, 3600);

        // Normalisasi kod (huruf besar) & slug (a-z0-9) sebelum semak keunikan.
        $this->code = Str::upper($this->code);
        $this->slug = Str::slug($this->slug !== '' ? $this->slug : $this->name);

        $this->validate([
            'code' => 'unique:mosques,code',
            'slug' => 'unique:mosques,slug',
        ], [], ['code' => 'kod akronim', 'slug' => 'slug']);

        DB::transaction(function () use ($data) {
            $mosque = Mosque::query()->create([
                'name' => $data['name'],
                'slug' => $this->slug,
                'code' => $this->code,
                'state' => $data['state'],
                'district' => $data['district'] ?: null,
                'phone' => $data['phone_wa'],
                'status' => MosqueStatus::Menunggu,
                'storage_quota_bytes' => (int) config('diwan.default_quota_gb', 20) * (1024 ** 3),
                'settings' => ['wa_intake_enabled' => true, 'wa_intake_keyword' => 'spdm'],
                'retention_ack_at' => now(),
            ]);

            // Pengguna admin — attach jika e-mel wujud; jika baharu, cipta (belum aktif).
            $user = User::query()->firstOrNew(['email' => $data['email']]);
            if (! $user->exists) {
                $user->fill([
                    'name' => $data['admin_name'],
                    'phone_wa' => $data['phone_wa'],
                    'is_active' => false,
                    'password' => null,
                ])->save();
            }

            $mosque->users()->syncWithoutDetaching([
                $user->id => ['role' => 'admin_masjid', 'joined_at' => now()],
            ]);

            $mosque->update(['retention_ack_by' => $user->id]);
        });

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.register-mosque', [
            'states' => config('diwan.states', []),
        ])->layout('components.guest-layout', ['title' => 'Daftar Masjid — Diwan']);
    }
}
