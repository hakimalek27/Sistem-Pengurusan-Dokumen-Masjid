<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Support\AllowedFormats;
use App\Support\MailIntakeHealth;

class HelpDiagnosisService
{
    public const CATEGORIES = [
        'login' => 'Log masuk',
        'upload' => 'Muat naik dokumen',
        'antivirus' => 'Antivirus',
        'ocr' => 'OCR',
        'quota' => 'Kuota storan',
        'intake' => 'E-mel / WhatsApp intake',
        'button' => 'Butang atau menu hilang',
        'classification' => 'Klasifikasi',
        'minit' => 'Minit',
        'approval' => 'Kelulusan',
        'notification' => 'Notifikasi',
    ];

    public function diagnose(string $category, ?User $user, ?Mosque $mosque, string $panel): array
    {
        if ($panel === 'public' || ! $user) {
            return $this->publicDiagnosis($category);
        }
        if ($panel === 'admin') {
            return $this->adminDiagnosis($category, $user);
        }
        if (! $mosque || ! $user->isMemberOf($mosque)) {
            return [$this->check('bahaya', 'Konteks tenant tidak sah', 'Log keluar dan masuk semula melalui tenant anda sendiri.', 'Pentadbir platform')];
        }

        return match ($category) {
            'upload' => $this->uploadChecks($user, $mosque),
            'antivirus' => [$this->check(config('diwan.clamav.enabled') ? 'baik' : 'amaran', 'Status antivirus', config('diwan.clamav.enabled') ? 'ClamAV aktif dan intake menggunakan mod fail-closed.' : 'Antivirus dimatikan dalam persekitaran ini.', 'Admin / Kerani')],
            'ocr' => $this->ocrChecks($user, $mosque),
            'quota' => $this->quotaChecks($user, $mosque),
            'intake' => $this->intakeChecks($user, $mosque),
            'button' => [$this->check('maklumat', 'Semakan kebenaran', 'Role anda ialah '.(config('roles.labels.'.$user->roleIn($mosque)) ?? 'tidak diketahui').'. Butang hanya muncul apabila permission, status dan sensitiviti membenarkan.', 'Admin / Kerani')],
            'classification' => $this->classificationChecks($user, $mosque),
            'minit' => [$this->check($user->canIn($mosque, 'minit.respond') ? 'baik' : 'amaran', 'Kebenaran minit', $user->canIn($mosque, 'minit.respond') ? 'Role boleh menerima dan memberi respons minit pada rekod yang boleh dilihat.' : 'Role ini tidak mempunyai kebenaran respons minit.', 'Admin / Kerani')],
            'approval' => $this->approvalChecks($user, $mosque),
            'notification' => $this->notificationChecks($user),
            default => [$this->check($user->is_active ? 'baik' : 'bahaya', 'Status akaun', $user->is_active ? 'Akaun aktif. Semak kata laluan, tenant dan had kadar jika login gagal.' : 'Akaun dinyahaktifkan.', 'Admin / Kerani')],
        };
    }

    protected function uploadChecks(User $user, Mosque $mosque): array
    {
        $usage = app(QuotaService::class)->usagePercent($mosque);

        return [
            $this->check($user->canIn($mosque, 'records.create') ? 'baik' : 'amaran', 'Kebenaran muat naik', $user->canIn($mosque, 'records.create') ? 'Role boleh mencipta rekod.' : 'Role ini tidak boleh mencipta rekod.', 'Admin / Kerani'),
            $this->check($usage < 100 ? 'baik' : 'bahaya', 'Kuota storan', number_format($usage, 1).'% digunakan.', 'Admin / Kerani'),
            $this->check('maklumat', 'Format dan saiz', AllowedFormats::label().'; maksimum '.config('diwan.max_upload_mb').' MB.', 'Pengguna'),
        ];
    }

    protected function ocrChecks(User $user, Mosque $mosque): array
    {
        $visibleIds = Record::query()->visibleTo($user, $mosque)->select('records.id');
        $failed = Record::query()->forMosque($mosque)->whereIn('id', clone $visibleIds)->where('ocr_status', 'gagal')->count();
        $processing = Record::query()->forMosque($mosque)->whereIn('id', clone $visibleIds)->whereIn('ocr_status', ['belum', 'dalam_proses'])->count();

        return [
            $this->check($failed ? 'amaran' : 'baik', 'OCR gagal', $failed ? "{$failed} rekod yang anda boleh lihat memerlukan semakan operator." : 'Tiada OCR gagal dalam skop anda.', 'Operator platform'),
            $this->check('maklumat', 'OCR sedang diproses', "{$processing} rekod masih dalam antrean atau pemprosesan.", 'Sistem'),
        ];
    }

    protected function quotaChecks(User $user, Mosque $mosque): array
    {
        $usage = app(QuotaService::class)->usagePercent($mosque);
        $severity = $usage >= 100 ? 'bahaya' : ($usage >= 80 ? 'amaran' : 'baik');

        return [$this->check($severity, 'Penggunaan storan', number_format($usage, 1).'% daripada kuota efektif digunakan.', $user->canIn($mosque, 'storage.order') ? 'Pengguna ini boleh memohon storan' : 'Admin / Kerani atau Bendahari')];
    }

