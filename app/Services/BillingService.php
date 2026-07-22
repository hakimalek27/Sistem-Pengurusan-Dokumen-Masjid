<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Mosque;
use App\Models\PlatformSetting;
use App\Models\StorageAddon;
use App\Models\StorageOrder;
use App\Models\User;
use App\Notifications\AddonExpiringNotification;
use App\Notifications\NewStorageOrderNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * §10.J / §5.13 — Bil & storan (aliran invois-manual MVP).
 */
class BillingService
{
    public function blockGb(): int
    {
        return (int) (PlatformSetting::get('pricing', ['block_gb' => 10])['block_gb'] ?? 10);
    }

    public function pricePerGbYearRm(): float
    {
        return (float) (PlatformSetting::get('pricing', ['per_gb_year_rm' => null])['per_gb_year_rm'] ?? 0);
    }

    /** Invois bersiri platform-global INV-{YYYY}-{0001}. */
    public function nextInvoiceNo(): string
    {
        $year = now()->format('Y');
        $key = 'invoice_sequence_'.$year;
        $lastInvoice = StorageOrder::query()->withoutGlobalScope('mosque')
            ->where('invoice_no', 'like', "INV-{$year}-%")
            ->orderByDesc('invoice_no')
            ->value('invoice_no');
        $existingSequence = $lastInvoice ? (int) substr($lastInvoice, -4) : 0;
        PlatformSetting::query()->firstOrCreate(['key' => $key], ['value' => ['seq' => $existingSequence]]);
        $counter = PlatformSetting::query()->where('key', $key)->lockForUpdate()->firstOrFail();
        $seq = ((int) ($counter->value['seq'] ?? 0)) + 1;
        $counter->update(['value' => ['seq' => $seq]]);

        return sprintf('INV-%s-%04d', $year, $seq);
    }

