<?php

namespace App\Livewire;

use App\Services\MagicLinkService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

// §9.C.1 — Halaman "Hantar Pautan Log Masuk" (/log-masuk). Rate limit 5/min/IP.
class RequestMagicLink extends Component
{
    #[Validate('required|email|max:255')]
    public string $email = '';

    public bool $sent = false;

    public function send(MagicLinkService $magic): void
    {
        $this->validate();

        $key = 'magic-link:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Terlalu banyak percubaan. Sila cuba lagi sebentar.');

            return;
        }

        RateLimiter::hit($key, 60);

        // Sentiasa papar mesej sama tanpa mengira kewujudan akaun (elak enumerasi e-mel).
        $magic->sendTo($this->email, request()->ip());

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.request-magic-link')
            ->layout('components.guest-layout', ['title' => 'Log Masuk — Diwan']);
    }
}
