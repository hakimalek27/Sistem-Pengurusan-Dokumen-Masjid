<?php

namespace App\Services;

use App\Models\FileMovement;
use App\Models\RegistryFile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FileTrackingService
{
    public function checkout(RegistryFile $file, User $actor, array $data): FileMovement
    {
        $this->authorize($file, $actor);
        $this->physical($file);

        $holderUserId = filled($data['holder_user_id'] ?? null) ? (int) $data['holder_user_id'] : null;
        $holderName = trim((string) ($data['holder_name'] ?? '')) ?: null;
        if (! $holderUserId && ! $holderName) {
            throw ValidationException::withMessages(['holder' => 'Pilih ahli atau nyatakan nama pemegang fail.']);
        }
        if ($holderUserId && ! $file->mosque->users()->where('users.id', $holderUserId)->where('users.is_active', true)->exists()) {
            throw ValidationException::withMessages(['holder_user_id' => 'Pemegang mesti ahli aktif tenant fail ini.']);
        }

        return DB::transaction(function () use ($file, $actor, $data, $holderUserId, $holderName): FileMovement {
            $locked = RegistryFile::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($file->id);
            $this->physical($locked);
            if ($locked->custody_status === 'dipinjam') {
                throw ValidationException::withMessages(['file' => 'Fail ini sedang dipinjam.']);
            }

            $movement = $locked->movements()->create([
                'mosque_id' => $locked->mosque_id,
                'action' => 'keluar',
                'from_location' => $locked->physical_location,
                'to_location' => trim((string) ($data['to_location'] ?? '')) ?: null,
                'holder_user_id' => $holderUserId,
                'holder_name' => $holderName,
                'due_at' => $data['due_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'handled_by' => $actor->id,
            ]);
            $locked->update([
                'custody_status' => 'dipinjam',
                'current_holder_user_id' => $holderUserId,
                'current_holder_name' => $holderName,
                'custody_due_at' => $data['due_at'] ?? null,
                'physical_location' => trim((string) ($data['to_location'] ?? '')) ?: $locked->physical_location,
            ]);

            $holder = $movement->holder_user_id
                ? User::query()->find($movement->holder_user_id)?->name
                : $movement->holder_name;
            app(MosqueActivityLogger::class)->log(
                $locked->mosque,
                'physical_file_checked_out',
                $actor->name.' mengeluarkan fail fizikal '.$locked->file_no.' kepada '.($holder ?: 'pemegang yang direkodkan').'.',
                $actor,
                $movement,
                file: $locked,
                metadata: [
                    'holder' => $holder,
                    'from_location' => $movement->from_location,
                    'to_location' => $movement->to_location,
                    'due_at' => $movement->due_at?->toIso8601String(),
                    'notes' => $movement->notes,
                ],
            );

            return $movement;
        });
    }

    public function return(RegistryFile $file, User $actor, ?string $location, ?string $notes = null): FileMovement
    {
        $this->authorize($file, $actor);
        $this->physical($file);

        return DB::transaction(function () use ($file, $actor, $location, $notes): FileMovement {
            $locked = RegistryFile::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($file->id);
            $this->physical($locked);
            if ($locked->custody_status !== 'dipinjam') {
                throw ValidationException::withMessages(['file' => 'Fail ini tidak sedang dipinjam.']);
            }

            $destination = trim((string) $location) ?: $locked->physical_location;
            $movement = $locked->movements()->create([
                'mosque_id' => $locked->mosque_id,
                'action' => 'masuk',
                'from_location' => $locked->physical_location,
                'to_location' => $destination,
                'returned_at' => now(),
                'notes' => $notes,
                'handled_by' => $actor->id,
            ]);
            $locked->update([
                'custody_status' => 'dalam_simpanan',
                'current_holder_user_id' => null,
                'current_holder_name' => null,
                'custody_due_at' => null,
                'physical_location' => $destination,
            ]);

            app(MosqueActivityLogger::class)->log(
                $locked->mosque,
                'physical_file_returned',
                $actor->name.' memulangkan fail fizikal '.$locked->file_no.' ke '.$destination.'.',
                $actor,
                $movement,
                file: $locked,
                metadata: ['from_location' => $movement->from_location, 'to_location' => $destination, 'notes' => $notes],
            );

            return $movement;
        });
    }

    public function relocate(RegistryFile $file, User $actor, string $location, ?string $notes = null): FileMovement
    {
        $this->authorize($file, $actor);
        $this->physical($file);
        if (trim($location) === '') {
            throw ValidationException::withMessages(['location' => 'Lokasi baharu diperlukan.']);
        }

        return DB::transaction(function () use ($file, $actor, $location, $notes): FileMovement {
            $locked = RegistryFile::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($file->id);
            $this->physical($locked);
            $movement = $locked->movements()->create([
                'mosque_id' => $locked->mosque_id,
                'action' => 'pindah',
                'from_location' => $locked->physical_location,
                'to_location' => trim($location),
                'notes' => $notes,
                'handled_by' => $actor->id,
            ]);
            $locked->update(['physical_location' => trim($location)]);

            app(MosqueActivityLogger::class)->log(
                $locked->mosque,
                'physical_file_relocated',
                $actor->name.' memindahkan lokasi fail fizikal '.$locked->file_no.' ke '.trim($location).'.',
                $actor,
                $movement,
                file: $locked,
                metadata: ['from_location' => $movement->from_location, 'to_location' => trim($location), 'notes' => $notes],
            );

            return $movement;
        });
    }

    protected function authorize(RegistryFile $file, User $actor): void
    {
        if (! $actor->can('track', $file)) {
            throw new AuthorizationException('Tiada kebenaran menjejak fail ini.');
        }
    }

    protected function physical(RegistryFile $file): void
    {
        if ($file->medium === 'elektronik') {
            throw ValidationException::withMessages(['medium' => 'Tracking fizikal hanya untuk fail fizikal atau hibrid.']);
        }
    }
}
