{{-- F5a (§6.1): `page-content` kini disediakan oleh <main> dalam components.guest-layout,
     jadi atribut di sini akan menjadi sasaran BERTINDAN dan bersarang. Registri §7.2
     menuntut sasaran aktif UNIK; `resolveStepElement()` pula memilih padanan pertama dalam
     susunan dokumen, jadi dua salinan menjadikan sasaran bergantung susunan. Dibuang. --}}
<div>
    <livewire:help-center panel="public" :origin-path="request()->query('asal', request()->path())" :request-id="request()->attributes->get('request_id')" />
</div>
