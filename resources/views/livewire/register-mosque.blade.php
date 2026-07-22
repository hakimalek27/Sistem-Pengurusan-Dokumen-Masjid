<div class="card">
    @if (! $registrationOpen)
        <div class="err">
            <strong>Pendaftaran ditutup sementara.</strong><br>
            Sila hubungi pentadbir platform jika organisasi anda memerlukan akaun baharu.
        </div>
        <p class="muted"><a href="{{ url('/log-masuk') }}">Sudah ada akaun? Log masuk</a></p>
    @elseif ($submitted)
        <div class="ok" data-help-target="registration-complete">
            <strong>Permohonan diterima!</strong><br>
            Masjid anda kini <em>menunggu kelulusan</em> platform. Kami akan menghantar pautan
            log masuk ke e-mel anda sebaik sahaja diluluskan.
        </div>
        <p class="muted"><a href="{{ url('/') }}">Kembali ke laman utama</a></p>
    @else
        <h2>Daftar Masjid</h2>
        <ol class="registration-steps" aria-label="Kemajuan pendaftaran">
            <li class="{{ $step >= 1 ? 'active' : '' }}"><b>1</b><span>Masjid</span></li>
            <li class="{{ $step >= 2 ? 'active' : '' }}"><b>2</b><span>Pentadbir</span></li>
            <li class="{{ $step >= 3 ? 'active' : '' }}"><b>3</b><span>Persetujuan</span></li>
        </ol>
        <form wire:submit="submit" data-help-target="registration-{{ $step === 1 ? 'organisation' : ($step === 2 ? 'admin' : 'consent') }}">
            @if ($step === 1)
                <section wire:key="registration-step-organisation">
                    <label>Nama Masjid <span style="color:#b91c1c">*</span></label>
                    <input type="text" wire:model.blur="name" placeholder="cth Masjid Al-Muttaqin Wangsa Melawati">
                    @error('name') <div class="err">{{ $message }}</div> @enderror

                    <label>Negeri <span style="color:#b91c1c">*</span></label>
                    <select wire:model="state">
                        <option value="">— Pilih negeri —</option>
                        @foreach ($states as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                    @error('state') <div class="err">{{ $message }}</div> @enderror

                    <label>Daerah</label>
                    <input type="text" wire:model="district">

                    <label>Cadangan Kod Akronim <span style="color:#b91c1c">*</span> (3–6 huruf)</label>
                    <input type="text" wire:model="code" maxlength="6" style="text-transform:uppercase;" placeholder="MAM">
                    @error('code') <div class="err">{{ $message }}</div> @enderror

                    <label>Slug URL <span style="color:#b91c1c">*</span> (a–z, 0–9 dan sengkang)</label>
                    <input type="text" wire:model="slug" placeholder="mam">
                    @error('slug') <div class="err">{{ $message }}</div> @enderror
                </section>
            @elseif ($step === 2)
                <section wire:key="registration-step-admin">
                    <label>Nama Pentadbir <span style="color:#b91c1c">*</span></label>
                    <input type="text" wire:model="admin_name" autocomplete="name">
                    @error('admin_name') <div class="err">{{ $message }}</div> @enderror

                    <label>E-mel <span style="color:#b91c1c">*</span></label>
                    <input type="email" wire:model="email" autocomplete="email">
                    @error('email') <div class="err">{{ $message }}</div> @enderror

                    <label>No. WhatsApp <span style="color:#b91c1c">*</span> (cth 60123456789)</label>
                    <input type="text" wire:model="phone_wa" placeholder="60123456789" inputmode="tel" autocomplete="tel">
                    @error('phone_wa') <div class="err">{{ $message }}</div> @enderror
                </section>
            @else
                <section wire:key="registration-step-consent">
                    <div class="registration-review">
                        <strong>{{ $name }}</strong>
                        <span>{{ strtoupper($code) }} · {{ $state }}{{ $district ? ' · '.$district : '' }}</span>
                        <span>Pentadbir: {{ $admin_name }} · {{ $email }} · {{ $phone_wa }}</span>
                    </div>
                    <label class="check">
                        <input type="checkbox" wire:model="agree_terms">
                        <span>Saya bersetuju dengan Terma Perkhidmatan &amp; Perjanjian Pemprosesan Data (DPA).</span>
                    </label>
                    @error('agree_terms') <div class="err">{{ $message }}</div> @enderror

                    <label class="check">
                        <input type="checkbox" wire:model="agree_retention">
                        <span>Saya memahami dasar retensi: dokumen disimpan mengikut jadual (lalai 7 tahun; minit mesyuarat/perjanjian/sijil/laporan kekal). Selepas notifikasi 90/30/7 hari, rekod cukup tempoh <strong>akan dipadam automatik dan tidak boleh dikembalikan</strong>; metadata rekod kekal.</span>
                    </label>
                    @error('agree_retention') <div class="err">{{ $message }}</div> @enderror
                </section>
            @endif

            <div class="registration-actions">
                @if ($step > 1)<button type="button" class="btn btn-ghost" wire:click="previousStep" data-help-target="registration-previous">Kembali</button>@endif
                @if ($step < 3)
                    <button type="button" class="btn" wire:click="nextStep" data-help-target="registration-next">Seterusnya</button>
                @else
                    <button type="submit" class="btn" data-help-target="registration-submit">Hantar Permohonan</button>
                @endif
            </div>
        </form>
        <p class="muted"><a href="{{ url('/log-masuk') }}">Sudah ada akaun? Log masuk</a></p>
    @endif
</div>
