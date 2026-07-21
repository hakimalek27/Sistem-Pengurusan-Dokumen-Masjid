<?php

namespace App\Services;

use App\Models\Favourite;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class FavouriteService
{
    public function toggle(User $user, Mosque $mosque, string $type, int $id): bool
    {
        $target = $this->resolveVisible($user, $mosque, $type, $id);
        if (! $target) {
            throw new AuthorizationException('Objek tidak ditemui atau tidak boleh dilihat.');
        }

        $where = [
            'mosque_id' => $mosque->id,
            'user_id' => $user->id,
            'target_type' => $type,
            'target_id' => $target->getKey(),
        ];
        $existing = Favourite::query()->where($where)->first();
        if ($existing) {
            $existing->delete();

            return false;
        }

        Favourite::query()->create($where);

        return true;
    }

    public function resolveVisible(User $user, Mosque $mosque, string $type, int $id): ?Model
    {
        return match ($type) {
            Favourite::RECORD => Record::query()->visibleTo($user, $mosque)->find($id),
            Favourite::REGISTRY_FILE => RegistryFile::query()->visibleTo($user, $mosque)->find($id),
            default => null,
        };
    }
}
