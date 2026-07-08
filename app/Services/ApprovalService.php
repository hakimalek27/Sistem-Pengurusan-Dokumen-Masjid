<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Record;
use App\Models\User;
use App\Notifications\ApprovalDecidedNotification;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * §9.C.7 / §10.D — e-Kelulusan (sah kata laluan semula + IP + timestamp + audit).
 */
class ApprovalService
{
    public function request(Record $record, User $requester, User $approver, ?string $note = null): Approval
    {
        $approval = Approval::query()->create([
            'mosque_id' => $record->mosque_id,
            'record_id' => $record->id,
            'requested_by' => $requester->id,
            'approver_id' => $approver->id,
            'status' => ApprovalStatus::Menunggu,
            'request_note' => $note,
        ]);

        Notification::send([$approver], new ApprovalRequestedNotification($approval));

        return $approval;
    }

    /** Kata laluan telah disahkan di UI (Filament password confirmation). */
    public function decide(Approval $approval, User $approver, ApprovalStatus $decision, ?string $note, ?string $ip): void
    {
        $approval->update([
            'status' => $decision,
            'decision_note' => $note,
            'decided_at' => now(),
            'decision_ip' => $ip,
        ]);

        activity()
            ->performedOn($approval)
            ->causedBy($approver)
            ->withProperties(['ip' => $ip, 'decision' => $decision->value])
            ->log('kelulusan');

        Notification::send([$approval->requestedBy], new ApprovalDecidedNotification($approval));
    }
}
