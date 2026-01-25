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

        if (!$action) {
            return;
        }

        $steps = $this->parseSteps($action->steps_json ?? null);
        if (empty($steps)) {
            return;
        }

        $this->executeSteps($steps, $action, $queue);
    }

    private function parseSteps(?string $stepsJson): array
    {
        if (!$stepsJson) {
            return [];
        }

        $decoded = json_decode($stepsJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function executeSteps(array $steps, object $action, Queue $queue): void
    {
        foreach ($steps as $step) {
            if (!is_array($step) || empty($step['type'])) {
                continue;
            }

            switch ($step['type']) {
                case 'set_state':
                    if (!empty($step['state'])) {
                        $this->upsertQueueState($queue->queue_id, $step['state']);
                    }
                    break;
                case 'start_countdown':
                    $minutes = isset($step['minutes']) ? (int) $step['minutes'] : null;
                    $this->startCountdown($queue->queue_id, $minutes);
                    break;
                case 'stop_countdown':
                    $this->stopCountdown($queue->queue_id);
                    break;
                case 'remove_from_queue':
                    $this->removeFromQueue($queue);
                    break;
                case 'notify':
                    $this->sendNotificationFromStep($step, $action, $queue);
                    break;
            }
        }
    }

    private function sendNotificationFromStep(array $step, object $action, Queue $queue): void
    {
        if (!$queue->patient_id) {
            return;
        }

        $title = $step['title'] ?? 'Queue Alert';
        $message = $step['message'] ?? ($action->message_template ?? null);
        if (!$message) {
            return;
        }

        $message = str_replace(
            '{minutes}',
            (string) ($action->countdown_minutes ?? ''),
            $message
        );

        $this->notificationService->sendToPatient(
            $queue->patient_id,
            $title,
            $message,
            'queue'
        );
    }

    private function upsertQueueState(int $queueId, string $state): void
    {
        DB::table('queue_states')->updateOrInsert(
            ['queue_id' => $queueId],
            [
                'state' => $state,
            ]
        );
    }

    private function startCountdown(int $queueId, ?int $minutes = null): void
    {
        $endTime = $minutes ? now()->addMinutes($minutes) : null;
        DB::table('queue_countdowns')->updateOrInsert(
            ['queue_id' => $queueId],
            [
                'end_time' => $endTime,
                'is_active' => 1,
            ]
        );
    }

    private function stopCountdown(int $queueId): void
    {
        DB::table('queue_countdowns')
            ->where('queue_id', $queueId)
            ->update([
                'is_active' => 0,
                'end_time' => now(),
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

}
