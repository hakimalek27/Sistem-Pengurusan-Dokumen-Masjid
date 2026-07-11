<?php

namespace App\Observers;

use App\Models\Mosque;
use App\Models\RetentionRule;
use App\Services\RetentionEngine;

class RetentionRuleObserver
{
    public function created(RetentionRule $rule): void
    {
        $this->refresh($rule->mosque_id);
    }

    public function updated(RetentionRule $rule): void
    {
        $this->refresh($rule->mosque_id);

        $oldMosqueId = $rule->getOriginal('mosque_id');
        if ($oldMosqueId !== $rule->mosque_id) {
            $this->refresh($oldMosqueId === null ? null : (int) $oldMosqueId);
        }
    }

    public function deleted(RetentionRule $rule): void
    {
        $this->refresh($rule->mosque_id);
    }

    protected function refresh(?int $mosqueId): void
    {
        $engine = app(RetentionEngine::class);

        if ($mosqueId !== null) {
            $mosque = Mosque::query()->find($mosqueId);
            if ($mosque) {
                $engine->refreshForMosque($mosque);
            }

            return;
        }

        Mosque::query()->cursor()->each(fn (Mosque $mosque) => $engine->refreshForMosque($mosque));
    }
}
