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

    /**
     * Konteks halaman SEBENAR, ditawan sekali pada mount().
     *
     * Semasa kitaran update Livewire, `request()` ialah `POST /livewire/update` — membaca
     * konteks dari situ memulangkan guide `null` dan memusnahkan Pembantu Diwan pada
     * 19/25 halaman produksi (RR-01-02). Sifat komponen kekal merentas update, dan
     * mount() hanya berjalan pada muat halaman penuh, jadi nilai ini sentiasa halaman
     * yang pengguna benar-benar lihat.
     */
    #[Locked]
    public string $originPath = '/';

    #[Locked]
    public ?string $requestedGuideId = null;

    #[Locked]
    public int $requestedStep = 0;

    /**
     * Pencetus auto-start SEKALI-GUNA.
     *
     * Dahulu `?panduan=` dibaca terus dari request setiap render, jadi isyarat itu hilang
     * sendiri pada kitaran seterusnya. Setelah ia menjadi sifat yang kekal, isyarat itu
     * akan MELEKAT dan tour yang baru ditutup boleh bermula semula pada setiap
     * `bootRuntime()`. Sebab itu ia dipadam apabila tour dimulakan/ditutup/selesai.
     */
    #[Locked]
    public bool $launchPending = false;

    public bool $showButton = true;

    public function mount(string $panel = 'public', bool $showButton = true): void
    {
        $this->panel = in_array($panel, ['public', 'app', 'admin'], true) ? $panel : 'public';
        $this->showButton = $showButton;
        $tenant = $this->panel === 'app' ? Filament::getTenant() : null;
        $this->mosqueId = $tenant instanceof Mosque ? $tenant->id : null;

        // Root: path() = '/' → trim = '' → hasil '/' (bukan '//' seperti dahulu — RR-01-11).
        $this->originPath = '/'.trim(request()->path(), '/');
        $requested = request()->query('panduan');
        $this->requestedGuideId = is_string($requested) ? $requested : null;
        $this->requestedStep = max(0, (int) request()->query('langkah', 0));
        $this->launchPending = filled($this->requestedGuideId);
    }

    #[On('guidanceProgress')]
    public function guidanceProgress(string $guideId, string $event, int $stepIndex = 0, ?string $target = null): void
    {
        if (! in_array($event, ['started', 'progressed', 'completed', 'dismissed', 'target_missing'], true)) {
            return;
        }

        // Padam pencetus SEBELUM guard findVisible() di bawah: kalau guide itu tidak lagi
        // kelihatan (kebenaran ditarik, guide dibuang katalog), guard memulangkan awal dan
        // launchPending akan kekal true selama-lamanya. Selamat kerana perbandingannya
        // dengan sifat #[Locked] yang ditetapkan server.
        if ($guideId === $this->requestedGuideId
            && in_array($event, ['started', 'dismissed', 'completed'], true)) {
            $this->launchPending = false;
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

        // Telemetri tidak mengubah HTML launcher — jangan render semula. Kesan yang
        // diterima: badge $taskCount tidak segar pada kitaran telemetri (ia segar pada
        // interaksi lain).
        $this->skipRender();
    }

    public function render()
    {
        $user = Auth::user();
        $mosque = $this->mosque();
        if (($this->panel === 'app' && (! $user || ! $mosque)) || ($this->panel === 'admin' && ! $user?->is_superadmin)) {
            return view('livewire.help-launcher', ['guide' => null, 'autoStart' => false, 'taskCount' => 0, 'helpUrl' => '/bantuan', 'resumeStep' => 0, 'mode' => 'lengkap']);
        }

        $catalog = app(HelpCatalog::class);
        $requestedId = $this->requestedGuideId;
        $guide = is_string($requestedId)
            ? $catalog->findVisible($requestedId, $this->panel, $user, $mosque)
            : $catalog->currentGuide($this->originPath, $this->panel, $user, $mosque);
        $autoStart = $this->launchPending;
        $resumeStep = $this->requestedStep;
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

        $origin = $this->originPath;
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
