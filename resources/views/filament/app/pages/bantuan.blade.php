<x-filament-panels::page>
    <div data-help-target="page-content">
        <livewire:help-center panel="app" :mosque-id="filament()->getTenant()->id" :origin-path="request()->query('asal', request()->path())" :request-id="request()->attributes->get('request_id')" />
    </div>
</x-filament-panels::page>
