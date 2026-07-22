<div class="diwan-help-launcher" data-diwan-help-runtime
    @if ($guide) data-guide-id="{{ $guide['id'] }}" @endif
    data-panel="{{ $panel }}"
    data-auto-start="{{ $autoStart ? '1' : '0' }}"
    data-mode="{{ $mode }}"
    data-resume-step="{{ $resumeStep }}"
    data-help-url="{{ $helpUrl }}">
    @if ($showButton)
        <a href="{{ $helpUrl }}" class="diwan-help-launcher-button" data-help-target="help-launcher"
            aria-label="Buka Pembantu Diwan" title="Buka Pembantu Diwan">
            <x-filament::icon icon="heroicon-o-lifebuoy" aria-hidden="true" />
            <span class="diwan-help-launcher-label" aria-hidden="true">Pembantu Diwan</span>
            @if ($taskCount > 0)
                <b aria-label="{{ $taskCount }} tugasan perlu tindakan">{{ min($taskCount, 99) }}</b>
            @endif
        </a>
    @endif
    @if ($guide)
        <script type="application/json" data-diwan-guide-payload>@json($guide, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    @endif
</div>
