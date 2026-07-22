<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Minit;
use App\Models\MinitRecipient;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RecordCorrectionRequest;
use App\Models\StorageOrder;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Support\MailIntakeHealth;
use Illuminate\Support\Collection;

class UserTaskService
{
    public function for(User $user, string $panel, ?Mosque $mosque = null): Collection
    {
        if (! config('diwan.guidance.nudges_enabled')) {
            return collect();
        }

        return $panel === 'admin' ? $this->forSuperadmin($user) : $this->forTenant($user, $mosque);
    }

    public function actionableCount(User $user, string $panel, ?Mosque $mosque = null): int
    {
        return $this->for($user, $panel, $mosque)
            ->where('ownership', 'personal')
            ->whereIn('status', ['Lewat', 'Perlu tindakan'])
            ->sum('count');
    }

    protected function forTenant(User $user, ?Mosque $mosque): Collection
    {
        if (! $mosque || ! $user->isMemberOf($mosque)) {
            return collect();
        }

        $base = '/app/'.$mosque->slug;
        $tasks = collect();
        $visibleRecordIds = Record::query()->visibleTo($user, $mosque)->select('records.id');
        $principalIds = app(DelegationService::class)->principalIdsFor($user, $mosque, 'minit');
        $recipientUserIds = collect([$user->id])->merge($principalIds)->unique();
        $minitIds = MinitRecipient::query()
            ->whereIn('user_id', $recipientUserIds)
            ->where('jenis', 'tindakan')
            ->where('status', '!=', 'selesai')
            ->pluck('minit_id');

        $overdue = Minit::query()->forMosque($mosque)->whereIn('record_id', clone $visibleRecordIds)
            ->whereIn('id', $minitIds)->where('status', 'terbuka')->whereDate('due_at', '<', today())->count();
        $openMinit = Minit::query()->forMosque($mosque)->whereIn('record_id', clone $visibleRecordIds)
            ->whereIn('id', $minitIds)->where('status', 'terbuka')->where(fn ($query) => $query->whereNull('due_at')->orWhereDate('due_at', '>=', today()))->count();

        $this->push($tasks, 'minit-overdue', 'Lewat', 'Minit lewat saya', $overdue, 'Tindakan melepasi tarikh akhir.', $base.'/minit-saya?tableFilters[kategori][value]=tindakan', 'minit');
        $this->push($tasks, 'minit-open', 'Perlu tindakan', 'Minit perlu tindakan', $openMinit, 'Arahan yang belum diselesaikan.', $base.'/minit-saya?tableFilters[kategori][value]=tindakan', 'minit');

        if ($user->canIn($mosque, 'approvals.decide')) {
            $approverIds = collect([$user->id])->merge(app(DelegationService::class)->principalIdsFor($user, $mosque, 'approvals'))->unique();
            $approvalCount = Approval::query()->forMosque($mosque)->whereIn('record_id', clone $visibleRecordIds)
                ->whereIn('approver_id', $approverIds)->where('status', 'menunggu')->count();
            $this->push($tasks, 'approvals', 'Perlu tindakan', 'Kelulusan menunggu keputusan', $approvalCount, 'Buka rekod asal sebelum membuat keputusan.', $base.'/kelulusan', 'approval');
        }

        if ($user->canIn($mosque, 'inbox.classify')) {
            $readyInbox = Record::query()->forMosque($mosque)->where('status', 'peti_masuk')
                ->whereNotIn('ocr_status', ['belum', 'dalam_proses'])->whereIn('virus_scan_status', ['clean', 'disabled'])->count();
            $processingInbox = Record::query()->forMosque($mosque)->where('status', 'peti_masuk')
                ->where(fn ($query) => $query->whereIn('ocr_status', ['belum', 'dalam_proses'])->orWhereNotIn('virus_scan_status', ['clean', 'disabled']))->count();
            $this->push($tasks, 'inbox-ready', 'Perlu tindakan', 'Peti Masuk sedia diklasifikasi', $readyInbox, 'Dokumen sudah melalui pemeriksaan awal.', $base.'/peti-masuk', 'inbox', 'team');
            $this->push($tasks, 'inbox-processing', 'Menunggu sistem', 'Dokumen sedang diproses', $processingInbox, 'Antivirus atau OCR masih berjalan.', $base.'/peti-masuk', 'system', 'team');
        }

        if ($user->canIn($mosque, 'records.update')) {
            $corrections = RecordCorrectionRequest::query()->forMosque($mosque)->whereIn('record_id', clone $visibleRecordIds)->where('status', 'menunggu')->count();
            $this->push($tasks, 'corrections', 'Perlu tindakan', 'Pembetulan menunggu semakan', $corrections, 'Bandingkan nilai asal dan cadangan.', $base.'/pembetulan-rekod', 'correction', 'team');
        } else {
            $corrections = RecordCorrectionRequest::query()->forMosque($mosque)->where('requested_by', $user->id)->where('status', 'menunggu')->count();
            $this->push($tasks, 'corrections-waiting', 'Menunggu orang lain', 'Pembetulan sedang disemak', $corrections, 'Permohonan telah dihantar kepada reviewer.', $base.'/pembetulan-rekod', 'correction');
        }

        $waitingApprovals = Approval::query()->forMosque($mosque)->where('requested_by', $user->id)->where('status', 'menunggu')->count();
        $this->push($tasks, 'approvals-waiting', 'Menunggu orang lain', 'Permohonan kelulusan dihantar', $waitingApprovals, 'Pelulus belum membuat keputusan.', $base.'/kelulusan', 'approval');

        if ($user->canIn($mosque, 'storage.order')) {
            $orders = StorageOrder::query()->forMosque($mosque)->where('ordered_by', $user->id)->where('status', 'menunggu_bayaran')->count();
            $this->push($tasks, 'storage-waiting', 'Menunggu orang lain', 'Pesanan storan menunggu', $orders, 'Kuota belum bertambah sehingga pembayaran disahkan.', $base.'/penggunaan', 'storage');
        }

        if ($user->canIn($mosque, 'mosque.settings') && blank(data_get($mosque->settings, 'onboarding_done'))) {
            $this->push($tasks, 'onboarding', 'Cadangan', 'Lengkapkan persediaan masjid', 1, 'Tetapkan saluran dan ahli awal.', $base.'/persediaan', 'setup', 'team');
        }

        if ($user->canIn($mosque, 'usage.view') && app(QuotaService::class)->usagePercent($mosque) >= 80) {
            $this->push($tasks, 'quota', 'Cadangan', 'Semak kapasiti storan', 1, 'Penggunaan storan telah mencapai sekurang-kurangnya 80%.', $base.'/penggunaan', 'storage', 'team');
        }

        $completed = MinitRecipient::query()->where('user_id', $user->id)->where('status', 'selesai')->where('updated_at', '>=', now()->subDays(7))->count();
        $this->push($tasks, 'completed', 'Selesai', 'Tindakan selesai 7 hari ini', $completed, 'Ringkasan peribadi, bukan skor prestasi.', $base.'/minit-saya?tableFilters[kategori][value]=selesai', 'complete');

        return $tasks->values();
    }

