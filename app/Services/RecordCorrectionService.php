<?php

namespace App\Services;

use App\Models\Record;
use App\Models\RecordCorrectionRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordCorrectionService
{
    public const FIELDS = [
        'title', 'record_type', 'our_ref', 'their_ref', 'record_date', 'received_date',
        'direction', 'sender_name', 'sender_org', 'recipient_name', 'sensitivity', 'metadata',
    ];

    public function request(Record $record, User $requester, string $reason, array $changes): RecordCorrectionRequest
    {
        if (! $requester->can('view', $record)) {
            throw new AuthorizationException('Tiada kebenaran melihat rekod ini.');
        }

        $changes = $this->validatedChanges($changes);
        $changes = collect($changes)->reject(fn ($value, $field) => $this->comparable($record->{$field}) === $this->comparable($value))->all();
        if ($changes === [] || trim($reason) === '') {
            throw ValidationException::withMessages(['changes' => 'Nyatakan sebab dan sekurang-kurangnya satu perubahan sebenar.']);
        }

        $request = RecordCorrectionRequest::query()->create([
            'mosque_id' => $record->mosque_id,
            'record_id' => $record->id,
            'requested_by' => $requester->id,
            'reason' => trim($reason),
            'proposed_changes' => $changes,
            'status' => 'menunggu',
        ]);

        $reviewers = $record->mosque->users()->where('users.is_active', true)->get()
            ->filter(fn (User $user) => $user->id !== $requester->id && $user->can('update', $record));
        if ($reviewers->isNotEmpty()) {
            Notification::make()->title('Permohonan pembetulan rekod baharu')
                ->body($record->title ?: $record->ulid)->warning()->sendToDatabase($reviewers);
        }

        activity()->performedOn($record)->causedBy($requester)
            ->withProperties(['correction_request_id' => $request->id, 'proposed_changes' => $changes])
            ->log('mohon_pembetulan');

        return $request;
    }

    public function review(RecordCorrectionRequest $request, User $reviewer, bool $approve, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $approve, $note): void {
            $locked = RecordCorrectionRequest::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($request->id);
            $record = Record::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($locked->record_id);

            if ($locked->mosque_id !== $record->mosque_id || ! $reviewer->can('review', $locked) || ! $reviewer->can('update', $record)) {
                throw new AuthorizationException('Tiada kebenaran menyemak pembetulan ini.');
            }
            if ($locked->status !== 'menunggu') {
                throw ValidationException::withMessages(['status' => 'Permohonan ini telah diputuskan.']);
            }

            $before = collect(array_keys($locked->proposed_changes))->mapWithKeys(fn ($field) => [$field => $record->{$field}])->all();
            if ($approve) {
                $record->update($this->validatedChanges($locked->proposed_changes));
            }
            $locked->update([
                'status' => $approve ? 'diluluskan' : 'ditolak',
                'reviewed_by' => $reviewer->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            activity()->performedOn($record)->causedBy($reviewer)->withProperties([
                'correction_request_id' => $locked->id,
                'decision' => $approve ? 'diluluskan' : 'ditolak',
                'before' => $before,
                'proposed_changes' => $locked->proposed_changes,
                'note' => $note,
            ])->log('semak_pembetulan');
        });

        $request->refresh();
        if ($approve) {
            $corrected = $request->record()->firstOrFail();
            app(RetentionEngine::class)->refreshForRecord($corrected);
            $corrected->searchable();
        }
        if ($request->requestedBy?->is_active && $request->requestedBy->can('view', $request->record)) {
            Notification::make()->title('Permohonan pembetulan '.$request->status)
                ->body($request->record?->title ?: 'Rekod')->success()->sendToDatabase($request->requestedBy);
        }
    }

    protected function validatedChanges(array $changes): array
    {
        $changes = collect($changes)->only(self::FIELDS)->all();

        return Validator::make($changes, [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'record_type' => ['sometimes', 'required', Rule::in(array_keys(config('record_types', [])))],
            'our_ref' => ['sometimes', 'nullable', 'string', 'max:255'],
            'their_ref' => ['sometimes', 'nullable', 'string', 'max:255'],
            'record_date' => ['sometimes', 'nullable', 'date'],
            'received_date' => ['sometimes', 'nullable', 'date'],
            'direction' => ['sometimes', 'nullable', Rule::in(['masuk', 'keluar', 'dalaman'])],
            'sender_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sender_org' => ['sometimes', 'nullable', 'string', 'max:255'],
            'recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sensitivity' => ['sometimes', 'required', Rule::in(['umum', 'dalaman', 'sulit'])],
            'metadata' => ['sometimes', 'array'],
        ])->validate();
    }

    protected function comparable(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : (string) $value;
    }
}
