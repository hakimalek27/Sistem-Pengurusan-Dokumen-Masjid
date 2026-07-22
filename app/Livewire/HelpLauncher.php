<?php

namespace App\Livewire;

use App\Models\GuidanceProgress;
use App\Models\Mosque;
use App\Services\GuidanceService;
use App\Services\HelpCatalog;
use App\Services\UserTaskService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class HelpLauncher extends Component
{
    #[Locked]
    public string $panel = 'public';

    #[Locked]
    public ?int $mosqueId = null;

    public bool $showButton = true;

    public function mount(string $panel = 'public', bool $showButton = true): void
    {
        $this->panel = in_array($panel, ['public', 'app', 'admin'], true) ? $panel : 'public';
        $this->showButton = $showButton;
        $tenant = $this->panel === 'app' ? Filament::getTenant() : null;
        $this->mosqueId = $tenant instanceof Mosque ? $tenant->id : null;
    }

    #[On('guidanceProgress')]
    public function guidanceProgress(string $guideId, string $event, int $stepIndex = 0, ?string $target = null): void
    {
        if (! in_array($event, ['started', 'progressed', 'completed', 'dismissed', 'target_missing'], true)) {
            return;
        }
        $guide = app(HelpCatalog::class)->findVisible($guideId, $this->panel, Auth::user(), $this->mosque());
        if (! $guide) {
            return;
        }

        if ($user = Auth::user()) {
            app(GuidanceService::class)->record($user, $this->panel, $this->mosque(), $guide, $event, $stepIndex, $target);
        } else {
            session()->put("diwan_help.public.{$guideId}", ['status' => $event, 'step_index' => $stepIndex]);
        }
    }

    public function render()
    {
        $user = Auth::user();
        $mosque = $this->mosque();
        if (($this->panel === 'app' && (! $user || ! $mosque)) || ($this->panel === 'admin' && ! $user?->is_superadmin)) {
            return view('livewire.help-launcher', ['guide' => null, 'autoStart' => false, 'taskCount' => 0, 'helpUrl' => '/bantuan', 'resumeStep' => 0, 'mode' => 'lengkap']);
        }

        $catalog = app(HelpCatalog::class);
        $requestedId = request()->query('panduan');
        $guide = is_string($requestedId)
            ? $catalog->findVisible($requestedId, $this->panel, $user, $mosque)
            : $catalog->currentGuide('/'.request()->path(), $this->panel, $user, $mosque);
        $autoStart = filled($requestedId);
        $resumeStep = max(0, (int) request()->query('langkah', 0));
        $taskCount = 0;
        $mode = 'lengkap';

        if ($user) {
            $preference = app(GuidanceService::class)->preference($user, $this->panel, $mosque);
            $mode = $preference->mode;
            $snoozed = $preference->snoozed_until?->isFuture() ?? false;
            if (! $requestedId && $guide && $preference->mode !== 'dimatikan' && $preference->auto_start_enabled && ! $snoozed) {
                $progress = GuidanceProgress::query()
                    ->where('user_id', $user->id)
                    ->where('context_key', app(GuidanceService::class)->contextKey($this->panel, $mosque))
                    ->where('guide_id', $guide['id'])->first();
                $autoStart = ! $progress || $progress->guide_version < (int) ($guide['version'] ?? 1);
            }
            if ($preference->mode !== 'dimatikan' && $preference->nudges_enabled && ! $snoozed) {
                $taskCount = app(UserTaskService::class)->actionableCount($user, $this->panel, $mosque);
            }
        } elseif ($guide && ! $requestedId) {
            $autoStart = ! session()->has("diwan_help.public.{$guide['id']}");
        }

        $origin = '/'.request()->path();
        $helpUrl = match ($this->panel) {
            'app' => '/app/'.$mosque->slug.'/bantuan?asal='.urlencode($origin),
            'admin' => '/admin/bantuan?asal='.urlencode($origin),
            default => '/bantuan?asal='.urlencode($origin),
        };

        return view('livewire.help-launcher', compact('guide', 'autoStart', 'taskCount', 'helpUrl', 'resumeStep', 'mode'));
    }

    protected function mosque(): ?Mosque
    {
        if (! $this->mosqueId) {
            return null;
        }
        $mosque = Mosque::query()->find($this->mosqueId);

        return $mosque && Auth::user()?->isMemberOf($mosque) ? $mosque : null;
    }
}
