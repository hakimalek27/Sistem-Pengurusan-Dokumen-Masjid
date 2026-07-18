<div class="card">
    <h2>Tetapkan Kata Laluan</h2>
    <p class="muted" style="margin-top:0; text-align:left;">
        Sebelum meneruskan, sila tetapkan kata laluan untuk akaun anda. Selepas ini
        anda boleh log masuk dengan e-mel/telefon &amp; kata laluan (pautan log masuk
        e-mel/WhatsApp tetap boleh digunakan).
    </p>
    <form wire:submit="save">
        <label>Kata Laluan Baharu</label>
        <input type="password" wire:model="password" autocomplete="new-password">
        @error('password') <div class="err">{{ $message }}</div> @enderror

        <label style="margin-top:1rem; display:block;">Sahkan Kata Laluan</label>
        <input type="password" wire:model="password_confirmation" autocomplete="new-password">

        <div style="margin-top:1rem;">
            <button type="submit" class="btn">Simpan &amp; Teruskan</button>
        </div>
    </form>
</div>
