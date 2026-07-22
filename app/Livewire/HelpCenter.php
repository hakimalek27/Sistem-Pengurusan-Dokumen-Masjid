<?php

namespace App\Livewire;

use App\Models\HelpAnnouncement;
use App\Models\Mosque;
use App\Models\SupportRequest;
use App\Services\GuidanceService;
use App\Services\HelpCatalog;
use App\Services\HelpDiagnosisService;
use App\Services\HelpSearchService;
use App\Services\SupportRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class HelpCenter extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $panel = 'public';

    #[Locked]
    public ?int $mosqueId = null;

    #[Locked]
    public string $originPath = '/bantuan';

    #[Locked]
    public ?string $requestId = null;

    public string $query = '';

    public array $results = [];

    public ?string $selectedGuideId = null;

    public array $selectedGuide = [];

    public string $diagnosisCategory = 'login';

    public array $diagnosisResults = [];

    public string $mode = 'lengkap';

    public bool $autoStartEnabled = true;

    public bool $nudgesEnabled = true;

    public bool $digestEmail = false;

    public bool $digestWhatsapp = false;

    public bool $digestTelegram = false;

    public string $quietHoursStart = '';

    public string $quietHoursEnd = '';

    public string $supportCategory = 'lain';

    public string $supportSubject = '';

    public string $supportExpected = '';

    public string $supportActual = '';

    public bool $queryConsent = false;

    public string $unmatchedQuery = '';

    public $supportAttachment = null;

    public string $browserContextJson = '{}';

    public ?string $submittedReference = null;

    public function mount(string $panel = 'public', ?int $mosqueId = null, ?string $originPath = null, ?string $requestId = null): void
    {
        abort_unless(in_array($panel, ['public', 'app', 'admin'], true), 404);
        $user = Auth::user();
        $mosque = $mosqueId ? Mosque::query()->findOrFail($mosqueId) : null;

        if ($panel === 'app') {
            abort_unless($user && $mosque && $user->isMemberOf($mosque), 404);
        }
        if ($panel === 'admin') {
            abort_unless($user?->is_superadmin, 403);
        }

        $this->panel = $panel;
        $this->mosqueId = $mosque?->id;
        $this->originPath = parse_url($originPath ?: request()->path(), PHP_URL_PATH) ?: '/';
        $this->requestId = $requestId ?: request()->attributes->get('request_id');
        $this->results = app(HelpCatalog::class)->forContext($panel, $user, $mosque)->take(12)->all();

        if (is_string(request()->query('artikel'))) {
            $guide = app(HelpCatalog::class)->findVisible((string) request()->query('artikel'), $panel, $user, $mosque);
            if ($guide) {
                $this->selectedGuideId = $guide['id'];
                $this->selectedGuide = $guide;
            }
        }

        if ($user) {
            $preference = app(GuidanceService::class)->preference($user, $panel, $mosque);
            $this->mode = $preference->mode;
            $this->autoStartEnabled = $preference->auto_start_enabled;
            $this->nudgesEnabled = $preference->nudges_enabled;
            $this->digestEmail = $preference->digest_email;
            $this->digestWhatsapp = $preference->digest_whatsapp;
            $this->digestTelegram = $preference->digest_telegram;
            $this->quietHoursStart = $preference->quiet_hours_start ? substr((string) $preference->quiet_hours_start, 0, 5) : '';
            $this->quietHoursEnd = $preference->quiet_hours_end ? substr((string) $preference->quiet_hours_end, 0, 5) : '';
        }
    }

    public function search(): void
    {
        $this->throttlePublic('search', 30);
        $this->validate(['query' => ['nullable', 'string', 'max:200']]);
        $this->results = app(HelpSearchService::class)
            ->search($this->query, $this->panel, Auth::user(), $this->mosque())
            ->all();
        $this->unmatchedQuery = $this->results === [] ? $this->query : '';
        $this->selectedGuide = [];
        $this->selectedGuideId = null;
    }

    public function selectGuide(string $guideId): void
    {
        $guide = app(HelpCatalog::class)->findVisible($guideId, $this->panel, Auth::user(), $this->mosque());
        abort_unless($guide, 404);
        $this->selectedGuideId = $guideId;
        $this->selectedGuide = $guide;
    }

    public function startGuide(string $guideId)
    {
        $guide = app(HelpCatalog::class)->findVisible($guideId, $this->panel, Auth::user(), $this->mosque());
        abort_unless($guide, 404);

        $stepIndex = 0;
        if ($user = Auth::user()) {
            $guidance = app(GuidanceService::class);
            $stepIndex = $guidance->resumeStep($user, $this->panel, $this->mosque(), $guide);
            $guidance->record($user, $this->panel, $this->mosque(), $guide, 'started', $stepIndex);
        } else {
            $state = session()->get("diwan_help.public.{$guideId}", []);
            if (in_array($state['status'] ?? null, ['started', 'progressed', 'dismissed', 'target_missing', 'dalam_proses'], true)) {
                $stepIndex = min(max(0, (int) ($state['step_index'] ?? 0)), max(0, count($guide['steps']) - 1));
            }
            session()->put("diwan_help.public.{$guideId}", ['status' => 'dalam_proses', 'step_index' => $stepIndex]);
        }

        return $this->redirect($guide['route'].'?panduan='.urlencode($guideId).'&langkah='.$stepIndex, navigate: false);
    }

    public function runDiagnosis(): void
    {
        $this->throttlePublic('diagnosis', 30);
        $this->validate(['diagnosisCategory' => [Rule::in(array_keys(HelpDiagnosisService::CATEGORIES))]]);
        $this->diagnosisResults = app(HelpDiagnosisService::class)
            ->diagnose($this->diagnosisCategory, Auth::user(), $this->mosque(), $this->panel);
    }

    public function savePreferences(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->validate([
            'mode' => ['required', Rule::in(['lengkap', 'ringkas', 'dimatikan'])],
            'quietHoursStart' => ['nullable', 'date_format:H:i'],
            'quietHoursEnd' => ['nullable', 'date_format:H:i'],
        ]);

        app(GuidanceService::class)->preference($user, $this->panel, $this->mosque())->update([
            'mode' => $this->mode,
            'auto_start_enabled' => $this->autoStartEnabled,
            'nudges_enabled' => $this->nudgesEnabled,
            'digest_email' => $this->digestEmail,
            'digest_whatsapp' => $this->digestWhatsapp,
            'digest_telegram' => $this->digestTelegram,
            'quiet_hours_start' => $this->quietHoursStart ?: null,
            'quiet_hours_end' => $this->quietHoursEnd ?: null,
        ]);
        session()->flash('help_message', 'Tetapan bantuan disimpan.');
    }

    public function snooze(int $days): void
    {
        abort_unless(in_array($days, [1, 7], true), 422);
        $user = Auth::user();
        abort_unless($user, 403);
        app(GuidanceService::class)->preference($user, $this->panel, $this->mosque())
            ->update(['snoozed_until' => now()->addDays($days)]);
        session()->flash('help_message', "Cadangan disenyapkan selama {$days} hari.");
    }

    public function submitSupport(): void
    {
        $this->validate([
            'supportCategory' => ['required', Rule::in([...array_keys(HelpDiagnosisService::CATEGORIES), 'lain'])],
            'supportSubject' => ['required', 'string', 'min:5', 'max:180'],
            'supportExpected' => ['required', 'string', 'min:5', 'max:5000'],
            'supportActual' => ['required', 'string', 'min:5', 'max:5000'],
            'supportAttachment' => ['nullable', 'file', 'max:5120'],
            'unmatchedQuery' => ['nullable', 'string', 'max:500'],
        ]);

        $key = 'support:'.(Auth::id() ?: request()->ip());
        if (RateLimiter::tooManyAttempts($key, Auth::check() ? 10 : 3)) {
            throw ValidationException::withMessages(['supportSubject' => 'Had laporan masalah dicapai. Sila cuba lagi kemudian.']);
        }
        RateLimiter::hit($key, 3600);

        $browser = json_decode($this->browserContextJson, true);
        $browser = is_array($browser) ? $browser : [];
        $browser['user_agent'] = request()->userAgent();
        $ticket = app(SupportRequestService::class)->create([
            'category' => $this->supportCategory,
            'subject' => $this->supportSubject,
            'expected' => $this->supportExpected,
            'actual' => $this->supportActual,
            'route_template' => $this->originPath,
            'request_id' => $this->requestId,
            'browser_context' => $browser,
            'query_consent' => $this->queryConsent,
            'unmatched_query' => $this->unmatchedQuery,
        ], Auth::user(), $this->mosque(), $this->panel, $this->supportAttachment);

        $this->submittedReference = $ticket->reference;
        $this->reset(['supportSubject', 'supportExpected', 'supportActual', 'supportAttachment', 'queryConsent']);
    }

    public function render()
    {
        $user = Auth::user();
        $mosque = $this->mosque();
        $announcements = HelpAnnouncement::query()->currentlyVisible(
            $this->panel,
            $this->panel === 'admin' ? 'superadmin' : ($user && $mosque ? $user->roleIn($mosque) : 'public'),
            $mosque?->id,
        )->latest()->limit(5)->get();

        $tickets = SupportRequest::query()
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->when(! $user, fn ($query) => $query->where('reporter_session_hash', hash_hmac('sha256', session()->getId(), (string) config('app.key'))))
            ->when($mosque, fn ($query) => $query->where('mosque_id', $mosque->id))
            ->latest()->limit(10)->get();

        return view('livewire.help-center', [
            'announcements' => $announcements,
            'tickets' => $tickets,
            'diagnosisCategories' => HelpDiagnosisService::CATEGORIES,
            'catalogVersion' => app(HelpCatalog::class)->version(),
        ]);
    }

    protected function mosque(): ?Mosque
    {
        if (! $this->mosqueId) {
            return null;
        }
        $mosque = Mosque::query()->find($this->mosqueId);
        abort_unless($mosque && Auth::user()?->isMemberOf($mosque), 404);

        return $mosque;
    }

    protected function throttlePublic(string $action, int $maxAttempts): void
    {
        if ($this->panel !== 'public') {
            return;
        }
        $key = "public-help:{$action}:".request()->ip();
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages(['query' => 'Had penggunaan bantuan dicapai. Sila cuba lagi sebentar.']);
        }
        RateLimiter::hit($key, 60);
    }
}
