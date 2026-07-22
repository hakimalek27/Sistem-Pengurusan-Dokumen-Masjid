<x-filament-panels::page>
    <div data-help-target="page-content">
        <livewire:help-center panel="admin" :origin-path="request()->query('asal', request()->path())" :request-id="request()->attributes->get('request_id')" />
    </div>
</x-filament-panels::page>
