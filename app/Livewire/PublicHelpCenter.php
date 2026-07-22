<?php

namespace App\Livewire;

use Livewire\Component;

class PublicHelpCenter extends Component
{
    public function mount(): void
    {
        abort_unless(config('diwan.guidance.enabled'), 404);
    }

    public function render()
    {
        return view('livewire.public-help-center')
            ->layout('components.guest-layout', ['title' => 'Pusat Bantuan — Diwan']);
    }
}
