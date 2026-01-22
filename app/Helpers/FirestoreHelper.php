<?php

namespace App\Helpers;

use Google\Cloud\Firestore\FirestoreClient;

class FirestoreHelper
{
    public static function getFirestore()
    {
        return new FirestoreClient([
            'keyFilePath' => base_path(env('FIREBASE_CREDENTIALS')),
            'projectId' => env('FIREBASE_PROJECT_ID'),
        ]);
    }
}
