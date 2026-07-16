<?php

namespace App\Livewire;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\WhatsAppRecipientResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public bool $registrationOpen = true;

    public function mount(): void
    {
        $this->registrationOpen = (bool) PlatformSetting::get('registration_open', true);
    }

    public function updatedName(): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function submit(): void
    {
        if (! (bool) PlatformSetting::get('registration_open', true)) {
            $this->registrationOpen = false;
            throw ValidationException::withMessages([
                'name' => 'Pendaftaran tenant baharu ditutup sementara oleh pentadbir platform.',
            ]);
        }

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
        $phoneWa = app(WhatsAppRecipientResolver::class)->normalize($data['phone_wa']);
        if (! $phoneWa) {
            throw ValidationException::withMessages([
                'phone_wa' => 'Nombor WhatsApp tidak sah.',
            ]);
        }

        // Akaun sedia ada boleh menyertai tenant lain melalui e-mel yang sama,
        // tetapi nombor global milik akaun lain tidak boleh dirampas.
        $existingUser = User::query()->where('email', strtolower($data['email']))->first();
        $this->phone_wa = $phoneWa;

        $this->validate([
            'code' => 'unique:mosques,code',
            'slug' => 'unique:mosques,slug',
            'phone_wa' => [Rule::unique('users', 'phone_wa')->ignore($existingUser?->id)],
        ], [], [
            'code' => 'kod akronim',
            'slug' => 'slug',
            'phone_wa' => 'nombor WhatsApp',
        ]);

        DB::transaction(function () use ($data, $phoneWa) {
            $mosque = Mosque::query()->create([
                'name' => $data['name'],
                'slug' => $this->slug,
                'code' => $this->code,
                'state' => $data['state'],
                'district' => $data['district'] ?: null,
                'phone' => $phoneWa,
                'status' => MosqueStatus::Menunggu,
                'storage_quota_bytes' => (int) config('diwan.default_quota_gb', 20) * (1024 ** 3),
                'settings' => [
                    'wa_intake_enabled' => true,
                    'wa_intake_keyword' => 'spdm',
                    'mail_intake_enabled' => true,
                    'mail_intake_keyword' => 'spdm',
                    'mail_intake_senders' => [strtolower($data['email'])],
                ],
                'retention_ack_at' => now(),
            ]);

            // Pengguna admin — attach jika e-mel wujud; jika baharu, cipta (belum aktif).
            $user = User::query()->firstOrNew(['email' => $data['email']]);
            if (! $user->exists) {
                $user->fill([
                    'name' => $data['admin_name'],
                    'phone_wa' => $phoneWa,
                    'is_active' => false,
                    'password' => null,
                ])->save();
            }

            $mosque->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => 'admin_masjid',
                    'phone_wa' => $phoneWa,
                    'notify_whatsapp' => true,
                    'joined_at' => now(),
                ],
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