    protected function forSuperadmin(User $user): Collection
    {
        if (! $user->is_superadmin) {
            return collect();
        }

        $tasks = collect();
        $pendingMosques = Mosque::query()->where('status', 'menunggu')->count();
        $pendingOrders = StorageOrder::query()->withoutMosqueScope()->where('status', 'menunggu_bayaran')->count();
        $downChannels = WhatsAppIntegration::query()->withoutMosqueScope()->where('enabled', true)->where('status', '!=', 'connected')->count();
        $mailHealth = MailIntakeHealth::evaluate();

        $this->push($tasks, 'tenant-approvals', 'Perlu tindakan', 'Tenant menunggu kelulusan', $pendingMosques, 'Semak identiti organisasi sebelum mengaktifkan.', '/admin/mosques', 'tenant', 'team');
        $this->push($tasks, 'storage-orders', 'Perlu tindakan', 'Pesanan storan menunggu', $pendingOrders, 'Sahkan pembayaran sebelum kuota ditambah.', '/admin/storage-orders', 'storage', 'team');
        $this->push($tasks, 'channels', 'Perlu tindakan', 'Sambungan WhatsApp bermasalah', $downChannels, 'Semak status tanpa membuka dokumen tenant.', '/admin/status-sambungan', 'channel', 'team');
        if (MailIntakeHealth::isUnhealthy($mailHealth['state'])) {
            $this->push($tasks, 'mail-intake', 'Lewat', 'Intake e-mel tidak sihat', 1, $mailHealth['description'], '/admin/status-sambungan', 'channel', 'team');
        }

        return $tasks->values();
    }

    protected function push(Collection $tasks, string $id, string $status, string $label, int $count, string $description, string $url, string $type, string $ownership = 'personal'): void
    {
        if ($count < 1) {
            return;
        }
        $tasks->push(compact('id', 'status', 'label', 'count', 'description', 'url', 'type', 'ownership'));
    }
}