    protected function intakeChecks(User $user, Mosque $mosque): array
    {
        if (! $user->canIn($mosque, 'mosque.settings')) {
            return [$this->check('maklumat', 'Akses diagnosis terhad', 'Status terperinci saluran hanya boleh dilihat Admin / Kerani untuk mengelakkan pendedahan konfigurasi.', 'Admin / Kerani')];
        }
        $wa = WhatsAppIntegration::query()->forMosque($mosque)->first();

        return [
            $this->check($mosque->waIntakeEnabled() ? 'baik' : 'amaran', 'Intake WhatsApp tenant', $mosque->waIntakeEnabled() ? 'Diaktifkan.' : 'Dimatikan dalam Tetapan Masjid.', 'Admin / Kerani'),
            $this->check($wa?->isReady() ? 'baik' : 'amaran', 'Sesi WhatsApp', $wa?->isReady() ? 'Tersambung dan mempunyai kredensial sesi.' : 'Belum sedia atau terputus.', 'Admin / Kerani'),
            $this->check($mosque->mailIntakeEnabled() ? 'baik' : 'amaran', 'Intake e-mel tenant', $mosque->mailIntakeEnabled() ? 'Diaktifkan.' : 'Dimatikan dalam Tetapan Masjid.', 'Admin / Kerani'),
        ];
    }

    protected function classificationChecks(User $user, Mosque $mosque): array
    {
        $openFiles = RegistryFile::query()->forMosque($mosque)->where('status', 'terbuka')->count();
        $inbox = Record::query()->forMosque($mosque)->where('status', 'peti_masuk')->count();

        return [
            $this->check($user->canIn($mosque, 'inbox.classify') ? 'baik' : 'amaran', 'Kebenaran klasifikasi', $user->canIn($mosque, 'inbox.classify') ? 'Role boleh mengklasifikasikan Peti Masuk.' : 'Role tidak dibenarkan mengklasifikasi.', 'Admin / Kerani'),
            $this->check($openFiles ? 'baik' : 'amaran', 'Fail destinasi terbuka', "{$openFiles} fail terbuka tersedia.", 'Admin / Kerani'),
            $this->check('maklumat', 'Item Peti Masuk', "{$inbox} item belum diklasifikasikan dalam tenant ini.", 'Admin / Kerani'),
        ];
    }

    protected function approvalChecks(User $user, Mosque $mosque): array
    {
        $visibleIds = Record::query()->visibleTo($user, $mosque)->select('records.id');
        $pending = Approval::query()->forMosque($mosque)->whereIn('record_id', $visibleIds)->where('approver_id', $user->id)->where('status', 'menunggu')->count();

        return [$this->check($user->canIn($mosque, 'approvals.decide') ? 'baik' : 'maklumat', 'Kelulusan pengguna', $user->canIn($mosque, 'approvals.decide') ? "{$pending} keputusan menunggu anda." : 'Role hanya boleh meminta kelulusan atau melihat status yang berkaitan.', 'Pengerusi / Nazir')];
    }

    protected function notificationChecks(User $user): array
    {
        return [
            $this->check($user->notify_email && filled($user->email) ? 'baik' : 'amaran', 'E-mel', $user->notify_email && filled($user->email) ? 'Aktif dan alamat tersedia.' : 'Tidak aktif atau alamat tiada.', 'Pengguna'),
            $this->check($user->notify_whatsapp && filled($user->phone_wa) ? 'baik' : 'amaran', 'WhatsApp', $user->notify_whatsapp && filled($user->phone_wa) ? 'Aktif dan nombor tersedia.' : 'Tidak aktif atau nombor tiada.', 'Pengguna'),
            $this->check($user->notify_telegram && filled($user->telegram_chat_id) ? 'baik' : 'amaran', 'Telegram', $user->notify_telegram && filled($user->telegram_chat_id) ? 'Aktif dan sudah dipautkan.' : 'Tidak aktif atau belum dipautkan.', 'Pengguna'),
        ];
    }

    protected function adminDiagnosis(string $category, User $user): array
    {
        if (! $user->is_superadmin) {
            return [$this->check('bahaya', 'Akses ditolak', 'Panel ini untuk superadmin.', 'Pentadbir platform')];
        }
        $mail = MailIntakeHealth::evaluate();
        $waDown = WhatsAppIntegration::query()->withoutMosqueScope()->where('enabled', true)->where('status', '!=', 'connected')->count();

        return [
            $this->check(MailIntakeHealth::isUnhealthy($mail['state']) ? 'bahaya' : 'baik', 'Intake e-mel platform', $mail['label'].': '.$mail['description'], 'Operator platform'),
            $this->check($waDown ? 'amaran' : 'baik', 'Sesi WhatsApp', "{$waDown} sambungan aktif tidak berstatus connected.", 'Operator platform'),
            $this->check('maklumat', 'Kategori dipilih', self::CATEGORIES[$category] ?? 'Umum', 'Superadmin'),
        ];
    }

    protected function publicDiagnosis(string $category): array
    {
        return [
            $this->check('maklumat', 'Semakan awam', $category === 'login' ? 'Pastikan permohonan telah diluluskan dan pautan belum tamat tempoh.' : 'Semak medan wajib, format e-mel/nombor telefon dan tunggu sebelum mengulang selepas had kadar.', 'Pentadbir platform'),
            $this->check('baik', 'Privasi', 'Diagnosis awam tidak membaca dokumen atau data tenant.', 'Sistem'),
        ];
    }

    protected function check(string $severity, string $title, string $message, string $owner): array
    {
        return compact('severity', 'title', 'message', 'owner');
    }
}
