<?php

namespace App\Jobs;

use App\Services\FirestoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteQueueFromFirestore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $firebaseUid;

    public function __construct(string $firebaseUid)
    {
        $this->firebaseUid = $firebaseUid;
    }

    public function handle(FirestoreService $firestoreService)
    {
        try {
            $firestoreService->deleteQueueRecordByUid($this->firebaseUid);
        } catch (\Throwable $e) {
            Log::error('DeleteQueueFromFirestore job failed', [
                'firebase_uid' => $this->firebaseUid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

