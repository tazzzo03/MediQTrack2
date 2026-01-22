<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;

class FirestoreService
{
    protected $firestore;

    public function __construct()
    {
        $this->firestore = new FirestoreClient([
            'projectId' => env('FIREBASE_PROJECT_ID'),
            'keyFilePath' => env('FIREBASE_CREDENTIALS'),
            'transport' => 'rest',
        ]);
    }

    /**
     * Delete queue document from Firestore
     */
    public function deleteQueueRecordByUid($firebaseUid)
    {
        try {
            $collection = $this->firestore->collection('queues');

            // Cari document di mana field "firebase_uid" sama dengan UID pesakit
            $query = $collection->where('firebase_uid', '=', $firebaseUid);
            $documents = $query->documents();

            foreach ($documents as $document) {
                $document->reference()->delete();
                Log::info("Firestore queue deleted for UID: {$firebaseUid}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Firestore delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function upsertQueueStatusByUid(string $firebaseUid, string $queueNumber, string $status): bool
    {
        try {
            $this->firestore->collection('queues')
                ->document($firebaseUid)
                ->set([
                    'firebase_uid' => $firebaseUid,
                    'queue_number' => $queueNumber,
                    'status' => $status,
                    'updated_at' => now()->timestamp,
                ], ['merge' => true]);

            return true;
        } catch (\Exception $e) {
            Log::error('Firestore queue update error: ' . $e->getMessage());
            return false;
        }
    }
}
