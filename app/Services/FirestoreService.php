<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FirestoreService
{
    private FirestoreRestService $rest;

    public function __construct()
    {
        $this->rest = new FirestoreRestService();
    }

    /**
     * Delete queue document from Firestore
     */
    public function deleteQueueRecordByUid($firebaseUid)
    {
        try {
            $this->rest->deleteDocument('queues/' . $firebaseUid);
            Log::info("Firestore queue deleted for UID: {$firebaseUid}");

            return true;
        } catch (\Exception $e) {
            Log::error('Firestore delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function upsertQueueStatusByUid(string $firebaseUid, string $queueNumber, string $status): bool
    {
        try {
            $fields = [
                'firebase_uid' => $firebaseUid,
                'queue_number' => $queueNumber,
                'status' => $status,
                'updated_at' => now()->timestamp,
            ];
            $updateMask = ['firebase_uid', 'queue_number', 'status', 'updated_at'];

            $this->rest->patchDocument('queues/' . $firebaseUid, $fields, $updateMask);

            return true;
        } catch (\Exception $e) {
            Log::error('Firestore queue update error: ' . $e->getMessage());
            return false;
        }
    }
}
