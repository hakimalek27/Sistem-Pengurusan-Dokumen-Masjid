<div class="diwan-help" data-help-target="help-center">
    <input type="hidden" wire:model="browserContextJson"
        x-init="$wire.set('browserContextJson', JSON.stringify({ browser: navigator.userAgentData?.brands?.map(item => item.brand).join(', ') || 'browser', platform: navigator.userAgentData?.platform || navigator.platform, language: navigator.language, viewport: `${window.innerWidth}x${window.innerHeight}` }))">

    @if (session('help_message'))
        <div class="diwan-help-alert diwan-help-alert-success" role="status">{{ session('help_message') }}</div>
    @endif

    @if ($announcements->isNotEmpty())
        <section class="diwan-help-band" aria-labelledby="help-announcements">
            <h2 id="help-announcements">Makluman</h2>
            <div class="diwan-help-announcements">
                @foreach ($announcements as $announcement)
                    <article>
                        <strong>{{ $announcement->title }}</strong>
                        <p>{{ $announcement->body }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="diwan-help-band" aria-labelledby="help-search-title" data-help-target="help-search">
        <div class="diwan-help-heading">
            <div>
                <h2 id="help-search-title">Cari panduan</h2>
                <p>Versi katalog {{ $catalogVersion }}</p>
            </div>
        </div>

        <form wire:submit="search" class="diwan-help-search">
            <label for="help-query">Apa yang anda mahu lakukan?</label>
            <div>
                <input id="help-query" type="search" wire:model="query" maxlength="200"
                    placeholder="Contoh: nak klasifikasi surat" autocomplete="off">
                <button type="submit" wire:loading.attr="disabled">Cari</button>
            </div>
            @error('query') <p class="diwan-help-error">{{ $message }}</p> @enderror
        </form>

        <div class="diwan-help-results" aria-live="polite">
            @forelse ($results as $guide)
                <article class="diwan-help-result" wire:key="guide-{{ $guide['id'] }}">
                    @if (filled($guide['images'] ?? []))
                        <img class="diwan-help-thumb" src="{{ route('help-image.show', ['guideId' => $guide['id'], 'tenant' => $mosqueId]) }}" alt="Paparan latihan {{ $guide['title'] }}" loading="lazy">
                    @endif
                    <div>
                        <span class="diwan-help-kicker">{{ str_starts_with($guide['id'], 'workflow.') ? 'Workflow' : 'Panduan' }}</span>
                        <h3>{{ $guide['title'] }}</h3>
                        <p>{{ $guide['summary'] }}</p>
                    </div>
                    <div class="diwan-help-actions">
                        <button type="button" class="diwan-help-secondary" wire:click="selectGuide('{{ $guide['id'] }}')">Baca langkah</button>
                        <button type="button" wire:click="startGuide('{{ $guide['id'] }}')">Mulakan panduan</button>
                    </div>
                </article>
            @empty
                <div class="diwan-help-empty">
                    Tiada panduan sepadan. Jalankan diagnosis atau sertakan pertanyaan ini dalam laporan masalah dengan persetujuan anda.
                </div>
            @endforelse
        </div>
    </section>

    @if ($selectedGuide)
        <section class="diwan-help-band" aria-labelledby="selected-guide-title" data-help-target="help-guide-detail">
            <div class="diwan-help-heading">
                <div>
                    <span class="diwan-help-kicker">{{ $selectedGuide['id'] }}</span>
                    <h2 id="selected-guide-title">{{ $selectedGuide['title'] }}</h2>
                    <p>{{ $selectedGuide['outcome'] }}</p>
                </div>
                <a class="diwan-help-link" href="{{ $selectedGuide['route'] }}">Buka halaman</a>
            </div>
            <ol class="diwan-help-steps">
                @foreach ($selectedGuide['steps'] as $step)
                    <li>
                        <span>{{ $loop->iteration }}</span>
                        <div><strong>{{ $step['title'] }}</strong><p>{{ $step['instruction'] }}</p></div>
                    </li>
                @endforeach
            </ol>
            <div class="diwan-help-actions">
                <button type="button" wire:click="startGuide('{{ $selectedGuide['id'] }}')">Mulakan pada skrin</button>
            </div>
        </section>
    @endif

    <section class="diwan-help-band" aria-labelledby="diagnosis-title" data-help-target="help-diagnosis">
        <div class="diwan-help-heading">
            <div>
                <h2 id="diagnosis-title">Diagnosis masalah</h2>
                <p>Pemeriksaan baca sahaja mengikut akses semasa.</p>
            </div>
        </div>
        <div class="diwan-help-diagnosis-form">
            <label for="diagnosis-category">Jenis masalah</label>
            <select id="diagnosis-category" wire:model="diagnosisCategory">
                @foreach ($diagnosisCategories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="runDiagnosis">Periksa</button>
        </div>
        @if ($diagnosisResults)
            <div class="diwan-help-checks" aria-live="polite">
                @foreach ($diagnosisResults as $check)
                    <article class="is-{{ $check['severity'] }}">
                        <strong>{{ $check['title'] }}</strong>
                        <p>{{ $check['message'] }}</p>
                        <small>Pihak berkaitan: {{ $check['owner'] }}</small>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @auth
        <section class="diwan-help-band" aria-labelledby="preference-title" data-help-target="help-preferences">
            <div class="diwan-help-heading">
                <div>
                    <h2 id="preference-title">Tetapan bantuan</h2>
                    <p>Peringatan luar sistem kekal pilihan pengguna.</p>
                </div>
            </div>
            <form wire:submit="savePreferences" class="diwan-help-preferences">
                <fieldset>
                    <legend>Mod pembelajaran</legend>
                    <label><input type="radio" wire:model="mode" value="lengkap"> Lengkap</label>
                    <label><input type="radio" wire:model="mode" value="ringkas"> Ringkas</label>
                    <label><input type="radio" wire:model="mode" value="dimatikan"> Dimatikan</label>
                </fieldset>
                <fieldset>
                    <legend>Paparan dalam aplikasi</legend>
                    <label><input type="checkbox" wire:model="autoStartEnabled"> Panduan automatik penggunaan pertama</label>
                    <label><input type="checkbox" wire:model="nudgesEnabled"> Penunjuk tugasan</label>
                </fieldset>
                @if ($panel === 'app')
                    <fieldset>
                        <legend>Digest harian</legend>
                        <label><input type="checkbox" wire:model="digestEmail"> E-mel</label>
                        <label><input type="checkbox" wire:model="digestWhatsapp"> WhatsApp</label>
                        <label><input type="checkbox" wire:model="digestTelegram"> Telegram</label>
                    </fieldset>
                    <div class="diwan-help-time-grid">
                        <label>Quiet hours mula <input type="time" wire:model="quietHoursStart"></label>
                        <label>Quiet hours tamat <input type="time" wire:model="quietHoursEnd"></label>
                    </div>
                @endif
                <div class="diwan-help-actions">
                    <button type="submit">Simpan tetapan</button>
                    <button type="button" class="diwan-help-secondary" wire:click="snooze(1)">Senyap 1 hari</button>
                    <button type="button" class="diwan-help-secondary" wire:click="snooze(7)">Senyap 7 hari</button>
                </div>
            </form>
        </section>
    @endauth

    @if (config('diwan.guidance.support_enabled'))
        <section class="diwan-help-band" aria-labelledby="support-title" data-help-target="help-support">
            <div class="diwan-help-heading">
                <div>
                    <h2 id="support-title">Lapor masalah</h2>
                    <p>ID Permintaan: <code>{{ $requestId ?: 'tidak tersedia' }}</code></p>
                </div>
            </div>

            @if ($submittedReference)
                <div class="diwan-help-alert diwan-help-alert-success" role="status">
                    Laporan diterima. Rujukan: <strong>{{ $submittedReference }}</strong>
                </div>
            @endif

            <form wire:submit="submitSupport" class="diwan-help-support-form">
                <label>Kategori
                    <select wire:model="supportCategory">
                        @foreach ($diagnosisCategories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        <option value="lain">Lain-lain</option>
                    </select>
                </label>
                <label>Ringkasan masalah
                    <input type="text" wire:model="supportSubject" maxlength="180" autocomplete="off">
                    @error('supportSubject') <span class="diwan-help-error">{{ $message }}</span> @enderror
                </label>
                <label>Hasil yang dijangka
                    <textarea wire:model="supportExpected" rows="3" maxlength="5000"></textarea>
                    @error('supportExpected') <span class="diwan-help-error">{{ $message }}</span> @enderror
                </label>
                <label>Kejadian sebenar
                    <textarea wire:model="supportActual" rows="4" maxlength="5000"></textarea>
                    @error('supportActual') <span class="diwan-help-error">{{ $message }}</span> @enderror
                </label>
                <label>Lampiran pilihan, maksimum 5 MB
                    <input type="file" wire:model="supportAttachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                    @error('supportAttachment') <span class="diwan-help-error">{{ $message }}</span> @enderror
                </label>
                @if ($unmatchedQuery !== '')
                    <label class="diwan-help-check"><input type="checkbox" wire:model="queryConsent"> Sertakan pertanyaan carian tanpa hasil dalam tiket ini</label>
                @endif
                <button type="submit" wire:loading.attr="disabled">Hantar laporan</button>
            </form>

            @if ($tickets->isNotEmpty())
                <div class="diwan-help-tickets">
                    <h3>Tiket saya</h3>
                    @foreach ($tickets as $ticket)
                        <div><strong>{{ $ticket->reference }}</strong><span>{{ $ticket->subject }}</span><small>{{ ucfirst($ticket->status) }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</small></div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
