<div data-help-target="page-content">
    <livewire:help-center panel="public" :origin-path="request()->query('asal', request()->path())" :request-id="request()->attributes->get('request_id')" />
</div>
