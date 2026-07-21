<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SavedSearchService
{
    public function save(User $user, Mosque $mosque, string $name, array $criteria, bool $isDefault = false): SavedSearch
    {
        if (! $user->isMemberOf($mosque) || ! $user->canIn($mosque, 'records.view')) {
            throw new AuthorizationException('Tiada kebenaran menyimpan carian untuk tenant ini.');
        }

        $name = trim($name);
        abort_if($name === '', 422, 'Nama carian diperlukan.');

        return DB::transaction(function () use ($user, $mosque, $name, $criteria, $isDefault): SavedSearch {
            if ($isDefault) {
                SavedSearch::query()->forMosque($mosque)->where('user_id', $user->id)->update(['is_default' => false]);
            }

            return SavedSearch::query()->updateOrCreate(
                ['mosque_id' => $mosque->id, 'user_id' => $user->id, 'name' => $name],
                ['criteria' => $criteria, 'is_default' => $isDefault],
            );
        });
    }

    public function use(User $user, Mosque $mosque, int $id): SavedSearch
    {
        $search = SavedSearch::query()->forMosque($mosque)->where('user_id', $user->id)->findOrFail($id);
        $search->update(['last_used_at' => now()]);

        return $search;
    }

    public function delete(User $user, Mosque $mosque, int $id): void
    {
        SavedSearch::query()->forMosque($mosque)->where('user_id', $user->id)->findOrFail($id)->delete();
    }
}