    /** §9.C.10 / Aliran J — Jana pesanan storan + invois PDF; status menunggu bayaran. */
    public function createOrder(Mosque $mosque, ?User $user, int $blocks, int $periodMonths = 12, ?string $idempotencyKey = null): StorageOrder
    {
        if (! $user || ! $user->canIn($mosque, 'storage.order')) {
            throw new AuthorizationException('Tiada kebenaran membuat pesanan storan.');
        }

        if ($blocks < 1 || $periodMonths < 0) {
            throw ValidationException::withMessages(['blocks' => 'Blok mesti sekurang-kurangnya satu dan tempoh tidak boleh negatif.']);
        }

        if ($idempotencyKey) {
            $existing = StorageOrder::query()->withoutGlobalScope('mosque')->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->mosque_id !== $mosque->id || $existing->ordered_by !== $user->id) {
                    throw new AuthorizationException('Kunci idempotensi tidak sepadan.');
                }

                return $existing;
            }
        }

        $gb = $blocks * $this->blockGb();
        $unitCents = (int) round($this->pricePerGbYearRm() * 100);
        $amountCents = $gb * $unitCents;
        $invoicePath = null;

        try {
            $order = DB::transaction(function () use ($mosque, $user, $gb, $unitCents, $amountCents, $periodMonths, $idempotencyKey, &$invoicePath) {
                $order = StorageOrder::query()->create([
                    'mosque_id' => $mosque->id,
                    'ordered_by' => $user->id,
                    'gb' => $gb,
                    'unit_price_cents' => $unitCents,
                    'amount_cents' => $amountCents,
                    'period_months' => $periodMonths,
                    'status' => OrderStatus::MenungguBayaran,
                    'invoice_no' => $this->nextInvoiceNo(),
                    'idempotency_key' => $idempotencyKey,
                ]);

                $invoicePath = $this->generateInvoicePdf($order);
                $order->update(['invoice_path' => $invoicePath]);

                return $order;
            });
        } catch (Throwable $e) {
            if ($invoicePath) {
                Storage::disk(config('diwan.storage_disk'))->delete($invoicePath);
            }

            throw $e;
        }

        Notification::send(
            User::query()->where('is_superadmin', true)->where('is_active', true)->get(),
            new NewStorageOrderNotification($order),
        );

        app(MosqueActivityLogger::class)->log(
            $mosque,
            'storage_order_created',
            $user->name.' memohon tambahan storan '.$order->gb.' GB melalui invois '.$order->invoice_no.'.',
            $user,
            $order,
            metadata: ['order_id' => $order->id, 'invoice_no' => $order->invoice_no, 'gb' => $order->gb, 'amount_cents' => $order->amount_cents],
        );

        return $order;
    }

    public function generateInvoicePdf(StorageOrder $order): string
    {
        $order->loadMissing('mosque');
        $bank = PlatformSetting::get('bank_details', []);
        $amount = number_format($order->amount_cents / 100, 2);

        $html = '<html><body style="font-family:sans-serif;">'
            .'<h2>INVOIS — Diwan (Wehdah Solution)</h2>'
            ."<p><strong>No. Invois:</strong> {$order->invoice_no}<br>"
            .'<strong>Tarikh:</strong> '.now()->format('d/m/Y').'</p>'
            ."<p><strong>Kepada:</strong> {$order->mosque->name} ({$order->mosque->code})</p>"
            .'<table border="1" cellpadding="6" cellspacing="0" width="100%">'
            .'<tr><th align="left">Perkara</th><th align="right">Jumlah</th></tr>'
            ."<tr><td>Storan tambahan {$order->gb} GB ({$order->period_months} bulan)</td><td align=\"right\">RM {$amount}</td></tr>"
            ."<tr><td align=\"right\"><strong>Jumlah</strong></td><td align=\"right\"><strong>RM {$amount}</strong></td></tr>"
            .'</table>'
            .'<h4>Arahan Bayaran</h4>'
            .'<p>Bank: '.($bank['bank'] ?? '✋ (belum ditetapkan)').'<br>'
            .'Nama Akaun: '.($bank['account_name'] ?? '—').'<br>'
            .'No. Akaun: '.($bank['account_no'] ?? '—').'</p>'
            ."<p>Sila nyatakan rujukan {$order->invoice_no} semasa pembayaran.</p>"
            .'</body></html>';

        $path = "platform/invoices/{$order->invoice_no}.pdf";
        Storage::disk(config('diwan.storage_disk'))->put($path, Pdf::loadHTML($html)->output());

        return $path;
    }

    /** §10.K — Tandakan dibayar → add-on aktif (kata laluan disahkan di UI). */
    public function markPaid(StorageOrder $order, ?User $confirmer): StorageAddon
    {
        if (! $confirmer?->is_superadmin) {
            throw new AuthorizationException('Hanya superadmin boleh mengesahkan bayaran.');
        }

        $addon = DB::transaction(function () use ($order, $confirmer) {
            $locked = StorageOrder::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($order->id);

            if ($locked->status === OrderStatus::Dibayar) {
                return StorageAddon::query()->withoutGlobalScope('mosque')->where('storage_order_id', $locked->id)->firstOrFail();
            }

            if ($locked->status !== OrderStatus::MenungguBayaran) {
                throw ValidationException::withMessages(['order' => 'Hanya pesanan menunggu bayaran boleh disahkan.']);
            }

            $addon = StorageAddon::query()->withoutGlobalScope('mosque')->firstOrCreate(
                ['storage_order_id' => $locked->id],
                [
                    'mosque_id' => $locked->mosque_id,
                    'gb' => $locked->gb,
                    'starts_at' => now(),
                    'expires_at' => $locked->period_months > 0 ? now()->addMonths($locked->period_months) : null,
                    'status' => 'aktif',
                ],
            );

            $locked->update(['status' => OrderStatus::Dibayar, 'paid_at' => now(), 'confirmed_by' => $confirmer->id]);

            return $addon;
        });

        activity()->performedOn($order)->causedBy($confirmer)
            ->withProperties(['ip' => request()->ip()])->log('tandakan_dibayar');

        app(MosqueActivityLogger::class)->log(
            $order->mosque,
            'storage_order_paid',
            $confirmer->name.' mengesahkan bayaran invois '.$order->invoice_no.'; storan '.$order->gb.' GB diaktifkan.',
            $confirmer,
            $order,
            metadata: ['order_id' => $order->id, 'invoice_no' => $order->invoice_no, 'gb' => $order->gb],
        );

        return $addon;
    }

    /** Batalkan pesanan belum bayar dengan audit actor/IP/sebab. */
    public function cancelOrder(StorageOrder $order, ?User $canceller, string $reason): void
    {
        if (! $canceller?->is_superadmin) {
            throw new AuthorizationException('Hanya superadmin boleh membatalkan pesanan storan.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Sebab pembatalan wajib diisi.']);
        }

        DB::transaction(function () use ($order): void {
            $locked = StorageOrder::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($order->id);

            if ($locked->status !== OrderStatus::MenungguBayaran) {
                throw ValidationException::withMessages(['order' => 'Hanya pesanan menunggu bayaran boleh dibatalkan.']);
            }

            $locked->update(['status' => OrderStatus::Dibatalkan]);
        });

        activity()->performedOn($order)->causedBy($canceller)
            ->withProperties(['ip' => request()->ip(), 'reason' => trim($reason)])
            ->log('batal_pesanan_storan');

        app(MosqueActivityLogger::class)->log(
            $order->mosque,
            'storage_order_cancelled',
            $canceller->name.' membatalkan invois storan '.$order->invoice_no.'.',
            $canceller,
            $order,
            metadata: ['order_id' => $order->id, 'invoice_no' => $order->invoice_no, 'reason' => trim($reason)],
        );
    }

    /** §5.14 / Aliran J — Notis T-30/T-7, luput add-on → kira semula kuota. */
    public function processExpiringAddons(): array
    {
        $expired = 0;
        $notified = 0;

        StorageAddon::query()->withoutGlobalScope('mosque')
            ->where('status', 'aktif')->whereNotNull('expires_at')
            ->cursor()
            ->each(function (StorageAddon $addon) use (&$expired, &$notified) {
                if ($addon->expires_at->isPast()) {
                    $addon->update(['status' => 'luput']);
                    $expired++;
                    $this->notifyAddon($addon, 'luput');

                    return;
                }

                foreach ([30, 7] as $days) {
                    if ($addon->expires_at->isSameDay(now()->addDays($days)->startOfDay())) {
                        $this->notifyAddon($addon, $days);
                        $notified++;
                    }
                }
            });

        return ['expired' => $expired, 'notified' => $notified];
    }

    protected function notifyAddon(StorageAddon $addon, int|string $when): void
    {
        $mosque = $addon->mosque;
        if (! $mosque) {
            return;
        }

        $recipients = $mosque->users()->get()->filter(fn (User $u) => $u->canIn($mosque, 'usage.view') || $u->canIn($mosque, 'storage.order'));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AddonExpiringNotification($mosque, $addon->gb, $when, optional($addon->expires_at)->format('d/m/Y') ?? '—'));
        }
    }
}
