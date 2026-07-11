<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Record;
use App\Models\User;
use App\Notifications\ApprovalDecidedNotification;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * §9.C.7 / §10.D — e-Kelulusan (sah kata laluan semula + IP + timestamp + audit).
 */
class ApprovalService
{
    public function request(Record $record, User $requester, User $approver, ?string $note = null): Approval
    {
        if (! $requester->can('requestApproval', $record) || ! $requester->can('view', $record)) {
            throw new AuthorizationException('Tiada kebenaran memohon kelulusan rekod ini.');
        }

        if (! $approver->is_active
            || ! $approver->isMemberOf($record->mosque)
            || ! $approver->canIn($record->mosque, 'approvals.decide')
            || ! $approver->can('view', $record)) {
            throw ValidationException::withMessages(['approver' => 'Pelulus mesti ahli aktif tenant dan dibenarkan melihat rekod ini.']);
        }

        $approval = DB::transaction(fn () => Approval::query()->create([
            'mosque_id' => $record->mosque_id,
            'record_id' => $record->id,
            'requested_by' => $requester->id,
            'approver_id' => $approver->id,
            'status' => ApprovalStatus::Menunggu,
            'request_note' => $note,
        ]));

        Notification::send([$approver], new ApprovalRequestedNotification($approval));

        return $approval;
    }

    /** Kata laluan telah disahkan di UI (Filament password confirmation). */
    public function decide(Approval $approval, User $approver, ApprovalStatus $decision, ?string $note, ?string $ip): void
    {
        DB::transaction(function () use ($approval, $approver, $decision, $note, $ip): void {
            $locked = Approval::query()->withoutGlobalScope('mosque')->lockForUpdate()->findOrFail($approval->id);

            if (! $approver->can('decide', $locked)) {
                throw new AuthorizationException('Pengguna bukan pelulus yang ditetapkan.');
            }

            if ($locked->status !== ApprovalStatus::Menunggu) {
                throw ValidationException::withMessages(['approval' => 'Keputusan kelulusan telah direkodkan.']);
            }

            $locked->update([
                'status' => $decision,
                'decision_note' => $note,
                'decided_at' => now(),
                'decision_ip' => $ip,
            ]);
        });

        $approval->refresh();

        activity()
            ->performedOn($approval)
            ->causedBy($approver)
            ->withProperties(['ip' => $ip, 'decision' => $decision->value])
            ->log('kelulusan');

        Notification::send([$approval->requestedBy], new ApprovalDecidedNotification($approval));
    }
}
