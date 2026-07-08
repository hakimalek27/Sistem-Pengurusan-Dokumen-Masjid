<div class="card">
    @if ($sent)
        <div class="ok">
            Jika e-mel <strong>{{ $email }}</strong> berdaftar &amp; aktif, pautan log masuk telah
            dihantar. Sila semak peti masuk anda (pautan sah 15 minit, sekali guna).
        </div>
        <p class="muted"><a href="{{ url('/') }}">Kembali ke laman utama</a></p>
    @else
        <h2>Log Masuk</h2>
        <p class="muted" style="margin-top:0; text-align:left;">
            Masukkan e-mel anda dan kami akan hantar pautan log masuk selamat (tanpa kata laluan).
        </p>
        <form wire:submit="send">
            <label>E-mel</label>
            <input type="email" wire:model="email">
            @error('email') <div class="err">{{ $message }}</div> @enderror
            <div style="margin-top:1rem;">
                <button type="submit" class="btn">Hantar Pautan Log Masuk</button>
            </div>
        </form>
        <p class="muted"><a href="{{ url('/daftar') }}">Belum berdaftar? Daftar Masjid</a></p>
    @endif
</div>
