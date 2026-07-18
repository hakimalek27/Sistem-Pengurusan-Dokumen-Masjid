<div class="card">
    @if ($sent)
        <div class="ok">
            Jika <strong>{{ $login }}</strong> berdaftar &amp; aktif, pautan log masuk telah
            dihantar melalui e-mel/WhatsApp. Sila semak (pautan sah 15 minit, sekali guna).
        </div>
        <p class="muted"><a href="{{ url('/') }}">Kembali ke laman utama</a></p>
    @else
        <h2>Log Masuk</h2>
        <p class="muted" style="margin-top:0; text-align:left;">
            Masukkan e-mel atau no. telefon anda dan kami akan hantar pautan log masuk
            selamat (tanpa kata laluan).
        </p>
        <form wire:submit="send">
            <label>E-mel atau No. Telefon</label>
            <input type="text" wire:model="login">
            @error('login') <div class="err">{{ $message }}</div> @enderror
            <div style="margin-top:1rem;">
                <button type="submit" class="btn">Hantar Pautan Log Masuk</button>
            </div>
        </form>
        <hr style="margin:1.25rem 0; border:none; border-top:1px solid #e5e7eb;">
        <p class="muted" style="text-align:center; margin:0;">
            Sudah tetapkan kata laluan?
            <a href="{{ url('/app/login') }}">Log masuk dengan kata laluan</a>
        </p>
        <p class="muted"><a href="{{ url('/daftar') }}">Belum berdaftar? Daftar Masjid</a></p>
    @endif
</div>
