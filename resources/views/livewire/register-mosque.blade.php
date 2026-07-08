<div class="card">
    @if ($submitted)
        <div class="ok">
            <strong>Permohonan diterima!</strong><br>
            Masjid anda kini <em>menunggu kelulusan</em> platform. Kami akan menghantar pautan
            log masuk ke e-mel anda sebaik sahaja diluluskan.
        </div>
        <p class="muted"><a href="{{ url('/') }}">Kembali ke laman utama</a></p>
    @else
        <h2>Daftar Masjid</h2>
        <form wire:submit="submit">
            <label>Nama Masjid <span style="color:#b91c1c">*</span></label>
            <input type="text" wire:model.blur="name" placeholder="cth Masjid Al-Muttaqin Wangsa Melawati">
            @error('name') <div class="err">{{ $message }}</div> @enderror

            <label>Negeri <span style="color:#b91c1c">*</span></label>
            <select wire:model="state">
                <option value="">— Pilih negeri —</option>
                @foreach ($states as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
            @error('state') <div class="err">{{ $message }}</div> @enderror

            <label>Daerah</label>
            <input type="text" wire:model="district">

            <label>Cadangan Kod Akronim <span style="color:#b91c1c">*</span> (3–6 huruf)</label>
            <input type="text" wire:model="code" maxlength="6" style="text-transform:uppercase;" placeholder="MAM">
            @error('code') <div class="err">{{ $message }}</div> @enderror

            <label>Slug URL <span style="color:#b91c1c">*</span> (a–z, 0–9)</label>
            <input type="text" wire:model="slug" placeholder="mam">
            @error('slug') <div class="err">{{ $message }}</div> @enderror

            <label>Nama Pentadbir <span style="color:#b91c1c">*</span></label>
            <input type="text" wire:model="admin_name">
            @error('admin_name') <div class="err">{{ $message }}</div> @enderror

            <label>E-mel <span style="color:#b91c1c">*</span></label>
            <input type="email" wire:model="email">
            @error('email') <div class="err">{{ $message }}</div> @enderror

            <label>No. WhatsApp <span style="color:#b91c1c">*</span> (cth 60123456789)</label>
            <input type="text" wire:model="phone_wa" placeholder="60123456789">
            @error('phone_wa') <div class="err">{{ $message }}</div> @enderror

            <label class="check">
                <input type="checkbox" wire:model="agree_terms">
                <span>Saya bersetuju dengan Terma Perkhidmatan &amp; Perjanjian Pemprosesan Data (DPA).</span>
            </label>
            @error('agree_terms') <div class="err">{{ $message }}</div> @enderror

            <label class="check">
                <input type="checkbox" wire:model="agree_retention">
                <span>Saya memahami dasar retensi: dokumen disimpan mengikut jadual (lalai 7 tahun;
                    minit mesyuarat/perjanjian/sijil/laporan kekal). Selepas notifikasi 90/30/7 hari,
                    rekod cukup tempoh <strong>akan dipadam automatik dan tidak boleh dikembalikan</strong>;
                    metadata rekod kekal.</span>
            </label>
            @error('agree_retention') <div class="err">{{ $message }}</div> @enderror

            <div style="margin-top:1rem;">
                <button type="submit" class="btn">Hantar Permohonan</button>
            </div>
        </form>
        <p class="muted"><a href="{{ url('/log-masuk') }}">Sudah ada akaun? Log masuk</a></p>
    @endif
</div>
