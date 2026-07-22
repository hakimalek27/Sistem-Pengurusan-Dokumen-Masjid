<?php

namespace App\Services;

use App\Models\GuidancePreference;
use App\Models\GuidanceProgress;
use App\Models\HelpEvent;
use App\Models\Mosque;
use App\Models\User;

class GuidanceService
{
    public function contextKey(string $panel, ?Mosque $mosque): string
    {
        return $panel === 'app' && $mosque ? "tenant:{$mosque->id}" : $panel;
    }

    public function preference(User $user, string $panel, ?Mosque $mosque): GuidancePreference
    {
        return GuidancePreference::query()->firstOrCreate(
            ['user_id' => $user->id, 'context_key' => $this->contextKey($panel, $mosque)],
            [
                'mosque_id' => $mosque?->id,
                'mode' => 'lengkap',
                'auto_start_enabled' => true,
                'nudges_enabled' => true,
                'digest_email' => false,
                'digest_whatsapp' => false,
                'digest_telegram' => false,
            ],
        );
    }

    public function progress(User $user, string $panel, ?Mosque $mosque, array $guide): GuidanceProgress
    {
        return GuidanceProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'context_key' => $this->contextKey($panel, $mosque),
                'guide_id' => $guide['id'],
            ],
            [
                'mosque_id' => $mosque?->id,
                'guide_version' => (int) ($guide['version'] ?? 1),
                'status' => 'belum_mula',
            ],
        );
    }

    public function resumeStep(User $user, string $panel, ?Mosque $mosque, array $guide): int
    {
        $progress = GuidanceProgress::query()
            ->where('user_id', $user->id)
            ->where('context_key', $this->contextKey($panel, $mosque))
            ->where('guide_id', $guide['id'])
            ->first();

        if (! $progress || $progress->guide_version !== (int) ($guide['version'] ?? 1)
            || ! in_array($progress->status, ['dalam_proses', 'ditutup'], true)) {
            return 0;
        }

        return min(max(0, $progress->step_index), max(0, count($guide['steps'] ?? []) - 1));
    }

    public function record(User $user, string $panel, ?Mosque $mosque, array $guide, string $event, int $stepIndex = 0, ?string $target = null): GuidanceProgress
    {
        $progress = $this->progress($user, $panel, $mosque, $guide);
        $attributes = [
            'guide_version' => (int) ($guide['version'] ?? 1),
            'step_index' => max(0, $stepIndex),
            'last_seen_at' => now(),
        ];

        if ($event === 'started') {
            $attributes['status'] = 'dalam_proses';
            $attributes['started_at'] = $progress->started_at ?? now();
            $attributes['dismissed_until'] = null;
        } elseif ($event === 'completed') {
            $attributes['status'] = 'selesai';
            $attributes['completed_at'] = now();
        } elseif ($event === 'dismissed') {
            $attributes['status'] = 'ditutup';
        }

        $progress->update($attributes);
        HelpEvent::query()->create([
            'user_id' => $user->id,
            'mosque_id' => $mosque?->id,
            'panel' => $panel,
            'guide_id' => $guide['id'],
            'event' => $event,
            'metadata' => array_filter(['step_index' => $stepIndex, 'target' => $target]),
        ]);

        return $progress->fresh();
    }
}
