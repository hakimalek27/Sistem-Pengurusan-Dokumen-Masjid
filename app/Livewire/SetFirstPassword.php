<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Fasa B / §15.1 — Tetapkan kata laluan kali pertama (untuk akaun magic-link
 * tanpa kata laluan). Selepas set → teruskan ke destinasi asal (intended).
 */
class SetFirstPassword extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        // Sudah ada kata laluan → tidak perlu berada di sini.
        if (Auth::check() && Auth::user()->password !== null) {
            $this->redirectIntended('/app');
        }
    }

    public function save(): void
    {
        $this->validate([
            'password' => ['required', 'confirmed', Password::default()],
        ]);

        Auth::user()->update(['password' => Hash::make($this->password)]);

        session()->flash('status', 'Kata laluan berjaya ditetapkan.');
        $this->redirectIntended('/app');
    }

    public function render()
    {
        return view('livewire.set-first-password')
            ->layout('components.guest-layout', ['title' => 'Tetapkan Kata Laluan — Diwan']);
    }
}
