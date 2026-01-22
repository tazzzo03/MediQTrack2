<?php

namespace App\Services;

use App\Models\Queue;
use Illuminate\Support\Facades\DB;

class QueueActionHandler
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(string $actionCode, Queue $queue): void
    {
        $action = DB::table('queue_actions')
            ->where('action_code', $actionCode)
            ->first();

        switch ($actionCode) {
            case 'SET_STATE_WAITING':
                $this->upsertQueueState($queue->queue_id, 'waiting');
                break;
            case 'SET_STATE_FINAL_CALL':
                $this->upsertQueueState($queue->queue_id, 'final_call');
                break;
            case 'START_FINAL_COUNTDOWN':
                $this->startCountdown($queue->queue_id);
                break;
            case 'STOP_FINAL_COUNTDOWN':
                $this->stopCountdown($queue->queue_id);
                break;
            case 'REMOVE_FROM_QUEUE':
                $this->removeFromQueue($queue);
                break;
            case 'NOTIFY_RETURN_TO_CLINIC':
                $this->notifyReturnToClinic($queue);
                break;
        }

        if ($action && (bool) $action->removes_user) {
            $this->removeFromQueue($queue);
        }

        $this->maybeNotifyAction($action, $queue);
    }

    private function upsertQueueState(int $queueId, string $state): void
    {
        DB::table('queue_states')->updateOrInsert(
            ['queue_id' => $queueId],
            [
                'state' => $state,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function startCountdown(int $queueId): void
    {
        DB::table('queue_countdowns')->updateOrInsert(
            ['queue_id' => $queueId],
            [
                'started_at' => now(),
                'ended_at' => null,
                'is_active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function stopCountdown(int $queueId): void
    {
        DB::table('queue_countdowns')
            ->where('queue_id', $queueId)
            ->update([
                'ended_at' => now(),
                'is_active' => 0,
                'updated_at' => now(),
            ]);
    }

    private function removeFromQueue(Queue $queue): void
    {
        $queue->status = 'auto_cancelled';
        $queue->save();

        if ($queue->patient && $queue->patient->firebase_uid) {
            $firestore = new FirestoreService();
            $firestore->upsertQueueStatusByUid(
                $queue->patient->firebase_uid,
                (string) $queue->queue_number,
                $queue->status
            );
        }
    }

    private function notifyReturnToClinic(Queue $queue): void
    {
        if ($queue->patient_id) {
            $this->notificationService->sendToPatient(
                $queue->patient_id,
                'Please return to clinic',
                'Your queue is now approaching. Please return to the clinic.',
                'queue'
            );
        }
    }

    private function maybeNotifyAction(?object $action, Queue $queue): void
    {
        if (!$action || !$queue->patient_id || empty($action->message_template)) {
            return;
        }

        $message = str_replace(
            '{minutes}',
            (string) ($action->countdown_minutes ?? ''),
            $action->message_template
        );

        $this->notificationService->sendToPatient(
            $queue->patient_id,
            'Queue Alert',
            $message,
            'queue'
        );
    }
}
