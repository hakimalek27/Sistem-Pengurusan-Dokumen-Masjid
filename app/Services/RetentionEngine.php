<?php

namespace App\Services;

use App\Enums\RetentionAction;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RetentionRule;
use Illuminate\Support\Carbon;

/**
 * §16.3 / §5.11 — Enjin retensi. Kiraan retention_due_at & resolusi peraturan efektif.
 * Resolusi: masjid-spesifik > lalai platform; dalam skop sama: record_type > prefix panjang > pendek.
 */
class RetentionEngine
{
    /** Peraturan efektif untuk rekod (atau null jika tiada padanan). */
    public function effectiveRule(Record $record): ?RetentionRule
    {
        $record->loadMissing('registryFile.classificationNode');
        $nodeCode = $record->registryFile?->classificationNode?->code ?? '';

        $rules = RetentionRule::query()
            ->where(function ($q) use ($record) {
                $q->whereNull('mosque_id')->orWhere('mosque_id', $record->mosque_id);
            })
            ->get();

        $best = null;
        $bestScore = -1;

        foreach ($rules as $rule) {
            $score = $this->scoreRule($rule, $record->record_type, $nodeCode);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $rule;
            }
        }

        return $best;
    }

    /** Skor keutamaan; lebih tinggi = lebih diutamakan. -1 = tidak padan. */
    protected function scoreRule(RetentionRule $rule, string $recordType, string $nodeCode): int
    {
        $mosqueBonus = $rule->mosque_id !== null ? 1000 : 0;

        // Padanan record_type (paling spesifik).
        if ($rule->record_type !== null) {
            return $rule->record_type === $recordType ? $mosqueBonus + 500 : -1;
        }

        // Padanan classification_prefix (panjang prefix = spesifik).
        if ($rule->classification_prefix !== null) {
            return str_starts_with($nodeCode, $rule->classification_prefix)
                ? $mosqueBonus + 100 + strlen($rule->classification_prefix)
                : -1;
        }

        return -1;
    }

    /** Kira tarikh cukup tempoh; NULL jika kekal atau legal_hold (§16.3). */
    public function computeDueDate(Record $record): ?Carbon
    {
        if ($record->legal_hold) {
            return null;
        }

        $rule = $this->effectiveRule($record);

        if (! $rule || $rule->action === RetentionAction::Kekal || $rule->retain_years === null) {
            return null; // kekal
        }

        $base = $record->record_date ?? $record->filed_at;
        if (! $base) {
            return null;
        }

        return Carbon::parse($base)->copy()->addYears($rule->retain_years)->startOfDay();
    }

    /** Segarkan retention_due_at satu rekod (dipanggil pada failkan / toggle hold / ubah peraturan). */
    public function refreshForRecord(Record $record): void
    {
        $due = $this->computeDueDate($record);

        $record->updateQuietly(['retention_due_at' => $due?->toDateString()]);
    }

    /** Segarkan semua rekod difailkan bagi masjid (bila peraturan berubah). */
    public function refreshForMosque(Mosque $mosque): void
    {
        Record::query()
            ->withoutGlobalScope('mosque')
            ->where('mosque_id', $mosque->id)
            ->whereIn('status', ['difailkan', 'diganti'])
            ->cursor()
            ->each(fn (Record $record) => $this->refreshForRecord($record));
    }

    /** Adakah rekod ini boleh dilupus automatik SEKARANG (§16.3 syarat penuh)? */
    public function isEligibleForAutoDisposal(Record $record, Mosque $mosque): bool
    {
        $notified = $record->retention_notified ?? [];

        return $record->retention_due_at !== null
            && $record->retention_due_at->startOfDay()->lte(now()->startOfDay())
            && ! $record->legal_hold
            && ($this->effectiveRule($record)?->action === RetentionAction::AutoPadam)
            && $mosque->auto_disposal_enabled
            && $mosque->isActive()                       // masjid digantung → JEDA
            && ! empty($notified['t30'])                 // t30 DAN t7 mesti sudah dihantar
            && ! empty($notified['t7']);
    }
}
